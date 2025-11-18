<?php
/**
 * Gestión de Categorías - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$page_title = 'Gestión de Categorías';

// Configuración de paginación
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? RECORDS_PER_PAGE);
$per_page = min(max($per_page, 5), MAX_RECORDS_PER_PAGE);
$offset = ($page - 1) * $per_page;

// Filtros de búsqueda
$search = sanitizeInput($_GET['search'] ?? '');

// Construir consulta con filtros
$where_conditions = ['c.activo = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.nombre LIKE ? OR c.descripcion LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total FROM categorias c WHERE {$where_clause}";
    $total_records = fetchOne($count_sql, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener categorías con filtros
    $sql = "
        SELECT c.*,
               (SELECT COUNT(*) FROM libros l WHERE l.categoria_id = c.id AND l.activo = 1) as total_libros
        FROM categorias c
        WHERE {$where_clause}
        ORDER BY c.nombre ASC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $categorias = fetchAll($sql, $params);
    
} catch (Exception $e) {
    error_log("Error en listado de categorías: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar el listado de categorías');
    $categorias = [];
    $total_records = 0;
    $total_pages = 1;
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-tags"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">Gestiona las categorías para clasificar los libros</p>
    </div>
    <div>
        <a href="categoria_form.php" class="btn btn-primary" style="position: relative; z-index: 1000;">
            <i class="bi bi-plus-circle"></i> Agregar Categoría
        </a>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-9">
                <label for="search" class="form-label">Búsqueda</label>
                <input type="text" 
                       class="form-control live-search" 
                       id="search" 
                       name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Nombre o descripción de la categoría..."
                       data-target="#categoriasTable">
            </div>
            <div class="col-md-3">
                <label for="per_page" class="form-label">Registros</label>
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 por página</option>
                    <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 por página</option>
                    <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 por página</option>
                    <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100 por página</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de categorías -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Categorías Registradas
            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($categorias)): ?>
            <div class="text-center py-5">
                <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No hay categorías registradas</h5>
                <p class="text-muted">Comience agregando la primera categoría al sistema</p>
                <a href="categoria_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Primera Categoría
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="categoriasTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Libros</th>
                            <th>Fecha Creación</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary"><?= htmlspecialchars($categoria['nombre']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($categoria['descripcion']): ?>
                                        <span class="text-truncate-2"><?= htmlspecialchars($categoria['descripcion']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin descripción</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($categoria['total_libros'] > 0): ?>
                                        <span class="badge bg-success"><?= $categoria['total_libros'] ?> libro(s)</span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin libros</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= formatDate($categoria['fecha_creacion'], 'd/m/Y') ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="categoria_detalle.php?id=<?= $categoria['id'] ?>" 
                                           class="btn btn-outline-info"
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="categoria_form.php?id=<?= $categoria['id'] ?>" 
                                           class="btn btn-outline-primary"
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($categoria['total_libros'] == 0): ?>
                                            <a href="categoria_delete.php?id=<?= $categoria['id'] ?>" 
                                               class="btn btn-outline-danger"
                                               data-bs-toggle="tooltip" 
                                               title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" 
                                                    disabled
                                                    data-bs-toggle="tooltip" 
                                                    title="No se puede eliminar: tiene libros asociados">
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
                <nav aria-label="Paginación de categorías">
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
                    de <?= number_format($total_records) ?> categorías
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>