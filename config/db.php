<?php
/**
 * Configuración de Base de Datos
 * Sistema de Biblioteca y Préstamos
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'biblioteca_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambiar por la contraseña de tu MySQL en XAMPP
define('DB_CHARSET', 'utf8mb4');

// Configuración PDO
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
}

/**
 * Función para obtener conexión PDO
 * @return PDO
 */
function getDbConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Función para ejecutar consultas preparadas
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta SQL: " . $e->getMessage());
        throw new Exception("Error en la operación de base de datos");
    }
}

/**
 * Función para obtener un solo registro
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Función para obtener múltiples registros
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Función para contar registros
 * @param string $table
 * @param string $where
 * @param array $params
 * @return int
 */
function countRecords($table, $where = '', $params = []) {
    $sql = "SELECT COUNT(*) as total FROM {$table}";
    if ($where) {
        $sql .= " WHERE {$where}";
    }
    $result = fetchOne($sql, $params);
    return (int)$result['total'];
}