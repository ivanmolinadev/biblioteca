<?php
/**
 * Dashboard Principal - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación
requireAuth();

$page_title = 'Dashboard';

// Dashboard único para todos los usuarios
try {
    $total_libros = countRecords('libros', 'activo = 1');
    $total_lectores = countRecords('lectores');
    $prestamos_activos = countRecords('prestamos', 'estado = ?', ['activo']);
    $prestamos_vencidos = countRecords('prestamos', 'estado = ? OR (estado = ? AND fecha_vencimiento < CURDATE())', ['atrasado', 'activo']);
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar las estadísticas');
    $total_libros = $total_lectores = $prestamos_activos = $prestamos_vencidos = 0;
}

include '../includes/header.php';
?>


<!-- DASHBOARD ÚNICO PARA TODOS LOS USUARIOS: Panel de Administración -->
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="bi bi-shield-check"></i> Panel de Administración
            <small class="text-muted">- Gestión del Sistema</small>
        </h1>
    </div>
</div>

<!-- Estadísticas básicas para gestión -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <h3><?= number_format($total_libros) ?></h3>
            <p><i class="bi bi-book"></i> Libros Registrados</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card success">
            <h3><?= number_format($total_lectores) ?></h3>
            <p><i class="bi bi-people"></i> Lectores</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card warning">
            <h3><?= number_format($prestamos_activos) ?></h3>
            <p><i class="bi bi-journal-check"></i> Préstamos Activos</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="stats-card danger">
            <h3><?= number_format($prestamos_vencidos) ?></h3>
            <p><i class="bi bi-exclamation-triangle"></i> Préstamos vencidos (No devueltos)</p>
        </div>
    </div>
</div>

<!-- Módulos de gestión -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-grid-3x3-gap"></i> Módulos de Gestión
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <a href="libros.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="bi bi-book fs-4 d-block mb-2"></i>
                            Libros
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="autores.php" class="btn btn-outline-info w-100 py-3">
                            <i class="bi bi-person-lines-fill fs-4 d-block mb-2"></i>
                            Autores
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="categorias.php" class="btn btn-outline-secondary w-100 py-3">
                            <i class="bi bi-tags fs-4 d-block mb-2"></i>
                            Categorías
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="lectores.php" class="btn btn-outline-success w-100 py-3">
                            <i class="bi bi-people fs-4 d-block mb-2"></i>
                            Lectores
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="prestamos.php" class="btn btn-outline-warning w-100 py-3">
                            <i class="bi bi-journal-arrow-up fs-4 d-block mb-2"></i>
                            Préstamos
                        </a>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="devoluciones.php" class="btn btn-outline-danger w-100 py-3">
                            <i class="bi bi-journal-arrow-down fs-4 d-block mb-2"></i>
                            Devoluciones
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>