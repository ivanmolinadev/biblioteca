# Sistema de Biblioteca y Préstamos

Un sistema web completo desarrollado en PHP para la gestión de bibliotecas, catálogo de libros, usuarios lectores y el ciclo completo de préstamos y devoluciones con control de multas y vencimientos.

## 🎯 Características Principales

### 📚 Gestión de Catálogo

- **Libros**: Registro completo con ISBN, título, autores, categoría, editorial, año
- **Autores**: Base de datos de autores con información biográfica
- **Categorías**: Clasificación y organización del catálogo
- **Control de inventario**: Gestión de copias totales y disponibles

### 👥 Gestión de Usuarios

- **Lectores**: Registro de usuarios con información personal y límites de préstamo
- **Usuarios del sistema**: Administradores y bibliotecarios
- **Autenticación segura**: Login con encriptación de contraseñas
- **Control de roles**: Admin (gestión completa) y Usuario (consulta personal)

### 📋 Gestión de Préstamos

- **Préstamos**: Sistema completo de registro y seguimiento
- **Devoluciones**: Control de fechas y cálculo automático de multas
- **Vencimientos**: Alertas y notificaciones de próximos vencimientos
- **Multas**: Cálculo automático por días de atraso
- **Disponibilidad**: Control automático de copias disponibles

### 🔒 Seguridad

- **PDO**: Consultas preparadas para prevenir inyección SQL
- **CSRF Protection**: Tokens de seguridad en formularios
- **XSS Protection**: Sanitización de entradas y salidas
- **Sesiones seguras**: Timeout automático y regeneración de IDs

## 🚀 Tecnologías Utilizadas

- **Backend**: PHP 8.0+
- **Base de Datos**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Bootstrap 5
- **JavaScript**: ES6+ (Vanilla JS)
- **Servidor**: XAMPP (Apache + MySQL)

## 📦 Instalación

### Requisitos Previos

- XAMPP (PHP 8.0+, MySQL, Apache)
- Navegador web moderno
- Editor de código (recomendado: VS Code)

### Pasos de Instalación

1. **Descargar e instalar XAMPP**

   ```bash
   # Descargar desde: https://www.apachefriends.org/
   # Instalar y ejecutar Apache y MySQL
   ```

2. **Clonar el proyecto**

   ```bash
   # Copiar la carpeta del proyecto a:
   C:\xampp\htdocs\biblioteca
   ```

3. **Configurar la base de datos**

   ```bash
   # Abrir phpMyAdmin en: http://localhost/phpmyadmin
   # Crear nueva base de datos: biblioteca_db
   # Importar el archivo: sql/schema.sql
   # Importar los datos de prueba: sql/seed_data.sql
   ```

4. **Configurar la aplicación**

   ```php
   // Editar config/db.php si es necesario
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'biblioteca_db');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Cambiar si tienes contraseña en MySQL
   ```

5. **Acceder al sistema**
   ```
   URL: http://localhost/biblioteca/public/
   ```

## 👤 Usuarios de Prueba

El sistema viene con usuarios predefinidos para pruebas:

| Usuario          | Contraseña | Rol     | Descripción                          |
| ---------------- | ---------- | ------- | ------------------------------------ |
| `admin`          | `password` | Admin   | Acceso completo al sistema           |
| `bibliotecario1` | `password` | Admin   | Bibliotecario con permisos completos |
| `usuario1`       | `password` | Usuario | Lector con acceso limitado           |
| `juan_perez`     | `password` | Usuario | Lector de ejemplo                    |

## 📊 Estructura del Proyecto

```
biblioteca/
├── .github/
│   └── copilot-instructions.md
├── config/
│   ├── config.php          # Configuración general
│   ├── db.php              # Configuración de base de datos
│   ├── csrf.php            # Protección CSRF
│   └── init.php            # Inicialización del sistema
├── public/
│   ├── index.php           # Página de inicio
│   ├── login.php           # Página de login
│   ├── logout.php          # Cerrar sesión
│   ├── dashboard.php       # Panel principal
│   ├── libros.php          # Gestión de libros
│   ├── autores.php         # Gestión de autores
│   ├── categorias.php      # Gestión de categorías
│   ├── lectores.php        # Gestión de lectores
│   ├── prestamos.php       # Gestión de préstamos
│   ├── devoluciones.php    # Gestión de devoluciones
│   └── ...
├── includes/
│   ├── header.php          # Encabezado común
│   └── footer.php          # Pie de página común
├── assets/
│   ├── css/
│   │   └── style.css       # Estilos personalizados
│   └── js/
│       └── main.js         # JavaScript principal
├── sql/
│   ├── schema.sql          # Estructura de la base de datos
│   └── seed_data.sql       # Datos de prueba
└── README.md
```

## 🎮 Uso del Sistema

### Para Administradores

1. **Dashboard Principal**

   - Vista general de estadísticas
   - Préstamos próximos a vencer
   - Préstamos atrasados
   - Actividad reciente

2. **Gestión de Catálogo**

   - Agregar, editar y eliminar libros
   - Gestionar autores y categorías
   - Control de inventario (copias)

3. **Gestión de Usuarios**

   - Registro de nuevos lectores
   - Gestión de usuarios del sistema
   - Control de permisos y roles

4. **Gestión de Préstamos**
   - Registrar nuevos préstamos
   - Procesar devoluciones
   - Gestionar multas y bloqueos
   - Generar reportes

### Para Usuarios (Lectores)

1. **Catálogo**

   - Búsqueda y filtrado de libros
   - Visualización de disponibilidad
   - Detalles completos de cada libro

2. **Mis Préstamos**
   - Ver préstamos activos
   - Historial de préstamos
   - Estado de multas

## 🔧 Configuración Avanzada

### Parámetros del Sistema

Edita la tabla `configuracion` en la base de datos o usa la interfaz de administración:

```sql
-- Días de préstamo por defecto
UPDATE configuracion SET valor = '21' WHERE clave = 'dias_prestamo_defecto';

-- Límite de préstamos por lector
UPDATE configuracion SET valor = '5' WHERE clave = 'limite_prestamos_defecto';

-- Tarifa de multa diaria
UPDATE configuracion SET valor = '0.50' WHERE clave = 'tarifa_multa_diaria';

-- Límite de multa para bloqueo
UPDATE configuracion SET valor = '15.00' WHERE clave = 'limite_multa_bloqueo';
```

### Personalización de Diseño

1. **Colores y Temas**

   ```css
   /* Editar assets/css/style.css */
   :root {
     --primary-color: #tu-color-principal;
     --secondary-color: #tu-color-secundario;
   }
   ```

2. **Logo y Nombre**
   ```php
   // Editar config/config.php
   define('APP_NAME', 'Tu Biblioteca');
   ```

## 📈 Funcionalidades Avanzadas

### Reglas de Negocio Implementadas

1. **Control de Disponibilidad**

   - Verificación automática antes de préstamos
   - Actualización en tiempo real del inventario
   - Bloqueo de préstamos cuando no hay copias

2. **Gestión de Vencimientos**

   - Cálculo automático de fechas de vencimiento
   - Cambio automático de estado a "atrasado"
   - Alertas en dashboard para próximos vencimientos

3. **Sistema de Multas**

   - Cálculo automático por días de atraso
   - Acumulación de multas por lector
   - Bloqueo automático por mora

4. **Límites de Préstamo**
   - Control de número máximo de préstamos activos
   - Verificación de bloqueos por multas
   - Validación antes de nuevos préstamos

### Seguridad Implementada

1. **Autenticación**

   - Contraseñas encriptadas con `password_hash()`
   - Validación con `password_verify()`
   - Regeneración de ID de sesión

2. **Autorización**

   - Control de acceso por roles
   - Verificación en cada página protegida
   - Separación de funcionalidades por rol

3. **Protección de Datos**
   - Consultas preparadas (PDO)
   - Sanitización de entrada y salida
   - Tokens CSRF en formularios críticos
   - Timeout automático de sesiones

## 🐛 Resolución de Problemas

### Problemas Comunes

1. **Error de conexión a la base de datos**

   ```
   Verificar:
   - XAMPP MySQL está ejecutándose
   - Credenciales en config/db.php
   - Base de datos existe
   ```

2. **Página en blanco**

   ```php
   // Activar errores para desarrollo
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. **Problemas de sesión**
   ```
   Verificar:
   - Permisos de carpeta temporal
   - Configuración de PHP
   - Cookies habilitadas en navegador
   ```

### Logs de Error

```php
// Los errores se registran en:
// - Logs de Apache (xampp/apache/logs/error.log)
// - Logs personalizados (usar error_log() en PHP)
```

## 📊 Base de Datos

### Tablas Principales

- `usuarios` - Credenciales y roles del sistema
- `lectores` - Información de los lectores de la biblioteca
- `libros` - Catálogo de libros
- `autores` - Base de datos de autores
- `categorias` - Clasificación de libros
- `prestamos` - Registro de préstamos
- `devoluciones` - Registro de devoluciones y multas
- `configuracion` - Parámetros del sistema

### Relaciones

- Libros ↔ Autores (muchos a muchos)
- Libros → Categorías (uno a muchos)
- Préstamos → Libros/Lectores (muchos a uno)
- Devoluciones → Préstamos (uno a uno)

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto es de código abierto y está disponible bajo la [Licencia MIT](LICENSE).

## 📞 Soporte

Para soporte técnico o preguntas:

- **Email**: soporte@biblioteca-sistema.com
- **Documentación**: Consultar este README
- **Issues**: Usar el sistema de issues de GitHub

## 🚀 Roadmap

### Versión 1.1 (Próxima)

- [ ] API REST para integraciones
- [ ] Sistema de reservas
- [ ] Notificaciones por email
- [ ] Reportes avanzados en PDF
- [ ] Código de barras para libros

### Versión 1.2 (Futuro)

- [ ] Aplicación móvil
- [ ] Sistema de recomendaciones
- [ ] Integración con bibliotecas externas
- [ ] Multi-biblioteca
- [ ] Sistema de multas online

---

## ⚡ Inicio Rápido

```bash
# 1. Descargar XAMPP e instalar
# 2. Copiar proyecto a C:\xampp\htdocs\biblioteca
# 3. Importar sql/schema.sql y sql/seed_data.sql en phpMyAdmin
# 4. Acceder a http://localhost/biblioteca/public/
# 5. Login: admin / password
```

¡Listo para usar! 🎉
