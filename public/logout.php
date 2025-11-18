<?php
/**
 * Página de Logout - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Verificar que el usuario esté autenticado
if (isAuthenticated()) {
    logActivity('logout', 'Cierre de sesión');
}

// Llamar función de logout
logout();