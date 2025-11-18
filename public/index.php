<?php
/**
 * Página de inicio - Sistema de Biblioteca y Préstamos
 * Redirige al usuario según su estado de autenticación
 */
require_once '../config/init.php';

// Verificar si el usuario está autenticado
if (isAuthenticated()) {
    // Redirigir según el rol
    if (isAdmin()) {
        redirect('dashboard.php');
    } else {
        redirect('dashboard.php');
    }
} else {
    // Redirigir al login
    redirect('login.php');
}