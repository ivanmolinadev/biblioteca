<?php
/**
 * Configuración General del Sistema
 * Sistema de Biblioteca y Préstamos
 */

// Las constantes se definen en init.php para evitar duplicaciones

// Zonas horarias
date_default_timezone_set('America/Mexico_City');

// Configuración de errores (solo para desarrollo)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

/**
 * Función para obtener configuración de la base de datos
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getConfig($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        try {
            require_once __DIR__ . '/db.php';
            $sql = "SELECT clave, valor FROM configuracion WHERE clave = ?";
            $stmt = executeQuery($sql, [$key]);
            $result = $stmt->fetch();
            return $result ? $result['valor'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Función para establecer configuración
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function setConfig($key, $value) {
    try {
        require_once __DIR__ . '/db.php';
        $sql = "INSERT INTO configuracion (clave, valor) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), fecha_modificacion = NOW()";
        executeQuery($sql, [$key, $value]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Función para formatear fechas
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Función para formatear moneda
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Función para sanitizar entrada
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Función para validar email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Función para generar URL
 * @param string $path
 * @return string
 */
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

