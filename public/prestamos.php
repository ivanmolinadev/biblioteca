<?php
/**
 * Gestión de Préstamos - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación (sin restricción de rol)
requireAuth();

$page_title = 'Gestión de Préstamos';

// Procesar formulario de nuevo préstamo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_prestamo') {
    requireCSRF();
    
    $libro_id = (int)($_POST['libro_id'] ?? 0);
    $lector_id = (int)($_POST['lector_id'] ?? 0);
    $dias_prestamo = (int)($_POST['dias_prestamo'] ?? DEFAULT_LOAN_DAYS);
    
    try {
        // Verificar que el libro existe y está disponible
        $libro = fetchOne("SELECT * FROM libros WHERE id = ? AND activo = 1", [$libro_id]);
        if (!$libro) {
            throw new Exception("Libro no encontrado");
        }
        
        if ($libro['copias_disponibles'] <= 0) {
            throw new Exception("El libro no tiene copias disponibles");
        }
        
        // Verificar que el lector existe y puede prestar
        $lector = fetchOne("SELECT * FROM lectores WHERE id = ?", [$lector_id]);
        if (!$lector) {
            throw new Exception("Lector no encontrado");
        }
        
        if ($lector['bloqueado']) {
            throw new Exception("El lector está bloqueado por multas");
        }
        
        // Verificar límite de préstamos
        $prestamos_activos = countRecords('prestamos', 'lector_id = ? AND estado IN (?, ?)', 
                                        [$lector_id, 'activo', 'atrasado']);
        if ($prestamos_activos >= $lector['limite_prestamos']) {
            throw new Exception("El lector ha alcanzado su límite de préstamos ({$lector['limite_prestamos']})");
        }
        
        // Crear el préstamo
        $fecha_vencimiento = date('Y-m-d', strtotime("+{$dias_prestamo} days"));
        
        $sql = "INSERT INTO prestamos (libro_id, lector_id, fecha_prestamo, fecha_vencimiento, estado, usuario_id) 
                VALUES (?, ?, CURDATE(), ?, 'activo', ?)";
        executeQuery($sql, [$libro_id, $lector_id, $fecha_vencimiento, getCurrentUser()['id']]);
        
        
        setFlashMessage('success', 'Préstamo registrado exitosamente');
        redirect('prestamos.php');
        
    } catch (Exception $e) {
        setFlashMessage('error', 'Error al crear préstamo: ' . $e->getMessage());
    }
}

// Configuración de paginación
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? RECORDS_PER_PAGE);
$per_page = min(max($per_page, 5), MAX_RECORDS_PER_PAGE);
$offset = ($page - 1) * $per_page;

// Filtros de búsqueda
$search = sanitizeInput($_GET['search'] ?? '');
$estado_filter = $_GET['estado'] ?? '';
$fecha_filter = $_GET['fecha'] ?? '';

// Construir consulta con filtros
$where_conditions = ['1 = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(l.titulo LIKE ? OR lec.nombre LIKE ? OR lec.apellidos LIKE ? OR lec.dni LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($estado_filter)) {
    if ($estado_filter === 'vencidos') {
        $where_conditions[] = "p.estado = 'activo' AND p.fecha_vencimiento < CURDATE()";
    } else {
        $where_conditions[] = "p.estado = ?";
        $params[] = $estado_filter;
    }
}

if (!empty($fecha_filter)) {
    switch ($fecha_filter) {
        case 'hoy':
            $where_conditions[] = "DATE(p.fecha_prestamo) = CURDATE()";
            break;
        case 'semana':
            $where_conditions[] = "p.fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'mes':
            $where_conditions[] = "p.fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Contar total de registros
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE {$where_clause}
    ";
    $total_records = fetchOne($count_sql, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener préstamos con filtros
    $sql = "
        SELECT p.*, l.titulo, l.isbn,
               CONCAT(lec.nombre, ' ', lec.apellidos) as lector_nombre,
               lec.dni, lec.telefono,
               DATEDIFF(p.fecha_vencimiento, CURDATE()) as dias_restantes,
               CASE 
                   WHEN p.estado = 'activo' AND p.fecha_vencimiento < CURDATE() THEN 'atrasado'
                   ELSE p.estado
               END as estado_real
        FROM prestamos p
        JOIN libros l ON p.libro_id = l.id
        JOIN lectores lec ON p.lector_id = lec.id
        WHERE {$where_clause}
        ORDER BY p.fecha_prestamo DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $prestamos = fetchAll($sql, $params);
    
    // Actualizar estados atrasados automáticamente
    executeQuery("
        UPDATE prestamos 
        SET estado = 'atrasado' 
        WHERE estado = 'activo' AND fecha_vencimiento < CURDATE()
    ");
    
    // Obtener datos para formulario
    $libros_disponibles = fetchAll("
        SELECT id, titulo, isbn, copias_disponibles 
        FROM libros 
        WHERE activo = 1 AND copias_disponibles > 0 
        ORDER BY titulo
    ");
    
    $lectores_activos = fetchAll("
        SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo, dni, limite_prestamos,
               (SELECT COUNT(*) FROM prestamos WHERE lector_id = lectores.id AND estado IN ('activo', 'atrasado')) as prestamos_actuales
        FROM lectores 
        WHERE bloqueado = 0
        HAVING prestamos_actuales < limite_prestamos
        ORDER BY nombre, apellidos
    ");
    
} catch (Exception $e) {
    error_log("Error en gestión de préstamos: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar los préstamos');
    $prestamos = [];
    $libros_disponibles = [];
    $lectores_activos = [];
    $total_records = 0;
    $total_pages = 1;
}



include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-journal-arrow-up"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">Gestiona los préstamos activos y el historial</p>
    </div>
    <?php if (isAdmin()): ?>
    <div>
        <a href="prestamo_form.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Préstamo
        </a>
    </div>
    <?php endif; ?>
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
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?= $estado_filter === 'activo' ? 'selected' : '' ?>>Activos</option>
                    <option value="atrasado" <?= $estado_filter === 'atrasado' ? 'selected' : '' ?>>Atrasados</option>
                    <option value="devuelto" <?= $estado_filter === 'devuelto' ? 'selected' : '' ?>>Devueltos</option>
                    <option value="vencidos" <?= $estado_filter === 'vencidos' ? 'selected' : '' ?>>Vencidos Hoy</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="fecha" class="form-label">Fecha Préstamo</label>
                <select class="form-select" id="fecha" name="fecha">
                    <option value="">Todas las fechas</option>
                    <option value="hoy" <?= $fecha_filter === 'hoy' ? 'selected' : '' ?>>Hoy</option>
                    <option value="semana" <?= $fecha_filter === 'semana' ? 'selected' : '' ?>>Última semana</option>
                    <option value="mes" <?= $fecha_filter === 'mes' ? 'selected' : '' ?>>Último mes</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="prestamos.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de préstamos -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Registro de Préstamos
            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($prestamos)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-arrow-up text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No hay préstamos registrados</h5>
                <p class="text-muted">Comience registrando el primer préstamo</p>
                <?php if (isAdmin()): ?>
                <button type="button" class="btn btn-primary" style="position: relative; z-index: 1000;" data-bs-toggle="modal" data-bs-target="#nuevoPrestamoModal">
                    <i class="bi bi-plus-circle"></i> Crear Primer Préstamo
                </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lector</th>
                            <th>Libro</th>
                            <th>Fecha Préstamo</th>
                            <th>Fecha Vencimiento</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestamos as $prestamo): ?>
                            <tr class="<?= $prestamo['estado_real'] === 'atrasado' ? 'table-danger' : '' ?>">
                                <td><strong>#<?= $prestamo['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($prestamo['lector_nombre']) ?></strong>
                                    <br><small class="text-muted">DNI: <?= htmlspecialchars($prestamo['dni']) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($prestamo['titulo']) ?></strong>
                                    <?php if ($prestamo['isbn']): ?>
                                        <br><small class="text-muted">ISBN: <?= htmlspecialchars($prestamo['isbn']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatDate($prestamo['fecha_prestamo']) ?></td>
                                <td>
                                    <?= formatDate($prestamo['fecha_vencimiento']) ?>
                                    <?php if ($prestamo['estado'] === 'activo'): ?>
                                        <br><small class="<?= $prestamo['dias_restantes'] < 0 ? 'text-danger' : ($prestamo['dias_restantes'] <= 3 ? 'text-warning' : 'text-success') ?>">
                                            <?php if ($prestamo['dias_restantes'] < 0): ?>
                                                Vencido hace <?= abs($prestamo['dias_restantes']) ?> día(s)
                                            <?php elseif ($prestamo['dias_restantes'] == 0): ?>
                                                Vence hoy
                                            <?php else: ?>
                                                <?= $prestamo['dias_restantes'] ?> día(s) restantes
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $estado = $prestamo['estado_real'];
                                    $badge_class = match($estado) {
                                        'activo' => 'bg-success',
                                        'atrasado' => 'bg-danger',
                                        'devuelto' => 'bg-secondary',
                                        default => 'bg-info'
                                    };
                                    ?>
                                    <span class="badge <?= $badge_class ?>">
                                        <i class="bi <?= $estado === 'activo' ? 'bi-check-circle' : ($estado === 'atrasado' ? 'bi-exclamation-triangle' : 'bi-arrow-return-left') ?>"></i>
                                        <?= ucfirst($estado) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <!-- Botón de ver eliminado -->
                                        <?php if (isAdmin()): ?>
                                            <?php if ($prestamo['estado'] === 'activo' || $prestamo['estado_real'] === 'atrasado'): ?>
                                                <a href="devolucion_form.php?prestamo_id=<?= $prestamo['id'] ?>" 
                                                   class="btn btn-outline-success"
                                                   data-bs-toggle="tooltip" 
                                                   title="Registrar devolución">
                                                    <i class="bi bi-arrow-return-left"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="prestamo_form.php?id=<?= $prestamo['id'] ?>" 
                                               class="btn btn-outline-primary"
                                               data-bs-toggle="tooltip" 
                                               title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginación de préstamos">
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

<!-- Modal Nuevo Préstamo -->
<div class="modal fade" id="nuevoPrestamoModal" tabindex="-1" aria-labelledby="nuevoPrestamoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal content removed as per the patch requirement -->
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>