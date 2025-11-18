<?php
echo "<h1>Diagnóstico del Sistema de Biblioteca</h1>";
echo "<h2>1. Verificando rutas de archivos:</h2>";

$config_path = __DIR__ . '/../config/config.php';
$db_path = __DIR__ . '/../config/db.php';
$csrf_path = __DIR__ . '/../config/csrf.php';
$init_path = __DIR__ . '/../config/init.php';

echo "Ruta config.php: " . $config_path . " - Existe: " . (file_exists($config_path) ? "SÍ" : "NO") . "<br>";
echo "Ruta db.php: " . $db_path . " - Existe: " . (file_exists($db_path) ? "SÍ" : "NO") . "<br>";
echo "Ruta csrf.php: " . $csrf_path . " - Existe: " . (file_exists($csrf_path) ? "SÍ" : "NO") . "<br>";
echo "Ruta init.php: " . $init_path . " - Existe: " . (file_exists($init_path) ? "SÍ" : "NO") . "<br>";

echo "<h2>2. Probando inclusión de config.php:</h2>";
try {
    require_once $config_path;
    echo "✅ config.php cargado exitosamente<br>";
    
    echo "<h2>3. Verificando constantes:</h2>";
    $constantes = ['APP_NAME', 'APP_VERSION', 'SESSION_NAME', 'SESSION_TIMEOUT'];
    foreach ($constantes as $const) {
        echo "Constante $const: " . (defined($const) ? "✅ Definida = " . constant($const) : "❌ No definida") . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error al cargar config.php: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Probando init.php:</h2>";
try {
    require_once $init_path;
    echo "✅ init.php cargado exitosamente<br>";
} catch (Exception $e) {
    echo "❌ Error al cargar init.php: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Estado de PHP:</h2>";
echo "Versión PHP: " . PHP_VERSION . "<br>";
echo "Directorio actual: " . __DIR__ . "<br>";
echo "Directorio de trabajo: " . getcwd() . "<br>";
?>