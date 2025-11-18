<?php
/**
 * Dashboard Principal - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación
requireAuth();

$page_title = 'Dashboard';

try {
    // Estadísticas generales
    $total_libros = countRecords('libros', 'activo = 1');
    $total_lectores = countRecords('lectores');
    $prestamos_activos = countRecords('prestamos', 'estado = ?', ['activo']);
    $prestamos_atrasados = countRecords('prestamos', 'estado = ?', ['atrasado']);
    
    // Libros más prestados (últimos 30 días)
    $sql_libros_populares = "
        SELECT l.titulo, l.isbn, COUNT(p.id) as total_prestamos,
               GROUP_CONCAT(CONCAT(a.nombre, ' ', a.apellidos) SEPARATOR ', ') as autores
        FROM libros l
        LEFT JOIN prestamos p ON l.id = p.libro_id AND p.fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        LEFT JOIN libro_autores la ON l.id = la.libro_id
        LEFT JOIN autores a ON la.autor_id = a.id
        WHERE l.activo = 1
        GROUP BY l.id, l.titulo, l.isbn
        HAVING total_prestamos > 0
        ORDER BY total_prestamos DESC
        LIMIT 5";
    
    $libros_populares = fetchAll($sql_libros_populares);
    
    // Préstamos próximos a vencer (próximos 3 días)
    $sql_proximos_vencer = "
        SELECT p.id, p.fecha_vencimiento, l.titulo, 
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               lec.telefono, lec.dni,
               DATEDIFF(p.fecha_vencimiento, CURDATE()) as dias_restantes
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE p.estado = 'activo' 
        AND p.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ORDER BY p.fecha_vencimiento ASC
        LIMIT 10";
    
    $proximos_vencer = fetchAll($sql_proximos_vencer);
    
    // Préstamos atrasados
    $sql_atrasados = "
        SELECT p.id, p.fecha_vencimiento, l.titulo,
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               lec.telefono, lec.dni,
               DATEDIFF(CURDATE(), p.fecha_vencimiento) as dias_atraso
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE p.estado = 'atrasado'
        ORDER BY p.fecha_vencimiento ASC
        LIMIT 10";
    
    $prestamos_atrasados_list = fetchAll($sql_atrasados);
    
    // Actividad reciente (últimos 10 registros de préstamos)
    $sql_actividad = "
        SELECT 'prestamo' as tipo, p.fecha_prestamo as fecha, l.titulo,
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               p.estado
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        UNION ALL
        SELECT 'devolucion' as tipo, d.fecha_devolucion as fecha, l.titulo,
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               'devuelto' as estado
        FROM devoluciones d
        JOIN prestamos p ON d.prestamo_id = p.id
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        ORDER BY fecha DESC
        LIMIT 10";
    
    $actividad_reciente = fetchAll($sql_actividad);
    
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar las estadísticas del dashboard');
    
    // Valores por defecto en caso de error
    $total_libros = $total_lectores = $prestamos_activos = $prestamos_atrasados = 0;
    $libros_populares = $proximos_vencer = $prestamos_atrasados_list = $actividad_reciente = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">
            <i class="bi bi-speedometer2"></i> Dashboard
            <small class="text-muted">- Panel de Control</small>
        </h1>
    </div>
</div>

<!-- Estadísticas principales -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="stats-card">
            <h3><?= number_format($total_libros) ?></h3>
            <p><i class="bi bi-book"></i> Libros en Catálogo</p>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stats-card success">
            <h3><?= number_format($total_lectores) ?></h3>
            <p><i class="bi bi-people"></i> Lectores Registrados</p>
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
            <h3><?= number_format($prestamos_atrasados) ?></h3>
            <p><i class="bi bi-exclamation-triangle"></i> Préstamos Atrasados</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Préstamos próximos a vencer -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock"></i> Próximos a Vencer
                    <span class="badge bg-warning text-dark"><?= count($proximos_vencer) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($proximos_vencer)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                        <p class="mt-2 mb-0">No hay préstamos próximos a vencer</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Lector</th>
                                    <th>Libro</th>
                                    <th>Vence</th>
                                    <th>Días</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proximos_vencer as $prestamo): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($prestamo['lector_nombre']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($prestamo['dni']) ?></small>
                                        </td>
                                        <td>
                                            <span class="text-truncate-2"><?= htmlspecialchars($prestamo['titulo']) ?></span>
                                        </td>
                                        <td><?= formatDate($prestamo['fecha_vencimiento']) ?></td>
                                        <td>
                                            <span class="badge <?= $prestamo['dias_restantes'] <= 1 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                <?= $prestamo['dias_restantes'] ?> día(s)
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center">
                        <a href="prestamos_activos.php?filter=proximos" class="btn btn-sm btn-outline-primary">
                            Ver Todos
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Préstamos atrasados -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Préstamos Atrasados
                    <span class="badge bg-danger"><?= count($prestamos_atrasados_list) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($prestamos_atrasados_list)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                        <p class="mt-2 mb-0">No hay préstamos atrasados</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Lector</th>
                                    <th>Libro</th>
                                    <th>Venció</th>
                                    <th>Atraso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prestamos_atrasados_list as $prestamo): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($prestamo['lector_nombre']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($prestamo['dni']) ?></small>
                                        </td>
                                        <td>
                                            <span class="text-truncate-2"><?= htmlspecialchars($prestamo['titulo']) ?></span>
                                        </td>
                                        <td><?= formatDate($prestamo['fecha_vencimiento']) ?></td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?= $prestamo['dias_atraso'] ?> día(s)
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center">
                        <a href="prestamos_atrasados.php" class="btn btn-sm btn-outline-danger">
                            Ver Todos
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Libros más populares -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up"></i> Libros Más Prestados (30 días)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($libros_populares)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-bar-chart fs-1"></i>
                        <p class="mt-2 mb-0">No hay estadísticas disponibles</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($libros_populares as $index => $libro): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-pill"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($libro['titulo']) ?></h6>
                                        <p class="mb-0 text-muted small">
                                            <?= htmlspecialchars($libro['autores'] ?? 'Sin autor') ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success"><?= $libro['total_prestamos'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Actividad reciente -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history"></i> Actividad Reciente
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($actividad_reciente)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-activity fs-1"></i>
                        <p class="mt-2 mb-0">No hay actividad reciente</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($actividad_reciente as $actividad): ?>
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex align-items-start">
                                    <div class="me-3 mt-1">
                                        <?php if ($actividad['tipo'] === 'prestamo'): ?>
                                            <i class="bi bi-arrow-up-circle text-info"></i>
                                        <?php else: ?>
                                            <i class="bi bi-arrow-down-circle text-success"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?= $actividad['tipo'] === 'prestamo' ? 'Préstamo' : 'Devolución' ?>
                                        </h6>
                                        <p class="mb-1 text-truncate-2">
                                            <strong><?= htmlspecialchars($actividad['titulo']) ?></strong>
                                        </p>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($actividad['lector_nombre']) ?> - 
                                            <?= formatDate($actividad['fecha']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- Acciones rápidas para administradores -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning"></i> Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="prestamos.php" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Nuevo Préstamo
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="devoluciones.php" class="btn btn-success w-100">
                            <i class="bi bi-arrow-return-left"></i> Registrar Devolución
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="libros.php?action=create" class="btn btn-info w-100">
                            <i class="bi bi-book"></i> Agregar Libro
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="lectores.php?action=create" class="btn btn-warning w-100">
                            <i class="bi bi-person-plus"></i> Nuevo Lector
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>