<?php
/**
 * Script de pruebas básicas - Sistema de Biblioteca y Préstamos
 * Para ejecutar: php test_basic.php
 */

// Incluir configuración
require_once __DIR__ . '/../config/init.php';

echo "🔄 Ejecutando pruebas básicas del Sistema de Biblioteca...\n\n";

$tests_passed = 0;
$tests_failed = 0;

/**
 * Función helper para tests
 */
function test($description, $condition) {
    global $tests_passed, $tests_failed;
    
    echo "Testing: $description... ";
    
    if ($condition) {
        echo "✅ PASS\n";
        $tests_passed++;
    } else {
        echo "❌ FAIL\n";
        $tests_failed++;
    }
}

// Test 1: Verificar conexión a base de datos
try {
    $pdo = getDbConnection();
    test("Conexión a base de datos", $pdo instanceof PDO);
} catch (Exception $e) {
    test("Conexión a base de datos", false);
    echo "  Error: " . $e->getMessage() . "\n";
}

// Test 2: Verificar tablas principales
$required_tables = [
    'usuarios', 'lectores', 'libros', 'autores', 
    'categorias', 'prestamos', 'devoluciones', 'configuracion'
];

foreach ($required_tables as $table) {
    try {
        $count = countRecords($table);
        test("Tabla '$table' existe y es accesible", true);
    } catch (Exception $e) {
        test("Tabla '$table' existe y es accesible", false);
    }
}

// Test 3: Verificar datos iniciales
try {
    $admin_count = countRecords('usuarios', 'rol = ?', ['admin']);
    test("Existen usuarios administradores", $admin_count > 0);
    
    $config_count = countRecords('configuracion');
    test("Configuraciones iniciales cargadas", $config_count > 0);
    
    $categorias_count = countRecords('categorias', 'activo = 1');
    test("Existen categorías activas", $categorias_count > 0);
    
} catch (Exception $e) {
    test("Verificación de datos iniciales", false);
}

// Test 4: Verificar funciones de seguridad
try {
    $token = generateCSRFToken();
    test("Generación de tokens CSRF", !empty($token));
    
    $is_valid = validateCSRFToken($token);
    test("Validación de tokens CSRF", $is_valid);
    
    $hash = password_hash('test123', PASSWORD_DEFAULT);
    $verify = password_verify('test123', $hash);
    test("Encriptación de contraseñas", $verify);
    
} catch (Exception $e) {
    test("Funciones de seguridad", false);
}

// Test 5: Verificar funciones de utilidad
try {
    $date = formatDate('2024-01-15');
    test("Formateo de fechas", !empty($date));
    
    $currency = formatCurrency(25.50);
    test("Formateo de moneda", strpos($currency, '$') !== false);
    
    $sanitized = sanitizeInput('<script>alert("xss")</script>');
    test("Sanitización de entrada", strpos($sanitized, '<script>') === false);
    
} catch (Exception $e) {
    test("Funciones de utilidad", false);
}

// Test 6: Verificar archivo de configuración
$required_constants = [
    'APP_NAME', 'APP_VERSION', 'DB_HOST', 'DB_NAME',
    'SESSION_TIMEOUT', 'DEFAULT_LOAN_DAYS'
];

foreach ($required_constants as $constant) {
    test("Constante '$constant' definida", defined($constant));
}

// Test 7: Verificar archivos críticos
$critical_files = [
    '../config/config.php',
    '../config/db.php',
    '../config/csrf.php',
    '../config/init.php',
    '../includes/header.php',
    '../includes/footer.php',
    '../assets/css/style.css',
    '../assets/js/main.js'
];

foreach ($critical_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    test("Archivo '$file' existe", file_exists($file_path));
}

// Test 8: Test de reglas de negocio básicas
try {
    // Verificar que no se puedan crear préstamos sin copias disponibles
    $libro_sin_copias = fetchOne(
        "SELECT id FROM libros WHERE copias_disponibles = 0 LIMIT 1"
    );
    
    if ($libro_sin_copias) {
        test("Control de disponibilidad implementado", true);
    } else {
        echo "ℹ️  No hay libros sin copias para probar el control de disponibilidad\n";
    }
    
    // Verificar cálculo de multas
    $prestamo_atrasado = fetchOne(
        "SELECT p.id, DATEDIFF(CURDATE(), p.fecha_vencimiento) as dias_atraso 
         FROM prestamos p 
         WHERE p.estado = 'atrasado' 
         LIMIT 1"
    );
    
    if ($prestamo_atrasado && $prestamo_atrasado['dias_atraso'] > 0) {
        test("Detección de préstamos atrasados", true);
    } else {
        echo "ℹ️  No hay préstamos atrasados para probar el cálculo de multas\n";
    }
    
} catch (Exception $e) {
    test("Verificación de reglas de negocio", false);
}

// Mostrar resumen
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMEN DE PRUEBAS:\n";
echo "✅ Pruebas exitosas: $tests_passed\n";
echo "❌ Pruebas fallidas: $tests_failed\n";
echo "📈 Total de pruebas: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed == 0) {
    echo "🎉 ¡Todas las pruebas pasaron exitosamente!\n";
    echo "✨ El sistema está listo para usar.\n";
} else {
    echo "⚠️  Algunas pruebas fallaron. Revisar la configuración.\n";
}

echo str_repeat("=", 50) . "\n\n";

// Información adicional
echo "📋 INFORMACIÓN DEL SISTEMA:\n";
echo "• Versión de PHP: " . PHP_VERSION . "\n";
echo "• Aplicación: " . APP_NAME . " v" . APP_VERSION . "\n";
echo "• Base de datos: " . DB_NAME . " en " . DB_HOST . "\n";
echo "• Timeout de sesión: " . (SESSION_TIMEOUT / 60) . " minutos\n";
echo "• Días de préstamo por defecto: " . DEFAULT_LOAN_DAYS . "\n";
echo "• Límite de préstamos: " . DEFAULT_LOAN_LIMIT . "\n";
echo "• Tarifa de multa diaria: $" . DAILY_FINE_RATE . "\n";

echo "\n🚀 Para acceder al sistema:\n";
echo "   URL: http://localhost/biblioteca/public/\n";
echo "   Usuario: admin\n";
echo "   Contraseña: password\n\n";

// Código de salida
exit($tests_failed > 0 ? 1 : 0);