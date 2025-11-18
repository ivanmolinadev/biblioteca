-- Datos de prueba para el Sistema de Biblioteca y Préstamos
USE biblioteca_db;

-- Insertar usuarios del sistema
INSERT INTO usuarios (username, email, password_hash, rol) VALUES
('admin', 'admin@biblioteca.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- password: password
('bibliotecario1', 'biblio1@biblioteca.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- password: password
('usuario1', 'usuario1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'), -- password: password
('usuario2', 'usuario2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'), -- password: password
('usuario3', 'usuario3@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'), -- password: password
('juan_perez', 'juan.perez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
('maria_gonzalez', 'maria.gonzalez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
('carlos_rodriguez', 'carlos.rodriguez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
('ana_martinez', 'ana.martinez@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario'),
('luis_garcia', 'luis.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario');

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Novela', 'Obras de ficción narrativa'),
('Ciencia Ficción', 'Literatura de ciencia ficción y fantasía'),
('Historia', 'Libros de historia y biografías'),
('Ciencias', 'Libros de ciencias naturales y exactas'),
('Tecnología', 'Libros de informática y tecnología'),
('Filosofía', 'Obras de filosofía y pensamiento'),
('Arte', 'Libros de arte, música y cultura'),
('Educación', 'Libros educativos y didácticos'),
('Biografías', 'Biografías y autobiografías'),
('Salud', 'Libros de medicina y salud'),
('Economía', 'Libros de economía y finanzas'),
('Derecho', 'Libros jurídicos y legales'),
('Psicología', 'Libros de psicología y desarrollo personal'),
('Infantil', 'Literatura infantil y juvenil'),
('Poesía', 'Obras poéticas y líricas');

-- Insertar autores
INSERT INTO autores (nombre, apellidos, biografia, fecha_nacimiento, nacionalidad) VALUES
('Gabriel', 'García Márquez', 'Escritor colombiano, Premio Nobel de Literatura 1982', '1927-03-06', 'Colombiano'),
('Isabel', 'Allende', 'Escritora chilena de renombre internacional', '1942-08-02', 'Chilena'),
('Mario', 'Vargas Llosa', 'Escritor peruano, Premio Nobel de Literatura 2010', '1936-03-28', 'Peruano'),
('Jorge Luis', 'Borges', 'Escritor argentino, maestro del cuento', '1899-08-24', 'Argentino'),
('Octavio', 'Paz', 'Poeta y ensayista mexicano, Premio Nobel 1990', '1914-03-31', 'Mexicano'),
('Pablo', 'Neruda', 'Poeta chileno, Premio Nobel de Literatura 1971', '1904-07-12', 'Chileno'),
('Julio', 'Cortázar', 'Escritor argentino, maestro del relato fantástico', '1914-08-26', 'Argentino'),
('Carlos', 'Fuentes', 'Escritor mexicano, una de las figuras principales del boom latinoamericano', '1928-11-11', 'Mexicano'),
('Laura', 'Esquivel', 'Escritora mexicana conocida por Como agua para chocolate', '1950-09-30', 'Mexicana'),
('Roberto', 'Bolaño', 'Escritor chileno, autor de Los detectives salvajes', '1953-04-28', 'Chileno'),
('Stephen', 'King', 'Autor estadounidense de novelas de terror', '1947-09-21', 'Estadounidense'),
('J.K.', 'Rowling', 'Autora británica de la saga Harry Potter', '1965-07-31', 'Británica'),
('George', 'Orwell', 'Escritor británico, autor de 1984', '1903-06-25', 'Británico'),
('Agatha', 'Christie', 'Escritora británica de novelas policíacas', '1890-09-15', 'Británico'),
('Miguel de', 'Cervantes', 'Escritor español, autor de Don Quijote', '1547-09-29', 'Español');

-- Insertar lectores
INSERT INTO lectores (usuario_id, nombre, apellidos, dni, telefono, direccion, fecha_nacimiento) VALUES
(3, 'Juan Carlos', 'Pérez López', '12345678A', '555-0101', 'Calle Principal 123, Ciudad', '1985-05-15'),
(4, 'María Elena', 'González Ruiz', '23456789B', '555-0102', 'Avenida Libertad 456, Ciudad', '1990-08-22'),
(5, 'Carlos Alberto', 'Rodríguez Sánchez', '34567890C', '555-0103', 'Plaza Mayor 789, Ciudad', '1988-12-10'),
(6, 'Juan Antonio', 'Pérez Martínez', '45678901D', '555-0104', 'Calle del Sol 321, Ciudad', '1992-03-18'),
(7, 'María José', 'González Fernández', '56789012E', '555-0105', 'Avenida del Mar 654, Ciudad', '1987-07-25'),
(8, 'Carlos Eduardo', 'Rodríguez García', '67890123F', '555-0106', 'Calle Luna 987, Ciudad', '1991-11-08'),
(9, 'Ana Isabel', 'Martínez López', '78901234G', '555-0107', 'Plaza de la Paz 147, Ciudad', '1989-04-12'),
(10, 'Luis Miguel', 'García Pérez', '89012345H', '555-0108', 'Calle Estrella 258, Ciudad', '1986-09-30'),
(NULL, 'Carmen Rosa', 'Jiménez Morales', '90123456I', '555-0109', 'Avenida Norte 369, Ciudad', '1993-01-14'),
(NULL, 'Francisco Javier', 'Torres Ruiz', '01234567J', '555-0110', 'Calle Sur 741, Ciudad', '1984-06-03'),
(NULL, 'Patricia Andrea', 'Vargas Castro', '11223344K', '555-0111', 'Plaza Central 852, Ciudad', '1995-10-17'),
(NULL, 'Miguel Ángel', 'Herrera Díaz', '22334455L', '555-0112', 'Calle Oriente 963, Ciudad', '1983-02-28'),
(NULL, 'Laura Beatriz', 'Mendoza Silva', '33445566M', '555-0113', 'Avenida Poniente 159, Ciudad', '1990-12-05'),
(NULL, 'Diego Alejandro', 'Ramírez Torres', '44556677N', '555-0114', 'Calle Jardín 357, Ciudad', '1994-08-19'),
(NULL, 'Sofía Valentina', 'Moreno Gutiérrez', '55667788O', '555-0115', 'Plaza Verde 486, Ciudad', '1991-05-07');

-- Insertar libros
INSERT INTO libros (isbn, titulo, subtitulo, año_publicacion, editorial, numero_paginas, categoria_id, copias_totales, copias_disponibles, ubicacion) VALUES
('978-84-376-0494-7', 'Cien años de soledad', NULL, 1967, 'Sudamericana', 471, 1, 3, 3, 'Estante A1'),
('978-84-204-8244-7', 'La casa de los espíritus', NULL, 1982, 'Sudamericana', 433, 1, 2, 2, 'Estante A2'),
('978-84-204-6865-6', 'La ciudad y los perros', NULL, 1963, 'Seix Barral', 413, 1, 2, 2, 'Estante A3'),
('978-84-376-0495-4', 'El laberinto de la soledad', NULL, 1950, 'Fondo de Cultura Económica', 352, 6, 2, 2, 'Estante F1'),
('978-84-376-0496-1', 'Ficciones', NULL, 1944, 'Emecé', 254, 1, 3, 3, 'Estante A4'),
('978-84-376-0497-8', 'Veinte poemas de amor y una canción desesperada', NULL, 1924, 'Nascimento', 132, 15, 4, 4, 'Estante P1'),
('978-84-376-0498-5', 'Rayuela', NULL, 1963, 'Sudamericana', 635, 1, 2, 2, 'Estante A5'),
('978-84-376-0499-2', 'La muerte de Artemio Cruz', NULL, 1962, 'Fondo de Cultura Económica', 315, 1, 2, 2, 'Estante A6'),
('978-84-376-0500-5', 'Como agua para chocolate', NULL, 1989, 'Planeta', 246, 1, 3, 3, 'Estante A7'),
('978-84-376-0501-2', 'Los detectives salvajes', NULL, 1998, 'Anagrama', 669, 1, 2, 2, 'Estante A8'),
('978-0-7432-7356-5', 'IT (Eso)', NULL, 1986, 'Viking Press', 1138, 2, 2, 2, 'Estante B1'),
('978-0-7475-3269-9', 'Harry Potter y la piedra filosofal', NULL, 1997, 'Bloomsbury', 223, 14, 5, 5, 'Estante I1'),
('978-0-452-28423-4', '1984', NULL, 1949, 'Secker & Warburg', 328, 2, 3, 3, 'Estante B2'),
('978-0-00-651208-8', 'Asesinato en el Orient Express', NULL, 1934, 'Collins Crime Club', 256, 1, 2, 2, 'Estante A9'),
('978-84-376-0502-9', 'Don Quijote de la Mancha', 'Tomo I', 1605, 'Francisco de Robles', 863, 1, 3, 3, 'Estante C1'),
('978-84-376-0503-6', 'El Alquimista', NULL, 1988, 'Planeta', 163, 6, 4, 4, 'Estante F2'),
('978-84-376-0504-3', 'Crónica de una muerte anunciada', NULL, 1981, 'La Oveja Negra', 122, 1, 2, 2, 'Estante A10'),
('978-84-376-0505-0', 'La tregua', NULL, 1960, 'Alfa', 208, 1, 2, 2, 'Estante A11'),
('978-84-376-0506-7', 'Pedro Páramo', NULL, 1955, 'Fondo de Cultura Económica', 124, 1, 3, 3, 'Estante A12'),
('978-84-376-0507-4', 'El túnel', NULL, 1948, 'Sur', 158, 1, 2, 2, 'Estante A13');

-- Insertar relaciones libro-autor
INSERT INTO libro_autores (libro_id, autor_id) VALUES
(1, 1), (2, 2), (3, 3), (4, 5), (5, 4), (6, 6), (7, 7), (8, 8), (9, 9), (10, 10),
(11, 11), (12, 12), (13, 13), (14, 14), (15, 15), (16, 1), (17, 1), (18, 3), (19, 8), (20, 4);

-- Insertar algunos préstamos de ejemplo (algunos activos, algunos atrasados)
INSERT INTO prestamos (lector_id, libro_id, fecha_prestamo, fecha_vencimiento, estado, usuario_registro_id) VALUES
(1, 1, '2024-10-15', '2024-10-29', 'activo', 1),
(2, 3, '2024-10-20', '2024-11-03', 'activo', 1),
(3, 5, '2024-09-15', '2024-09-29', 'atrasado', 1),
(4, 7, '2024-11-01', '2024-11-15', 'activo', 2),
(5, 9, '2024-10-25', '2024-11-08', 'activo', 1),
(1, 11, '2024-09-01', '2024-09-15', 'atrasado', 1),
(6, 12, '2024-11-05', '2024-11-19', 'activo', 2),
(7, 14, '2024-10-30', '2024-11-13', 'activo', 1),
(8, 16, '2024-08-15', '2024-08-29', 'atrasado', 1),
(2, 18, '2024-11-10', '2024-11-24', 'activo', 2);

-- Insertar algunas devoluciones con multas
INSERT INTO devoluciones (prestamo_id, fecha_devolucion, usuario_registro_id) VALUES
(3, '2024-10-15', 1), -- 16 días de atraso
(6, '2024-10-01', 1), -- 16 días de atraso  
(9, '2024-09-20', 1); -- 22 días de atraso

-- Actualizar copias disponibles por los préstamos activos
UPDATE libros SET copias_disponibles = copias_disponibles - 1 WHERE id IN (1, 3, 7, 9, 12, 14, 18);

-- Actualizar estado de préstamos atrasados
UPDATE prestamos SET estado = 'atrasado' WHERE fecha_vencimiento < CURDATE() AND estado = 'activo';