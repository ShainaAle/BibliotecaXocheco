-- =============================================
-- triggers.sql
-- Library Managment System
-- =============================================
-- NOTE: Before each PHP operation stablish:
--   $pdo->exec("SET @current_user_id = " . $_SESSION['id_user']);
-- =============================================

USE mydb;

ALTER TABLE bookings
MODIFY COLUMN status
ENUM('En Espera', 'Listo para entrega', 'Entregado', 'Cancelado', 'Finalizado') NOT NULL;


-- =============================================
-- AUX Function: fn_add_business_days
-- Calculate a date by adding N business days.
-- (mon-fri) from a given date
-- Used by trg_before_loan_insert for calculate
-- the return deadline based on the user type
-- =============================================

DROP FUNCTION IF EXISTS fn_add_business_days;

DELIMITER $$

CREATE FUNCTION fn_add_business_days(p_start DATE, p_days INT)
RETURNS DATE
DETERMINISTIC
BEGIN
    DECLARE v_date  DATE;
    DECLARE v_count INT DEFAULT 0;

    SET v_date = p_start;

    WHILE v_count < p_days DO
        SET v_date = DATE_ADD(v_date, INTERVAL 1 DAY);
        -- DAYOFWEEK: 1=Sunday, 2=Monday ... 6=Friday, 7=Saturday
        IF DAYOFWEEK(v_date) NOT IN (1, 7) THEN
            SET v_count = v_count + 1;
        END IF;
    END WHILE;

    RETURN v_date;
END$$

DELIMITER ;


-- =============================================
-- MÓDULO: PRÉSTAMOS
-- =============================================

DELIMITER $$

-- ---------------------------------------------
-- Trigger 1: trg_before_loan_insert
--It runs BEFORE registering a loan.
-- Responsibilities:
-- 1. Verifies that the copy status is 'Disponible'.
--      If it is not, it throws an error and cancels
--      the transaction.
-- 2. Verifies that the user doesn't have more than 3
--      simultaneous active loans. 
--      If they do, it cancels the transaction.
-- 3. Automatically calculates the return deadline
--      date according to the user type:
--          - External User: 3 business days
--          - Student: 5 business days
--          - Faculty: 10 business days
--      It overwrites any value sent in the
--      return_deadline to ensure that the
--      business rule is always met.
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_before_loan_insert$$

CREATE TRIGGER trg_before_loan_insert
BEFORE INSERT ON loans
FOR EACH ROW
BEGIN
    DECLARE v_copy_status   VARCHAR(20);
    DECLARE v_active_loans  INT;
    DECLARE v_user_type     VARCHAR(30);
    DECLARE v_loan_days     INT;

    -- 1. verify availability of the copy
    SELECT status INTO v_copy_status
    FROM copies
    WHERE id_copy = NEW.id_copy;

    IF v_copy_status != 'Disponible' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El ejemplar no está disponible para préstamo.';
    END IF;

    -- 2. verify active loan limit
    SELECT COUNT(*) INTO v_active_loans
    FROM loans
    WHERE id_user = NEW.id_user
        AND status IN ('Activo', 'Con adeudo');

    IF v_active_loans >= 3 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario ya tiene 3 préstamos activos. No puede registrar más.';
    END IF;

    -- 3. calculate deadline based on user type
    SELECT ut.name INTO v_user_type
    FROM users u
    JOIN user_types ut ON u.id_user_type = ut.id_user_type
    WHERE u.id_user = NEW.id_user;

    IF v_user_type = 'Usuario Externo' THEN
        SET v_loan_days = 3;
    ELSEIF v_user_type = 'Alumno' THEN
        SET v_loan_days = 5;
    ELSE
        SET v_loan_days = 10; -- Docente
    END IF;

    SET NEW.return_deadline = fn_add_business_days(NEW.start_date, v_loan_days);
END$$


-- ---------------------------------------------
-- Trigger 2: trg_after_loan_insert
-- This process runs AFTER a loan is registered
-- Responsibilities:
--   1. Changes the copy status to 'Prestado'
--      immediately after the loan is created.
--   2. If the loan originates from a booking,
--      updates the booking status to 'Entregado', indicating
--      that the user has physically picked up the book.
--      The previous step, 'Listo para entrega', is managed
--      manually by the librarian through the interface
--   3. Records the event in the logs table
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_loan_insert$$

CREATE TRIGGER trg_after_loan_insert
AFTER INSERT ON loans
FOR EACH ROW
BEGIN
    -- 1. Update copy status
    UPDATE copies
    SET status = 'Prestado'
    WHERE id_copy = NEW.id_copy;

    -- 2. If it's from a booking, mark it as delivered
    --    (The user already has the physical book)
    IF NEW.id_booking IS NOT NULL THEN
        UPDATE bookings
        SET status = 'Entregado'
        WHERE id_booking = NEW.id_booking;
    END IF;

    -- 3. Audit
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, NEW.id_user),
        'loans',
        'Crear',
        NULL,
        JSON_OBJECT(
            'id_loan',         NEW.id_loan,
            'id_user',         NEW.id_user,
            'id_copy',         NEW.id_copy,
            'id_booking',      NEW.id_booking,
            'start_date',      NEW.start_date,
            'return_deadline', NEW.return_deadline,
            'status',          NEW.status
        )
    );
END$$


-- ---------------------------------------------
-- Trigger 3: trg_after_loan_update
-- It runs AFTER updating a loan.
-- Responsibilities:
--   1. If the loan changes to 'Cancelado', it restores 
--      the copy to 'Disponible' and, if it had an associated 
--      hold, it cancels that as well.
--   2. It logs the change with the previous status 
--      (old_data) and the new status (new_data).
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_loan_update$$

CREATE TRIGGER trg_after_loan_update
AFTER UPDATE ON loans
FOR EACH ROW
BEGIN
    -- 1. If the loan is cancelled
    IF NEW.status = 'Cancelado' AND OLD.status != 'Cancelado' THEN

        UPDATE copies
        SET status = 'Disponible'
        WHERE id_copy = NEW.id_copy;

    END IF;

    -- 2. Audit
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, NEW.id_user),
        'loans',
        'Actualizar',
        JSON_OBJECT(
            'id_loan',         OLD.id_loan,
            'id_user',         OLD.id_user,
            'id_copy',         OLD.id_copy,
            'id_booking',      OLD.id_booking,
            'start_date',      OLD.start_date,
            'return_deadline', OLD.return_deadline,
            'status',          OLD.status
        ),
        JSON_OBJECT(
            'id_loan',         NEW.id_loan,
            'id_user',         NEW.id_user,
            'id_copy',         NEW.id_copy,
            'id_booking',      NEW.id_booking,
            'start_date',      NEW.start_date,
            'return_deadline', NEW.return_deadline,
            'status',          NEW.status
        )
    );
END$$

DELIMITER ;


-- =============================================
-- MÓDULO: DEVOLUCIONES
-- =============================================

DELIMITER $$

-- ---------------------------------------------
-- Trigger 4: trg_after_return_insert (the most complex)
-- It executes AFTER a return is registered
-- Responsibilities:
--   1. Restores the copy to 'Disponible'
--   2. Calculates the number of days overdue by comparing 
--      the loan's return_date against its return_deadline
--   3. If there is a delay:
--      a. Calculates the fine ($25 per day overdue)
--      b. Inserts the record into the fines table with a
--         status of 'Pendiente'
--      c. Changes the loan to 'Con adeudo'
--      d. Blocks the user (active = 0)
--   4. If there is no delay:
--      a. Changes the loan to 'Finalizado'
--      b. If the loan originated from a booking, closes
--         the hold as 'Finalizado'
--   5. Notifies the next user on the waiting list for that
--      book by triggering their availability alert and 
--      changing their hold to 'Listo para entrega'
--   6. Logs the event
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_return_insert$$

CREATE TRIGGER trg_after_return_insert
AFTER INSERT ON returns
FOR EACH ROW
BEGIN
    DECLARE v_id_user         INT;
    DECLARE v_id_copy         INT;
    DECLARE v_id_booking      INT;
    DECLARE v_id_book         INT;
    DECLARE v_return_deadline DATE;
    DECLARE v_days_late       INT;
    DECLARE v_fine_amount     DECIMAL(5,2);
    DECLARE v_next_booking    INT;

    -- Obtain related loan details
    SELECT id_user, id_copy, id_booking, return_deadline
    INTO v_id_user, v_id_copy, v_id_booking, v_return_deadline
    FROM loans
    WHERE id_loan = NEW.id_loan;

    -- Find the book the copy belongs to
    -- (to find the waiting list for that book)
    SELECT id_book INTO v_id_book
    FROM copies
    WHERE id_copy = v_id_copy;

    -- 1. Restore copy
    UPDATE copies
    SET status = 'Disponible'
    WHERE id_copy = v_id_copy;

    -- 2. Calculate days of delay
    SET v_days_late = DATEDIFF(NEW.return_date, v_return_deadline);

    IF v_days_late > 0 THEN

        -- 3a. calculate fine
        SET v_fine_amount = v_days_late * 25.00;

        -- 3b. register fine
        INSERT INTO fines (id_return, fine_date, amount, status, payment_date)
        VALUES (NEW.id_return, NEW.return_date, v_fine_amount, 'Pendiente', NULL);

        -- 3c. Update loan status with 'Con adeudo'
        UPDATE loans
        SET status = 'Con adeudo'
        WHERE id_loan = NEW.id_loan;

        -- 3d. Blocks the user
        UPDATE users
        SET active = 0
        WHERE id_user = v_id_user;

    ELSE

        -- 4a. Return on time, close loan
        UPDATE loans
        SET status = 'Finalizado'
        WHERE id_loan = NEW.id_loan;

        -- 4b. If it came from a booking, close it
        IF v_id_booking IS NOT NULL THEN
            UPDATE bookings
            SET status = 'Finalizado'
            WHERE id_booking = v_id_booking;
        END IF;

    END IF;

    -- 5. Notify the next person on the waiting list for this book
    SELECT id_booking INTO v_next_booking
    FROM bookings
    WHERE id_book = v_id_book
        AND status = 'En Espera'
    ORDER BY booking_date ASC
    LIMIT 1;

    IF v_next_booking IS NOT NULL THEN
        UPDATE bookings
        SET status = 'Listo para entrega',
            availability_alert = 1
        WHERE id_booking = v_next_booking;
    END IF;

    -- 6. Audit
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, v_id_user),
        'returns',
        'Crear',
        NULL,
        JSON_OBJECT(
            'id_return',   NEW.id_return,
            'id_loan',     NEW.id_loan,
            'return_date', NEW.return_date,
            'days_late',   v_days_late,
            'notes',       NEW.notes
        )
    );
END$$

DELIMITER ;


-- =============================================
-- MÓDULO: RESERVAS
-- =============================================

DELIMITER $$

-- ---------------------------------------------
-- Trigger 5: trg_before_booking_insert
-- It runs BEFORE registering a reservation
-- Responsibilities:
--   1. Verifies that the user doesn't already have an 
--      active reservation for the same book. The following
--      statuses are considered active:
--          - 'En espera'         : reservation in queue
--          - 'Listo para entrega': reservation confirmed
--          - 'Entregado'         : book in the user's 
--            possession, loan still active
--      If a booking exists, it cancels the operation
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_before_booking_insert$$

CREATE TRIGGER trg_before_booking_insert
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
    DECLARE v_existing INT;

    SELECT COUNT(*) INTO v_existing
    FROM bookings
    WHERE id_user = NEW.id_user
        AND id_book = NEW.id_book
        AND status IN ('En Espera', 'Listo para entrega', 'Entregado');

    IF v_existing > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario ya tiene una reserva activa para este libro.';
    END IF;
END$$


-- ---------------------------------------------
-- Trigger 6: trg_after_booking_insert
-- It runs AFTER a reservation is registered
-- Responsibilities:
--   1. Registers the new booking in the logs
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_booking_insert$$

CREATE TRIGGER trg_after_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, NEW.id_user),
        'bookings',
        'Crear',
        NULL,
        JSON_OBJECT(
            'id_booking',         NEW.id_booking,
            'id_user',            NEW.id_user,
            'id_book',            NEW.id_book,
            'booking_date',       NEW.booking_date,
            'status',             NEW.status,
            'availability_alert', NEW.availability_alert
        )
    );
END$$


-- ---------------------------------------------
-- Trigger 7: trg_after_booking_update
-- It runs AFTER updating a booking
-- Responsibilities:
--   1. If the booking changes to 'Cancelado', it checks
--      if there are other users on the waiting list
--      for the same book. If there are, it notifies
--      the next user in the queue by changing their status to
--      'Listo para entrega' and setting their
--      availability_alert = 1.
--      NOTE: The notification when the book is returned
--      is also in trg_after_return_insert.
--      This trigger covers manual cancellation.
--   2. It logs the change.
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_booking_update$$

CREATE TRIGGER trg_after_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    DECLARE v_next_booking INT;

    -- 1. If the booking is cancelled, notify the next person on the list
    IF NEW.status = 'Cancelado' AND OLD.status != 'Cancelado' THEN

        SELECT id_booking INTO v_next_booking
        FROM bookings
        WHERE id_book = OLD.id_book
            AND status = 'En Espera'
        ORDER BY booking_date ASC
        LIMIT 1;

        IF v_next_booking IS NOT NULL THEN
            UPDATE bookings
            SET status = 'Listo para entrega',
                availability_alert = 1
            WHERE id_booking = v_next_booking;
        END IF;

    END IF;

    -- 2. Audit
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, NEW.id_user),
        'bookings',
        'Actualizar',
        JSON_OBJECT(
            'id_booking',         OLD.id_booking,
            'id_user',            OLD.id_user,
            'id_book',            OLD.id_book,
            'booking_date',       OLD.booking_date,
            'status',             OLD.status,
            'availability_alert', OLD.availability_alert
        ),
        JSON_OBJECT(
            'id_booking',         NEW.id_booking,
            'id_user',            NEW.id_user,
            'id_book',            NEW.id_book,
            'booking_date',       NEW.booking_date,
            'status',             NEW.status,
            'availability_alert', NEW.availability_alert
        )
    );
END$$

DELIMITER ;


-- =============================================
-- MÓDULO: MULTAS
-- =============================================

DELIMITER $$

-- ---------------------------------------------
-- Trigger 8: trg_after_fine_insert
-- It runs AFTER a fine is logged
-- Responsibilities:
--   1. Register the new fine
--     (The fine is created by trg_after_return_insert; 
--     this trigger only ensures that it is recorded in
--     the audit log as a separate and traceable event.)
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_fine_insert$$

CREATE TRIGGER trg_after_fine_insert
AFTER INSERT ON fines
FOR EACH ROW
BEGIN
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, 1),
        'fines',
        'Crear',
        NULL,
        JSON_OBJECT(
            'id_fine',   NEW.id_fine,
            'id_return', NEW.id_return,
            'fine_date', NEW.fine_date,
            'amount',    NEW.amount,
            'status',    NEW.status
        )
    );
END$$


-- ---------------------------------------------
-- Trigger 9: trg_after_fine_update
-- It runs AFTER updating a fine
-- Responsibilities:
--   1. If the fine changes to 'Pagada', it checks if 
--      the user has any other outstanding fines
--      If they don't, it automatically unlocks them (active = 1)
--   2. If the loan associated with the fine originated 
--      from a booking, it closes that booking as 'Finalizado',
--      completing the entire cycle: 
--      En Espera → Listo para entrega → Entregado → (devolución tardía) → Finalizado
--   3. It logs the change
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_fine_update$$

CREATE TRIGGER trg_after_fine_update
AFTER UPDATE ON fines
FOR EACH ROW
BEGIN
    DECLARE v_id_user       INT;
    DECLARE v_id_booking    INT;
    DECLARE v_pending_fines INT;

    -- Only act when marked as paid
    IF NEW.status = 'Pagada' AND OLD.status != 'Pagada' THEN

        -- Obtain user and related booking through the chain: 
        -- ends → returns → loans
        SELECT l.id_user, l.id_booking
        INTO v_id_user, v_id_booking
        FROM returns r
        JOIN loans l ON r.id_loan = l.id_loan
        WHERE r.id_return = NEW.id_return;

        -- 1. Check for remaining outstanding fines
        SELECT COUNT(*) INTO v_pending_fines
        FROM fines f
        JOIN returns r ON f.id_return = r.id_return
        JOIN loans l   ON r.id_loan   = l.id_loan
        WHERE l.id_user = v_id_user
            AND f.status  = 'Pendiente';

        -- If there are no outstanding fines, unlock user
        IF v_pending_fines = 0 THEN
            UPDATE users
            SET active = 1
            WHERE id_user = v_id_user;
        END IF;

        -- 2. Close the booking if the loan came from a booking
        IF v_id_booking IS NOT NULL THEN
            UPDATE bookings
            SET status = 'Finalizado'
            WHERE id_booking = v_id_booking
                AND status = 'Entregado';
        END IF;

    END IF;

    -- 3. Audit
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, 1),
        'fines',
        'Actualizar',
        JSON_OBJECT(
            'id_fine',      OLD.id_fine,
            'id_return',    OLD.id_return,
            'fine_date',    OLD.fine_date,
            'amount',       OLD.amount,
            'status',       OLD.status,
            'payment_date', OLD.payment_date
        ),
        JSON_OBJECT(
            'id_fine',      NEW.id_fine,
            'id_return',    NEW.id_return,
            'fine_date',    NEW.fine_date,
            'amount',       NEW.amount,
            'status',       NEW.status,
            'payment_date', NEW.payment_date
        )
    );
END$$

DELIMITER ;


-- =============================================
-- MÓDULO: AUDITORÍA (usuarios)
-- =============================================

DELIMITER $$

-- ---------------------------------------------
-- Trigger 10: trg_after_user_update
-- It runs AFTER a user is modified.
-- Responsibilities:
--   1. Logs any modifications to a user. 
--      For security reasons, the password
--      is not included in the log.
-- ---------------------------------------------

DROP TRIGGER IF EXISTS trg_after_user_update$$

CREATE TRIGGER trg_after_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    INSERT INTO logs (id_user, table_name, action, old_data, new_data)
    VALUES (
        COALESCE(@current_user_id, NEW.id_user),
        'users',
        'Actualizar',
        JSON_OBJECT(
            'id_user',      OLD.id_user,
            'name',         OLD.name,
            'last_name',    OLD.last_name,
            'email',        OLD.email,
            'id_user_type', OLD.id_user_type,
            'id_role',      OLD.id_role,
            'active',       OLD.active
        ),
        JSON_OBJECT(
            'id_user',      NEW.id_user,
            'name',         NEW.name,
            'last_name',    NEW.last_name,
            'email',        NEW.email,
            'id_user_type', NEW.id_user_type,
            'id_role',      NEW.id_role,
            'active',       NEW.active
        )
    );
END$$

DELIMITER ;