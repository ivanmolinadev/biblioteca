# Sistema de Biblioteca y Préstamos

## Especificaciones del Proyecto

- **Tecnologías**: PHP, HTML, CSS, SQL, XAMPP
- **Objetivo**: Sistema web para gestionar catálogo de libros, usuarios lectores, y ciclo de préstamo/devolución
- **Autenticación**: Login con password_hash/password_verify (PDO)
- **Roles**: Admin (gestiona todo) y Usuario (lector: consulta catálogo y sus préstamos)
- **Módulos CRUD**: Libros, Autores, Categorías, Lectores, Préstamos, Devoluciones/Multas
- **Reglas de negocio**: Control de disponibilidad, límites de préstamos, vencimientos, multas
- **Seguridad**: PDO con consultas preparadas, CSRF tokens, sanitización XSS
- **Front-end**: HTML semántico, CSS responsive, validación JS

## Estado del Proyecto

✅ Proyecto completamente implementado
✅ Base de datos diseñada e inicializada
✅ Sistema de autenticación y roles
✅ Módulos CRUD principales
✅ Reglas de negocio implementadas
✅ Seguridad completa (PDO, CSRF, XSS)
✅ Front-end responsive con Bootstrap 5
✅ Documentación completa

## Estructura Creada

- **config/**: Configuración del sistema, DB, CSRF, inicialización
- **public/**: Páginas principales (login, dashboard, módulos CRUD)
- **includes/**: Header y footer compartidos
- **assets/**: CSS y JavaScript personalizados
- **sql/**: Esquema de DB y datos de prueba
- **test_basic.php**: Script de verificación del sistema

## Instrucciones de Instalación

1. Copiar a `C:\xampp\htdocs\biblioteca`
2. Importar `sql/schema.sql` y `sql/seed_data.sql` en phpMyAdmin
3. Acceder a `http://localhost/biblioteca/public/`
4. Login: admin/password

## Usuarios de Prueba

- **admin** / **password** (Administrador)
- **usuario1** / **password** (Lector)

El sistema está listo para usar y cumple con todos los requerimientos especificados.
