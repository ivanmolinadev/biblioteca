<?php
/**
 * Gestión de Devoluciones - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación (sin restricción de rol)
requireAuth();

$page_title = 'Gestión de Devoluciones';

// Procesar formulario de nueva devolución
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_devolucion') {
    requireCSRF();
    
    $prestamo_id = (int)($_POST['prestamo_id'] ?? 0);
    $observaciones = sanitizeInput($_POST['observaciones'] ?? '');
    
    try {
        // Verificar que el préstamo existe y está activo
        $prestamo = fetchOne("
            SELECT p.*, l.titulo, lec.id as lector_id, lec.nombre, lec.apellidos, lec.multa_total
            FROM prestamos p
            JOIN libros l ON p.libro_id = l.id
            JOIN lectores lec ON p.lector_id = lec.id
            WHERE p.id = ? AND p.estado IN ('activo', 'atrasado')
        ", [$prestamo_id]);
        
        if (!$prestamo) {
            throw new Exception("Préstamo no encontrado o ya devuelto");
        }
        
        // Los triggers se encargan del cálculo automático de multas y actualización de estado
        $fecha_devolucion = date('Y-m-d');
        
        // Insertar registro de devolución (los triggers harán el resto automáticamente)
        $sql_devolucion = "
            INSERT INTO devoluciones (prestamo_id, fecha_devolucion, observaciones, usuario_registro_id)
            VALUES (?, ?, ?, ?)
        ";
        executeQuery($sql_devolucion, [
            $prestamo_id, 
            $fecha_devolucion, 
            $observaciones, 
            getCurrentUser()['id']
        ]);
        
        setFlashMessage('success', 'Devolución registrada exitosamente');
        redirect('devoluciones.php');
        
    } catch (Exception $e) {
        error_log("Error en devolución: " . $e->getMessage());
        setFlashMessage('error', 'Error al registrar devolución: ' . $e->getMessage());
    }
}

// Configuración de paginación
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? RECORDS_PER_PAGE);
$per_page = min(max($per_page, 5), MAX_RECORDS_PER_PAGE);
$offset = ($page - 1) * $per_page;

// Filtros de búsqueda
$search = sanitizeInput($_GET['search'] ?? '');
$fecha_filter = $_GET['fecha'] ?? '';
$multa_filter = $_GET['multa'] ?? '';

// Construir consulta con filtros
$where_conditions = ['1 = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(l.titulo LIKE ? OR lec.nombre LIKE ? OR lec.apellidos LIKE ? OR lec.dni LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($fecha_filter)) {
    switch ($fecha_filter) {
        case 'hoy':
            $where_conditions[] = "DATE(d.fecha_devolucion) = CURDATE()";
            break;
        case 'semana':
            $where_conditions[] = "d.fecha_devolucion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'mes':
            $where_conditions[] = "d.fecha_devolucion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
}

if ($multa_filter === 'con_multa') {
    $where_conditions[] = "d.multa_por_atraso > 0";
} elseif ($multa_filter === 'sin_multa') {
    $where_conditions[] = "d.multa_por_atraso = 0";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Contar total de registros
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM devoluciones d
        JOIN prestamos p ON d.prestamo_id = p.id
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE {$where_clause}
    ";
    $total_records = fetchOne($count_sql, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener devoluciones con filtros
    $sql = "
        SELECT d.*, p.fecha_prestamo, p.fecha_vencimiento,
               l.titulo, l.isbn,
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               lec.dni,
               d.dias_atraso,
               d.multa_por_atraso as multa_total
        FROM devoluciones d
        JOIN prestamos p ON d.prestamo_id = p.id
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE {$where_clause}
        ORDER BY d.fecha_devolucion DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $devoluciones = fetchAll($sql, $params);
    
    // Obtener préstamos activos para el modal
    $prestamos_activos = fetchAll("
        SELECT p.id, p.fecha_prestamo, p.fecha_vencimiento,
               l.titulo, l.isbn,
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               lec.dni,
               DATEDIFF(CURDATE(), p.fecha_vencimiento) as dias_atraso
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE p.estado IN ('activo', 'atrasado')
        ORDER BY p.fecha_vencimiento ASC
    ");
    
} catch (Exception $e) {
    error_log("Error en listado de devoluciones: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar las devoluciones');
    $devoluciones = [];
    $prestamos_activos = [];
    $total_records = 0;
    $total_pages = 1;
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-journal-arrow-down"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">Gestiona las devoluciones y el cálculo de multas</p>
    </div>

</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Búsqueda</label>
                <input type="text" 
                       class="form-control" 
                       id="search" 
                       name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Libro, lector o DNI...">
            </div>
            
            <div class="col-md-3">
                <label for="fecha" class="form-label">Fecha Devolución</label>
                <select class="form-select" id="fecha" name="fecha">
                    <option value="">Todas las fechas</option>
                    <option value="hoy" <?= $fecha_filter === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                    <option value="semana" <?= $fecha_filter === 'semana' ? 'selected' : '' ?>>Última semana</option>
                    <option value="mes" <?= $fecha_filter === 'mes' ? 'selected' : '' ?>>Último mes</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="multa" class="form-label">Multas</label>
                <select class="form-select" id="multa" name="multa">
                    <option value="">Todas</option>
                    <option value="con_multa" <?= $multa_filter === 'con_multa' ? 'selected' : '' ?>>Con multa</option>
                    <option value="sin_multa" <?= $multa_filter === 'sin_multa' ? 'selected' : '' ?>>Sin multa</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="devoluciones.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de devoluciones -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Registro de Devoluciones
            <span class="badge bg-success ms-2"><?= number_format($total_records) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($devoluciones)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-arrow-down text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No hay devoluciones registradas</h5>
                <p class="text-muted">Comience registrando la primera devolución</p>

            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Préstamo #</th>
                            <th>Lector</th>
                            <th>Libro</th>
                            <th>Fecha Devolución</th>
                            <th>Días Atraso</th>
                            <th>Multa Total</th>
                            <th>Estado</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devoluciones as $devolucion): ?>
                            <tr>
                                <td><strong>#<?= $devolucion['prestamo_id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($devolucion['lector_nombre']) ?></strong>
                                    <br><small class="text-muted">DNI: <?= htmlspecialchars($devolucion['dni']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($devolucion['titulo']) ?></strong>
                                    <?php if ($devolucion['isbn']): ?>
                                        <br><small class="text-muted">ISBN: <?= htmlspecialchars($devolucion['isbn']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= formatDate($devolucion['fecha_devolucion']) ?>
                                    <br><small class="text-muted">
                                        Prestado: <?= formatDate($devolucion['fecha_prestamo']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($devolucion['dias_atraso'] > 0): ?>
                                        <span class="badge bg-danger"><?= $devolucion['dias_atraso'] ?> día(s)</span>
                                    <?php elseif ($devolucion['dias_atraso'] < 0): ?>
                                        <span class="badge bg-success">A tiempo (<?= abs($devolucion['dias_atraso']) ?> días antes)</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">En fecha</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($devolucion['multa_total'] > 0): ?>
                                        <span class="text-danger fw-bold"><?= formatCurrency($devolucion['multa_total']) ?></span>
                                        <br><small class="text-muted">
                                            Tarifa: <?= formatCurrency($devolucion['tarifa_diaria'] ?? 0.25) ?>/día
                                            <?php if ($devolucion['multa_pagada']): ?>
                                                <br><span class="badge bg-success">Pagada</span>
                                            <?php else: ?>
                                                <br><span class="badge bg-danger">Pendiente</span>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-success">Sin multa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">Devuelto</span>
                                    <br><small class="text-muted">
                                        <?= formatDate($devolucion['fecha_creacion'], 'd/m/Y H:i') ?>
                                    </small>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginación de devoluciones">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i < $page; $i++): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        
                        <?php for ($i = $page + 1; $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nueva Devolución -->
<div class="modal fade" id="nuevaDevolucionModal" tabindex="-1" aria-labelledby="nuevaDevolucionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= generateCSRFToken() ?>
                <input type="hidden" name="action" value="registrar_devolucion">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevaDevolucionModalLabel">
                        <i class="bi bi-arrow-return-left"></i> Registrar Devolución
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="prestamo_id" class="form-label">Préstamo a Devolver *</label>
                            <select class="form-select" id="prestamo_id" name="prestamo_id" required onchange="mostrarInfoPrestamo()">
                                <option value="">Seleccione un préstamo activo</option>
                                <?php foreach ($prestamos_activos as $prestamo): ?>
                                    <option value="<?= $prestamo['id'] ?>" 
                                            data-dias-atraso="<?= max(0, $prestamo['dias_atraso']) ?>"
                                            data-multa-diaria="<?= DAILY_FINE_RATE ?>">
                                        #<?= $prestamo['id'] ?> - <?= htmlspecialchars($prestamo['lector_nombre']) ?> - 
                                        <?= htmlspecialchars($prestamo['titulo']) ?>
                                        <?php if ($prestamo['dias_atraso'] > 0): ?>
                                            <span class="text-danger">(<?= $prestamo['dias_atraso'] ?> días atrasado)</span>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Nota:</strong> La multa por atraso se calculará automáticamente según los días de retraso y la tarifa configurada en el sistema.
                            </div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                      placeholder="Observaciones sobre el estado del libro o la devolución..."></textarea>
                        </div>
                        
                        <!-- Resumen de multa -->
                        <div class="col-12">
                            <div class="alert alert-info" id="resumen-multa" style="display: none;">
                                <h6><i class="bi bi-info-circle"></i> Información del Préstamo:</h6>
                                <div id="detalle-multa"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Registrar Devolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function mostrarInfoPrestamo() {
    const select = document.getElementById('prestamo_id');
    const option = select.selectedOptions[0];
    
    if (option && option.value) {
        const diasAtraso = parseInt(option.dataset.diasAtraso) || 0;
        
        if (diasAtraso > 0) {
            let detalle = `<strong><i class="bi bi-exclamation-triangle text-warning"></i> Préstamo atrasado:</strong> ${diasAtraso} día(s)<br>`;
            detalle += `<small class="text-muted">La multa se calculará automáticamente al registrar la devolución.</small>`;
            
            document.getElementById('detalle-multa').innerHTML = detalle;
            document.getElementById('resumen-multa').style.display = 'block';
        } else {
            document.getElementById('resumen-multa').style.display = 'none';
        }
    } else {
        document.getElementById('resumen-multa').style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>