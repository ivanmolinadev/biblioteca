<?php
/**
 * Protección CSRF y Validación de Tokens
 * Sistema de Biblioteca y Préstamos
 */

/**
 * Generar token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_time'] = time();
    
    return $token;
}

/**
 * Validar token CSRF
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar que existe el token en la sesión
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_time'])) {
        return false;
    }
    
    // Verificar que el token no haya expirado (1 hora)
    if (time() - $_SESSION['csrf_time'] > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_time']);
        return false;
    }
    
    // Verificar que los tokens coinciden
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    
    // Regenerar token después de validación exitosa
    if ($isValid) {
        generateCSRFToken();
    }
    
    return $isValid;
}

/**
 * Obtener token CSRF actual
 * @return string
 */
function getCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return generateCSRFToken();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Generar campo hidden con token CSRF para formularios
 * @return string
 */
function csrfTokenField() {
    $token = getCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validar token CSRF desde POST
 * @return bool
 */
function validateCSRFFromPost() {
    $token = $_POST['csrf_token'] ?? '';
    return validateCSRFToken($token);
}

/**
 * Middleware para verificar CSRF en formularios
 */
function requireCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFFromPost()) {
            http_response_code(403);
            die('Error de seguridad: Token CSRF inválido');
        }
    }
}