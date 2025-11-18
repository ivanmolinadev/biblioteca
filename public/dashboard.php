<?php
/**
 * Dashboard Principal - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación
requireAuth();

$page_title = 'Dashboard';

// Dashboard diferenciado por rol según especificaciones
if (isAdmin()) {
    // ADMIN: Gestiona todo el sistema
    try {
        // Estadísticas básicas para gestión
        $total_libros = countRecords('libros', 'activo = 1');
        $total_lectores = countRecords('lectores');
        $prestamos_activos = countRecords('prestamos', 'estado = ?', ['activo']);
        $prestamos_vencidos = countRecords('prestamos', 'estado = ? OR (estado = ? AND fecha_vencimiento < CURDATE())', ['atrasado', 'activo']);
        
    } catch (Exception $e) {
        error_log("Error en dashboard admin: " . $e->getMessage());
        setFlashMessage('error', 'Error al cargar las estadísticas');
        $total_libros = $total_lectores = $prestamos_activos = $prestamos_vencidos = 0;
    }
} else {
    // USUARIO: Solo consulta catálogo y sus préstamos
    try {
        // Obtener información del lector actual
        $user_id = getCurrentUser()['id'];
        $lector = fetchOne("SELECT * FROM lectores WHERE usuario_id = ?", [$user_id]);
        
        if ($lector) {
            // Préstamos del usuario actual
            $mis_prestamos = fetchAll("
                SELECT p.*, l.titulo, l.isbn, p.fecha_vencimiento,
                       DATEDIFF(p.fecha_vencimiento, CURDATE()) as dias_restantes
                FROM prestamos p
                JOIN libros l ON p.libro_id = l.id
                WHERE p.lector_id = ? AND p.estado IN ('activo', 'atrasado')
                ORDER BY p.fecha_vencimiento ASC
            ", [$lector['id']]);
            
            $total_mis_prestamos = count($mis_prestamos);
            $prestamos_por_vencer = count(array_filter($mis_prestamos, function($p) { 
                return $p['dias_restantes'] <= 3 && $p['dias_restantes'] >= 0; 
            }));
        } else {
            $mis_prestamos = [];
            $total_mis_prestamos = 0;
            $prestamos_por_vencer = 0;
        }
        
    } catch (Exception $e) {
        error_log("Error en dashboard usuario: " . $e->getMessage());
        setFlashMessage('error', 'Error al cargar sus préstamos');
        $mis_prestamos = [];
        $total_mis_prestamos = 0;
        $prestamos_por_vencer = 0;
    }
}

include '../includes/header.php';
?>

<?php if (isAdmin()): ?>
    <!-- DASHBOARD PARA ADMINISTRADOR: Gestiona todo -->
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
                <p><i class="bi bi-exclamation-triangle"></i> Préstamos de libros vencidos (No devueltos)</p>
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

<?php else: ?>
    <!-- DASHBOARD PARA USUARIO: Solo consulta catálogo y sus préstamos -->
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-person-circle"></i> Mi Biblioteca
                <small class="text-muted">- Bienvenido <?= htmlspecialchars(getCurrentUser()['username']) ?></small>
            </h1>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Consultar catálogo -->
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-search fs-1 text-primary mb-3"></i>
                    <h5 class="card-title">Consultar Catálogo</h5>
                    <p class="card-text text-muted">Explora todos los libros disponibles en la biblioteca</p>
                    <a href="catalogo.php" class="btn btn-primary">
                        <i class="bi bi-book"></i> Ver Catálogo
                    </a>
                </div>
            </div>
        </div>

        <!-- Mis préstamos -->
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center py-5">
                    <i class="bi bi-journal-check fs-1 text-success mb-3"></i>
                    <h5 class="card-title">Mis Préstamos</h5>
                    <p class="card-text text-muted">Revisa tus libros prestados y fechas de devolución</p>
                    <a href="mis_prestamos.php" class="btn btn-success">
                        <i class="bi bi-list-check"></i> Ver Mis Préstamos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de préstamos activos -->
    <?php if (isset($lector) && $lector): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Resumen de Préstamos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h3 class="text-primary"><?= $total_mis_prestamos ?></h3>
                                <p class="mb-0">Libros Prestados</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h3 class="text-warning"><?= $prestamos_por_vencer ?></h3>
                                <p class="mb-0">Por Vencer (3 días)</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h3 class="text-success"><?= DEFAULT_LOAN_LIMIT - $total_mis_prestamos ?></h3>
                                <p class="mb-0">Disponibles</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($mis_prestamos)): ?>
                    <hr>
                    <h6>Próximos Vencimientos:</h6>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($mis_prestamos, 0, 3) as $prestamo): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($prestamo['titulo']) ?></h6>
                                    <small class="text-muted">Vence: <?= formatDate($prestamo['fecha_vencimiento']) ?></small>
                                </div>
                                <span class="badge <?= $prestamo['dias_restantes'] <= 1 ? 'bg-danger' : ($prestamo['dias_restantes'] <= 3 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                    <?= $prestamo['dias_restantes'] >= 0 ? $prestamo['dias_restantes'] . ' días' : 'VENCIDO' ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>