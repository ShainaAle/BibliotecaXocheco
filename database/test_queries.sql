-- =============================================
-- Data test
-- =============================================
-- INSTRUCTIONS:
--   Run AFTER:
--     1. library_database.sql
--     2. triggers.sql
--   Execute from terminal (on macOS):
--     /Applications/XAMPP/bin/mysql -u root -p mydb < test_queries.sql
-- =============================================
-- NOTES:
--   - roles & user_types just has their own base registers
--   - login_history & logs are omited because they're
--     generated automatically by the system
--   - Passwords are hashed with BCrypt equivalent:
--     password_hash('password123', PASSWORD_BCRYPT)
--   - @current_user_id is stablished with 0 to avoid errors
--     with audit triggers during data test insertion
-- =============================================

USE mydb;

SET FOREIGN_KEY_CHECKS = 0;
SET @current_user_id = 1; -- temporal value for audit triggers

-- =============================================
-- BASE CATALOGS (unique)
-- =============================================

-- System roles (3)
INSERT INTO roles (id_role, name) VALUES
(1, 'Administrador'),
(2, 'Bibliotecario'),
(3, 'Usuario');

-- User type (3)
INSERT INTO user_types (id_user_type, name) VALUES
(1, 'Docente'),
(2, 'Alumno'),
(3, 'Usuario Externo');

-- =============================================
-- PERMISSIONS AND ROLES_PERMISSIONS
-- =============================================

INSERT INTO permissions (id_permission, access, module) VALUES
(1,  'Crear',      'Usuarios'),
(2,  'Actualizar', 'Usuarios'),
(3,  'Consultar',   'Usuarios'),
(4,  'Crear',      'Préstamos'),
(5,  'Actualizar', 'Préstamos'),
(6,  'Consultar',   'Préstamos'),
(7,  'Crear',      'Libros'),
(8,  'Actualizar', 'Libros'),
(9,  'Eliminar',   'Libros'),
(10, 'Consultar',  'Libros'),
(11, 'Crear',      'Ejemplares'),
(12, 'Actualizar', 'Ejemplares'),
(13, 'Eliminar',   'Ejemplares'),
(14, 'Consultar',  'Ejemplares'),
(15, 'Crear',      'Editoriales'),
(16, 'Actualizar', 'Editoriales'),
(17, 'Eliminar',   'Editoriales'),
(18, 'Consultar',  'Editoriales'),
(19, 'Crear',      'Géneros'),
(20, 'Actualizar', 'Géneros'),
(21, 'Eliminar',   'Géneros'),
(22, 'Consultar',  'Géneros'),
(23, 'Crear',      'Autores'),
(24, 'Actualizar', 'Autores'),
(25, 'Eliminar',   'Autores'),
(26, 'Consultar',  'Autores'),
(27, 'Crear',      'Reservas'),
(28, 'Actualizar', 'Reservas'),
(29, 'Consultar',  'Reservas'),
(30, 'Crear',      'Tipo_usuario'),
(31, 'Actualizar', 'Tipo_usuario'),
(32, 'Eliminar',   'Tipo_usuario'),
(33, 'Consultar',  'Tipo_usuario'),
(34, 'Crear',      'Roles'),
(35, 'Actualizar', 'Roles'),
(36, 'Eliminar',   'Roles'),
(37, 'Consultar',  'Roles'),
(38, 'Crear',      'Permisos'),
(39, 'Actualizar', 'Permisos'),
(40, 'Eliminar',   'Permisos'),
(41, 'Consultar',  'Permisos'),
(42, 'Crear',      'Domicilio'),
(43, 'Actualizar', 'Domicilio'),
(44, 'Eliminar',   'Domicilio'),
(45, 'Consultar',  'Domicilio'),
(46, 'Crear',      'Devoluciones'),
(47, 'Actualizar', 'Devoluciones'),
(48, 'Consultar',  'Devoluciones'),
(49, 'Actualizar', 'Multas'),
(50, 'Consultar',  'Multas'),
(51, 'Consultar',  'Logs'),
(52, 'Consultar',  'Logins');

-- Admin has all permissions
INSERT INTO roles_permissions (id_role, id_permission) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(1, 5), (1, 6), (1, 7), (1, 8), 
(1, 9), (1, 10),(1, 11),(1, 12),
(1, 13),(1, 14),(1, 15),(1, 16),
(1, 17),(1, 18),(1, 19),(1, 20),
(1, 21),(1, 22),(1, 23),(1, 24),
(1, 25),(1, 26),(1, 27),(1, 28),
(1, 29),(1, 30),(1, 31),(1, 32),
(1, 33),(1, 34),(1, 35),(1, 36),
(1, 37),(1, 38),(1, 39),(1, 40),
(1, 41),(1, 42),(1, 43),(1, 44),
(1, 45),(1, 46),(1, 47),(1, 48),
(1, 49),(1, 50),(1, 51),(1, 52);

-- Librarian - managment  (they can do everything except delete)
INSERT INTO roles_permissions (id_role, id_permission) VALUES
(2, 1), (2, 2), (2, 3), (2, 4),
(2, 5), (2, 6), (2, 7), (2, 8), 
(2, 10),(2, 11),(2, 12),
(2, 14),(2, 15),(2, 16),
(2, 18),(2, 19),(2, 20),
(2, 22),(2, 23),(2, 24),
(2, 26),(2, 27),(2, 28),
(2, 29),(2, 30),(2, 31),
(2, 33),(2, 34),(2, 35),
(2, 37),(2, 38),(2, 39),
(2, 41),(2, 42),(2, 43),
(2, 45),(2, 46),(2, 47),(2, 48),
(2, 49),(2, 50),(2, 51),(2, 52);

-- User just can consult it's own loans, bookings, fines, the books/copies and create bookings
INSERT INTO roles_permissions (id_role, id_permission) VALUES
(3, 6), (3, 10), (3, 14), 
(3, 27), (3, 29), (3, 50);

-- =============================================
-- PUBLISHERS
-- =============================================

INSERT INTO publishers (id_publisher, name) VALUES
(1, 'Planeta'),
(2, 'Penguin Random House'),
(3, 'Fondo de Cultura Económica'),
(4, 'Alfaguara'),
(5, 'Porrúa');

-- =============================================
-- GENRES
-- =============================================

INSERT INTO genres (id_genre, name) VALUES
(1, 'Novela'),
(2, 'Ciencia Ficción'),
(3, 'Historia'),
(4, 'Matemáticas'),
(5, 'Programación');

-- =============================================
-- AUTHORS
-- =============================================

INSERT INTO authors (id_author, full_name) VALUES
(1, 'Gabriel García Márquez'),
(2, 'Julio Cortázar'),
(3, 'Isaac Asimov'),
(4, 'Donald Knuth'),
(5, 'Robert C. Martin');

-- =============================================
-- BOOKS
-- =============================================

INSERT INTO books (id_book, title, isbn, id_publisher, id_genre, synopsis) VALUES
(1, 'Cien años de soledad',    '9780307474728', 2, 1, 'La historia de la familia Buendía a lo largo de siete generaciones en el pueblo ficticio de Macondo.'),
(2, 'Rayuela',                 '9788437604572', 4, 1, 'Una novela experimental que puede leerse de múltiples formas, siguiendo la historia de Horacio Oliveira.'),
(3, 'Fundación',               '9780553293357', 2, 2, 'El matemático Hari Seldon predice la caída del Imperio Galáctico y busca preservar el conocimiento humano.'),
(4, 'El Arte de Programar',    '9780201896831', 2, 5, 'La obra más completa sobre algoritmos y programación fundamental escrita por Donald Knuth.'),
(5, 'Código Limpio',           '9780132350884', 2, 5, 'Guía de buenas prácticas para escribir código mantenible, legible y profesional.');

-- Relation book-authors
INSERT INTO book_authors (id_author, id_book) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

-- =============================================
-- ADDRESSES
-- =============================================

INSERT INTO addresses (id_address, street, ext_num, int_num, neighborhood, zip_code, city, state) VALUES
(1, 'Av. Tecnológico',    '1',   NULL, 'Centro',          '76000', 'Querétaro', 'Querétaro'),
(2, 'Calle Reforma',      '245', 'B',  'Jardines',        '76100', 'Querétaro', 'Querétaro'),
(3, 'Blvd. Bernardo Q.',  '88',  NULL, 'Milenio',         '76060', 'Querétaro', 'Querétaro'),
(4, 'Av. Universidad',    '500', NULL, 'Constituyentes',  '76170', 'Querétaro', 'Querétaro'),
(5, 'Calle Hidalgo',      '12',  '3',  'Centro Histórico','76000', 'Querétaro', 'Querétaro'),
(6, 'Paseo del Bosque',   '33',  NULL, 'Bosques',         '76140', 'Querétaro', 'Querétaro'),
(7, 'Av. Insurgentes',    '77',  NULL, 'Cimatario',       '76030', 'Querétaro', 'Querétaro');

-- =============================================
-- USERS
-- =============================================
-- Passwords: every hashed (with BCrypt) password is 
-- '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
-- In logIn.php use: password_verify($_POST['password'], $usuario['password'])
-- =============================================

INSERT INTO users (id_user, name, last_name, email, password, id_user_type, id_address, id_role, active) VALUES
-- Admin
(1, 'Carlos',    'Mendoza López',    'admin@xocheco.com',         'password123', 1, 1, 1, 1),
-- Librarian
(2, 'María',     'García Hernández', 'maria.garcia@xocheco.com',  'password123', 1, 2, 2, 1),
(3, 'Roberto',   'Sánchez Díaz',     'roberto.sanchez@xocheco.com','password123', 2, 3, 2, 1),
-- User type: Alumno
(4, 'Ana',       'Torres Ramos',     'ana.torres@alumnos.uaq.mx', 'password123', 2, 4, 3, 1),
(5, 'Luis',      'Pérez Vargas',     'luis.perez@alumnos.uaq.mx', 'password123', 2, 5, 3, 1),
-- User type: Docente
(6, 'Patricia',  'Ruiz Morales',     'patricia.ruiz@uaq.mx',      'password123', 1, 6, 3, 1),
-- User type: Usuario Externo
(7, 'Jorge',     'Castillo Núñez',   'jorge.castillo@gmail.com',  'password123', 3, 7, 3, 1);

-- =============================================
-- COPIES
-- Each book has 2-3 copies for testing purposes
-- availability, active loans & bookings
-- =============================================

INSERT INTO copies (id_copy, id_book, year, edition, code, location, status, notes) VALUES
-- Cien años de soledad
(1,  1, '2010', 'Primera edición',   'LIT-001-A', 'Estante A1', 'Disponible',    NULL),
(2,  1, '2015', 'Segunda edición',   'LIT-001-B', 'Estante A1', 'Disponible',    NULL),
(3,  1, '2020', 'Edición especial',  'LIT-001-C', 'Estante A1', 'Prestado',      NULL),
-- Rayuela
(4,  2, '2012', 'Primera edición',   'LIT-002-A', 'Estante A2', 'Disponible',    NULL),
(5,  2, '2018', 'Segunda edición',   'LIT-002-B', 'Estante A2', 'Prestado',      NULL),
-- Fundación
(6,  3, '2009', 'Primera edición',   'CF-001-A',  'Estante B1', 'Disponible',    NULL),
(7,  3, '2019', 'Edición revisada',  'CF-001-B',  'Estante B1', 'Disponible',    NULL),
-- El Arte de Programar
(8,  4, '2011', 'Tercera edición',   'INF-001-A', 'Estante C1', 'Disponible',    NULL),
(9,  4, '2021', 'Cuarta edición',    'INF-001-B', 'Estante C1', 'En reparación', 'Pasta despegada'),
-- Código Limpio
(10, 5, '2008', 'Primera edición',   'INF-002-A', 'Estante C2', 'Disponible',    NULL),
(11, 5, '2022', 'Segunda edición',   'INF-002-B', 'Estante C2', 'Prestado',      NULL);

-- =============================================
-- ACTIVE LOANS
-- They're inserted directly without going through triggers
-- to have total control over the initial status
-- =============================================

-- NOTE: return_deadline it's calculated manually here
-- because triggers don't run on test queries in the same
-- way as in production 

INSERT INTO loans (id_loan, id_user, id_booking, id_copy, start_date, return_deadline, status) VALUES
-- Luis (alumno) has 'Cien años de soledad' (code LIT-001-C, id_copie 3) — 'activo'
(1, 5, NULL, 3,  '2026-03-01', '2026-03-08', 'Activo'),
-- Ana (alumna) has 'Rayuela' (code LIT-002-B, id_copie 5) — 'activo'
(2, 4, NULL, 5,  '2026-03-03', '2026-03-10', 'Activo'),
-- Patricia (docente) has 'Código Limpio' (code INF-002-B, id_copie 11) — 'con adeudo' (for testing)
(3, 6, NULL, 11, '2026-02-10', '2026-02-24', 'Con adeudo');

-- Update borrowed loans status
-- (it's usually done by trigger, here it's manual)
UPDATE copies SET status = 'Prestado' WHERE id_copy IN (3, 5, 11);

-- =============================================
-- TEST RETURN
-- A finished loan just to show history
-- =============================================

INSERT INTO loans (id_loan, id_user, id_booking, id_copy, start_date, return_deadline, status) VALUES
(4, 4, NULL, 1, '2026-02-01', '2026-02-08', 'Finalizado');

INSERT INTO returns (id_return, id_loan, return_date, notes) VALUES
(1, 4, '2026-02-07', 'Devolución en buen estado');

-- =============================================
-- FINE TEST
-- Patricia has an outstanding fine for loan 3 
-- (due on February 24th)
-- =============================================

INSERT INTO fines (id_fine, id_loan, id_return, fine_date, amount, status, payment_date) VALUES
(1, 3, NULL, '2026-02-25', 225.00, 'Pendiente', NULL);
-- 9 days late by $25 = $225

-- Block Patricia for the fine
UPDATE users SET active = 0 WHERE id_user = 6;

-- =============================================
-- TEST BOOKINGS
-- =============================================

INSERT INTO bookings (id_booking, id_user, id_book, booking_date, availability_alert, status) VALUES
-- Jorge (externo) booked 'Fundación' — 'en espera'
(1, 7, 3, '2026-03-05', 0, 'En Espera'),
-- Luis has a completed booking in his history
(2, 5, 2, '2026-02-28', 0, 'Finalizado');

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- QUICK CHECK
-- Execute the next queries just to confirm 
-- data was inserted correctly 
-- =============================================

SELECT 'publishers'  AS tabla, COUNT(*) AS registros FROM publishers  UNION ALL
SELECT 'genres',               COUNT(*)               FROM genres       UNION ALL
SELECT 'authors',              COUNT(*)               FROM authors      UNION ALL
SELECT 'books',                COUNT(*)               FROM books        UNION ALL
SELECT 'book_authors',         COUNT(*)               FROM book_authors UNION ALL
SELECT 'user_types',           COUNT(*)               FROM user_types   UNION ALL
SELECT 'roles',                COUNT(*)               FROM roles        UNION ALL
SELECT 'permissions',          COUNT(*)               FROM permissions  UNION ALL
SELECT 'roles_permissions',    COUNT(*)               FROM roles_permissions UNION ALL
SELECT 'addresses',            COUNT(*)               FROM addresses    UNION ALL
SELECT 'users',                COUNT(*)               FROM users        UNION ALL
SELECT 'copies',               COUNT(*)               FROM copies       UNION ALL
SELECT 'loans',                COUNT(*)               FROM loans        UNION ALL
SELECT 'returns',              COUNT(*)               FROM returns      UNION ALL
SELECT 'fines',                COUNT(*)               FROM fines        UNION ALL
SELECT 'bookings',             COUNT(*)               FROM bookings;