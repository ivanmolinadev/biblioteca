<?php
if (!defined('APP_NAME')) {
    require_once '../config/init.php';
}
$current_user = getCurrentUser();
$page_title = $page_title ?? 'Sistema de Biblioteca';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= APP_NAME ?></title>
    
    <!-- CSS Bootstrap (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Iconos Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- CSS personalizado -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- Meta para SEO -->
    <meta name="description" content="Sistema de gestión de biblioteca y préstamos de libros">
    <meta name="author" content="Sistema de Biblioteca">
</head>
<body>
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary py-1">
        <div class="container-fluid px-3">
            <a class="navbar-brand me-auto" href="dashboard.php">
                <i class="bi bi-book"></i> Biblioteca
            </a>
            
            <?php if (isAuthenticated()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Navegación simplificada para Admin - Módulos principales -->
                    <li class="nav-item">
                        <a class="nav-link" href="libros.php">
                            <i class="bi bi-book"></i> Libros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="autores.php">
                            <i class="bi bi-person-lines-fill"></i> Autores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">
                            <i class="bi bi-tags"></i> Categorías
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lectores.php">
                            <i class="bi bi-people"></i> Lectores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prestamos.php">
                            <i class="bi bi-journal-arrow-up"></i> Préstamos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="devoluciones.php">
                            <i class="bi bi-journal-arrow-down"></i> Devoluciones
                        </a>
                    </li>
                    
                    <?php else: ?>
                    <!-- Menú para usuarios regulares (lectores) -->
                    <li class="nav-item">
                        <a class="nav-link" href="libros.php">
                            <i class="bi bi-search"></i> Catálogo de Libros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mis_prestamos.php">
                            <i class="bi bi-journal-text"></i> Mis Préstamos
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Menú usuario -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($current_user['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">
                                <?= htmlspecialchars($current_user['username']) ?><br>
                                <small class="text-muted"><?= ucfirst($current_user['role']) ?></small>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container-fluid py-4">
        <!-- Mostrar mensajes flash -->
        <?php 
        $flash_messages = getFlashMessages();
        foreach ($flash_messages as $type => $message): 
            $alert_class = match($type) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-info'
            };
        ?>
            <div class="alert <?= $alert_class ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>