<?php
/**
 * Configuración de desarrollo - Sistema de Biblioteca y Préstamos
 * Este archivo debe ser renombrado o copiado como dev_config.php y personalizado
 */

// Definir entorno de desarrollo
define('ENVIRONMENT', 'development');

// Configuración de base de datos para desarrollo
define('DEV_DB_HOST', 'localhost');
define('DEV_DB_NAME', 'biblioteca_dev');
define('DEV_DB_USER', 'root');
define('DEV_DB_PASS', '');

// Configuración de errores para desarrollo
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// URLs de desarrollo
define('DEV_APP_URL', 'http://localhost/biblioteca');

// Configuración de logs para desarrollo
define('DEV_LOG_LEVEL', 'debug');

// Desactivar caché en desarrollo
define('DEV_CACHE_ENABLED', false);

// Configuración de debug
define('DEBUG_MODE', true);

/**
 * Función para debug (solo en desarrollo)
 */
function debug($data, $label = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<div style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin: 10px; font-family: monospace;">';
        if ($label) {
            echo '<strong>' . htmlspecialchars($label) . ':</strong><br>';
        }
        echo '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
        echo '</div>';
    }
}

/**
 * Función para logging en desarrollo
 */
function devLog($message, $level = 'info') {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents(__DIR__ . '/dev.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Configuración de desarrollo de correo (simulado)
define('DEV_MAIL_ENABLED', false);
define('DEV_MAIL_LOG', true);

/**
 * Simular envío de email en desarrollo
 */
function devSendMail($to, $subject, $message) {
    if (defined('DEV_MAIL_LOG') && DEV_MAIL_LOG) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'message' => $message
        ];
        
        file_put_contents(
            __DIR__ . '/mail.log', 
            json_encode($logEntry) . PHP_EOL, 
            FILE_APPEND | LOCK_EX
        );
    }
    
    return true; // Simular éxito
}