<?php
/**
 * Gestión de Lectores - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$page_title = 'Gestión de Lectores';

// Configuración de paginación
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? RECORDS_PER_PAGE);
$per_page = min(max($per_page, 5), MAX_RECORDS_PER_PAGE);
$offset = ($page - 1) * $per_page;

// Filtros de búsqueda
$search = sanitizeInput($_GET['search'] ?? '');
$estado_filter = $_GET['estado'] ?? '';

// Construir consulta con filtros
$where_conditions = ['1 = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(l.nombre LIKE ? OR l.apellidos LIKE ? OR l.dni LIKE ? OR l.telefono LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($estado_filter === 'bloqueado') {
    $where_conditions[] = "l.bloqueado = 1";
} elseif ($estado_filter === 'activo') {
    $where_conditions[] = "l.bloqueado = 0";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total FROM lectores l WHERE {$where_clause}";
    $total_records = fetchOne($count_sql, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener lectores con filtros
    $sql = "
        SELECT l.*,
               u.username, u.email,
               (SELECT COUNT(*) FROM prestamos p WHERE p.lector_id = l.id AND p.estado IN ('activo', 'atrasado')) as prestamos_activos,
               (SELECT COUNT(*) FROM prestamos p WHERE p.lector_id = l.id) as total_prestamos
        FROM lectores l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        WHERE {$where_clause}
        ORDER BY l.nombre ASC, l.apellidos ASC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $lectores = fetchAll($sql, $params);
    
} catch (Exception $e) {
    error_log("Error en listado de lectores: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar el listado de lectores');
    $lectores = [];
    $total_records = 0;
    $total_pages = 1;
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-people"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">Gestiona los lectores registrados en el sistema</p>
    </div>
    <div>
        <a href="lector_form.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Agregar Lector
        </a>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Búsqueda</label>
                <input type="text" 
                       class="form-control live-search" 
                       id="search" 
                       name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Nombre, DNI o teléfono..."
                       data-target="#lectoresTable">
            </div>
            
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?= $estado_filter === 'activo' ? 'selected' : '' ?>>Activos</option>
                    <option value="bloqueado" <?= $estado_filter === 'bloqueado' ? 'selected' : '' ?>>Bloqueados</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <div class="me-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
                <div class="me-2">
                    <a href="lectores.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Limpiar
                    </a>
                </div>
                
                <div class="ms-auto">
                    <select name="per_page" class="form-select" onchange="this.form.submit()">
                        <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 por página</option>
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 por página</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 por página</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de lectores -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Lectores Registrados
            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($lectores)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No hay lectores registrados</h5>
                <p class="text-muted">Comience registrando el primer lector en el sistema</p>
                <a href="lector_form.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Registrar Primer Lector
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="lectoresTable">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>DNI</th>
                            <th>Contacto</th>
                            <th>Usuario Sistema</th>
                            <th>Préstamos</th>
                            <th>Multa Total</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lectores as $lector): ?>
                            <tr class="<?= $lector['bloqueado'] ? 'table-warning' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($lector['nombre'] . ' ' . $lector['apellidos']) ?></strong>
                                    <?php if ($lector['fecha_nacimiento']): ?>
                                        <br><small class="text-muted">Nacido: <?= formatDate($lector['fecha_nacimiento']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lector['dni']): ?>
                                        <code><?= htmlspecialchars($lector['dni']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">Sin DNI</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lector['telefono']): ?>
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($lector['telefono']) ?><br>
                                    <?php endif; ?>
                                    <?php if ($lector['email']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($lector['email']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lector['username']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-person-check"></i> <?= htmlspecialchars($lector['username']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin cuenta</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lector['prestamos_activos'] > 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= $lector['prestamos_activos'] ?> activos
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin préstamos</span>
                                    <?php endif; ?>
                                    <br><small class="text-muted">Total: <?= $lector['total_prestamos'] ?></small>
                                </td>
                                <td>
                                    <?php if ($lector['multa_total'] > 0): ?>
                                        <span class="text-danger">
                                            <strong><?= formatCurrency($lector['multa_total']) ?></strong>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lector['bloqueado']): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-lock"></i> Bloqueado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-unlock"></i> Activo
                                        </span>
                                    <?php endif; ?>
                                    <br><small class="text-muted">
                                        Límite: <?= $lector['limite_prestamos'] ?> libros
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="lector_detalle.php?id=<?= $lector['id'] ?>" 
                                           class="btn btn-outline-info"
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="lector_form.php?id=<?= $lector['id'] ?>" 
                                           class="btn btn-outline-primary"
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($lector['prestamos_activos'] == 0): ?>
                                            <a href="lector_delete.php?id=<?= $lector['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete"
                                               data-bs-toggle="tooltip" 
                                               title="Eliminar"
                                               data-confirm="¿Está seguro de que desea eliminar este lector?">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" 
                                                    disabled
                                                    data-bs-toggle="tooltip" 
                                                    title="No se puede eliminar: tiene préstamos activos">
                                                <i class="bi bi-lock"></i>
                                            </button>
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
                <nav aria-label="Paginación de lectores">
                    <ul class="pagination justify-content-center">
                        <!-- Primera página -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Páginas anteriores -->
                        <?php for ($i = max(1, $page - 2); $i < $page; $i++): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Página actual -->
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        
                        <!-- Páginas siguientes -->
                        <?php for ($i = $page + 1; $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Última página -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&<?= http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)) ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Mostrando <?= ($offset + 1) ?> - <?= min($offset + $per_page, $total_records) ?> 
                    de <?= number_format($total_records) ?> lectores
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>