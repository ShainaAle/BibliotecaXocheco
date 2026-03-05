USE mydb;

DELIMITER $$

DROP EVENT IF EXISTS evt_daily_fines$$

CREATE EVENT evt_daily_fines
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DECLARE done            INT DEFAULT 0;
    DECLARE v_id_loan       INT;
    DECLARE v_id_user       INT;
    DECLARE v_existing_fine INT;

    -- Cursor that iterates through all outstanding loans
    DECLARE cur_overdue CURSOR FOR
        SELECT id_loan, id_user
        FROM loans
        WHERE status IN ('Activo', 'Con adeudo')
            AND return_deadline < CURDATE();

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur_overdue;

    loan_loop: LOOP
        FETCH cur_overdue INTO v_id_loan, v_id_user;
        IF done = 1 THEN
            LEAVE loan_loop;
        END IF;

        -- Is there already an outstanding fine for this loan?
        SELECT id_fine INTO v_existing_fine
        FROM fines
        WHERE id_loan = v_id_loan
            AND status = 'Pendiente'
        LIMIT 1;

        IF v_existing_fine IS NOT NULL THEN
            -- It exists, increment $25
            UPDATE fines
            SET amount = amount + 25.00
            WHERE id_fine = v_existing_fine;
        ELSE
            -- No exist, create the fine and block the user
            INSERT INTO fines (id_loan, id_return, fine_date, amount, status, payment_date)
            VALUES (v_id_loan, NULL, CURDATE(), 25.00, 'Pendiente', NULL);

            UPDATE users
            SET active = 0
            WHERE id_user = v_id_user;

            UPDATE loans
            SET status = 'Con adeudo'
            WHERE id_loan = v_id_loan;
        END IF;

    END LOOP;

    CLOSE cur_overdue;
END$$

DELIMITER ;