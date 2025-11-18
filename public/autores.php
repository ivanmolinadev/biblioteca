<?php
/**
 * Gestión de Autores - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$page_title = 'Gestión de Autores';

// Configuración de paginación
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? RECORDS_PER_PAGE);
$per_page = min(max($per_page, 5), MAX_RECORDS_PER_PAGE);
$offset = ($page - 1) * $per_page;

// Filtros de búsqueda
$search = sanitizeInput($_GET['search'] ?? '');

// Construir consulta con filtros
$where_conditions = ['a.activo = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(a.nombre LIKE ? OR a.apellidos LIKE ? OR a.nacionalidad LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total FROM autores a WHERE {$where_clause}";
    $total_records = fetchOne($count_sql, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener autores con filtros
    $sql = "
        SELECT a.*,
               (SELECT COUNT(*) FROM libro_autores la WHERE la.autor_id = a.id) as total_libros
        FROM autores a
        WHERE {$where_clause}
        ORDER BY a.nombre ASC, a.apellidos ASC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $autores = fetchAll($sql, $params);
    
} catch (Exception $e) {
    error_log("Error en listado de autores: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar el listado de autores');
    $autores = [];
    $total_records = 0;
    $total_pages = 1;
}

include '../includes/header.php';
?>

<!-- Notificaciones de éxito -->
<?php if (isset($_GET['created']) && $_GET['created'] == '1'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <strong>¡Éxito!</strong> El autor ha sido creado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <strong>¡Éxito!</strong> El autor ha sido actualizado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <strong>¡Éxito!</strong> El autor ha sido eliminado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-person-lines-fill"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">Gestiona los autores registrados en el sistema</p>
    </div>
    <div>
        <a href="autor_form.php" class="btn btn-primary" style="position: relative; z-index: 1000;">
            <i class="bi bi-plus-circle"></i> Agregar Autor
        </a>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Búsqueda</label>
                <input type="text" 
                       class="form-control live-search" 
                       id="search" 
                       name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Nombre, apellidos o nacionalidad..."
                       data-target="#autoresTable">
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <div class="me-2">
                    <button type="submit" class="btn btn-outline-primary" style="position: relative; z-index: 1000;">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
                <div class="me-2">
                    <a href="autores.php" class="btn btn-outline-secondary" style="position: relative; z-index: 1000;">
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

<!-- Tabla de autores -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Autores Registrados
            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($autores)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-lines-fill text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No hay autores registrados</h5>
                <p class="text-muted">Comience agregando el primer autor al sistema</p>
                <a href="autor_form.php" class="btn btn-primary" style="position: relative; z-index: 1000;">
                    <i class="bi bi-plus-circle"></i> Agregar Primer Autor
                </a>
            </div>
        <?php else: ?>
            <!-- Información para el usuario -->
            <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <div>
                    <strong>Información sobre eliminación:</strong> Los autores con <i class="bi bi-lock text-muted"></i> 
                    no se pueden eliminar porque tienen libros asociados. Primero elimine los libros correspondientes.
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="autoresTable">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Fecha Nacimiento</th>
                            <th>Nacionalidad</th>
                            <th>Libros</th>
                            <th>Registro</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($autores as $autor): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($autor['nombre'] . ' ' . $autor['apellidos']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($autor['fecha_nacimiento']): ?>
                                        <?= formatDate($autor['fecha_nacimiento']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No especificada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($autor['nacionalidad']): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($autor['nacionalidad']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">No especificada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($autor['total_libros'] > 0): ?>
                                        <span class="badge bg-success"><?= $autor['total_libros'] ?> libro(s)</span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin libros</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= formatDate($autor['fecha_creacion'], 'd/m/Y') ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="autor_detalle.php?id=<?= $autor['id'] ?>" 
                                           class="btn btn-outline-info"
                                           style="position: relative; z-index: 1000;"
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="autor_form.php?id=<?= $autor['id'] ?>" 
                                           class="btn btn-outline-primary"
                                           style="position: relative; z-index: 1000;"
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($autor['total_libros'] == 0): ?>
                                            <a href="autor_delete.php?id=<?= $autor['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete"
                                               style="position: relative; z-index: 1000;"
                                               data-bs-toggle="tooltip" 
                                               title="Eliminar autor"
                                               data-confirm="¿Está seguro de que desea eliminar este autor?">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" 
                                                    disabled
                                                    style="position: relative; z-index: 1000;"
                                                    data-bs-toggle="tooltip" 
                                                    title="No se puede eliminar: tiene <?= $autor['total_libros'] ?> libro(s) asociado(s). Primero elimine los libros.">
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
                <nav aria-label="Paginación de autores">
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
                    de <?= number_format($total_records) ?> autores
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>