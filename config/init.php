<?php
/**
 * Archivo de inicialización del sistema
 * Sistema de Biblioteca y Préstamos
 */

// Definir constantes del sistema
define('APP_NAME', 'Sistema de Biblioteca y Préstamos');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/biblioteca');
define('SESSION_TIMEOUT', 1800);
define('SESSION_NAME', 'biblioteca_session');
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 6);
define('RECORDS_PER_PAGE', 10);
define('MAX_RECORDS_PER_PAGE', 50);
define('DEFAULT_LOAN_DAYS', 14);
define('DEFAULT_LOAN_LIMIT', 3);
define('DAILY_FINE_RATE', 0.25);
define('FINE_BLOCK_LIMIT', 10.00);

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Incluir archivos de configuración
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

// Configurar sesiones seguras
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name('biblioteca_session');
    session_start();
}

/**
 * Función de redirección
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Verificar si el usuario es admin
 */
function isAdmin() {
    return isAuthenticated() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'usuario'
    ];
}

/**
 * Verificar timeout de sesión
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) {
            session_unset();
            session_destroy();
            redirect('login.php?timeout=1');
        }
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Regenerar ID de sesión
 */
function regenerateSessionId() {
    if (!isset($_SESSION['regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
    } else if (time() - $_SESSION['regenerated'] > 300) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
    }
}

/**
 * Requerir autenticación
 */
function requireAuth($adminOnly = false) {
    if (!isAuthenticated()) {
        redirect('login.php');
    }
    
    if ($adminOnly && !isAdmin()) {
        redirect('dashboard.php?error=no_permission');
    }
}

/**
 * Cerrar sesión
 */
function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    redirect('login.php?logout=1');
}

/**
 * Establecer mensaje flash
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Obtener mensajes flash
 */
function getFlashMessages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Función para logs de actividad
 */
function logActivity($action, $details = '') {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO logs_actividad (user_id, accion, detalles, ip, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

/**
 * Inicializar la aplicación
 */
function initializeApp() {
    checkSessionTimeout();
    regenerateSessionId();
}

// Inicializar la aplicación
initializeApp();