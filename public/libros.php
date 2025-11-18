<?php
/**
 * Gestión de Libros - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$page_title = 'Gestión de Libros';

// Configuración de paginación
$page = (int)($_GET['page'] ?? 1);
$per_page = (int)($_GET['per_page'] ?? RECORDS_PER_PAGE);
$per_page = min(max($per_page, 5), MAX_RECORDS_PER_PAGE);
$offset = ($page - 1) * $per_page;

// Filtros de búsqueda
$search = sanitizeInput($_GET['search'] ?? '');
$categoria_id = (int)($_GET['categoria_id'] ?? 0);
$autor_id = (int)($_GET['autor_id'] ?? 0);
$disponible_filter = $_GET['disponible'] ?? '';

// Construir consulta con filtros
$where_conditions = ['l.activo = 1'];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(l.titulo LIKE ? OR l.isbn LIKE ? OR l.editorial LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($categoria_id > 0) {
    $where_conditions[] = "l.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($autor_id > 0) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM libro_autores la WHERE la.libro_id = l.id AND la.autor_id = ?)";
    $params[] = $autor_id;
}

if ($disponible_filter === 'disponible') {
    $where_conditions[] = "l.copias_disponibles > 0";
} elseif ($disponible_filter === 'agotado') {
    $where_conditions[] = "l.copias_disponibles = 0";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total FROM libros l WHERE {$where_clause}";
    $total_records = fetchOne($count_sql, $params)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Obtener libros con filtros
    $sql = "
        SELECT l.*,
               c.nombre as categoria_nombre,
               GROUP_CONCAT(CONCAT(a.nombre, ' ', a.apellidos) SEPARATOR ', ') as autores,
               (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.estado = 'activo') as prestamos_activos
        FROM libros l
        LEFT JOIN categorias c ON l.categoria_id = c.id
        LEFT JOIN libro_autores la ON l.id = la.libro_id
        LEFT JOIN autores a ON la.autor_id = a.id
        WHERE {$where_clause}
        GROUP BY l.id
        ORDER BY l.titulo ASC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $libros = fetchAll($sql, $params);
    
    // Obtener categorías para el filtro
    $categorias = fetchAll("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
    
    // Obtener autores para el filtro
    $autores = fetchAll("SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo FROM autores WHERE activo = 1 ORDER BY nombre, apellidos");
    
} catch (Exception $e) {
    error_log("Error en listado de libros: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar el listado de libros');
    $libros = [];
    $categorias = [];
    $autores = [];
    $total_records = 0;
    $total_pages = 1;
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-book"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">Gestiona el catálogo de libros de la biblioteca</p>
    </div>
    <div>
        <a href="libro_form.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Agregar Libro
        </a>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Búsqueda</label>
                <input type="text" 
                       class="form-control live-search" 
                       id="search" 
                       name="search"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Título, ISBN o editorial..."
                       data-target="#librosTable">
            </div>
            
            <div class="col-md-3">
                <label for="categoria_id" class="form-label">Categoría</label>
                <select class="form-select" id="categoria_id" name="categoria_id">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= $categoria['id'] ?>" <?= $categoria_id == $categoria['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="autor_id" class="form-label">Autor</label>
                <select class="form-select" id="autor_id" name="autor_id">
                    <option value="">Todos los autores</option>
                    <?php foreach ($autores as $autor): ?>
                        <option value="<?= $autor['id'] ?>" <?= $autor_id == $autor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($autor['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="disponible" class="form-label">Disponibilidad</label>
                <select class="form-select" id="disponible" name="disponible">
                    <option value="">Todos</option>
                    <option value="disponible" <?= $disponible_filter === 'disponible' ? 'selected' : '' ?>>Disponibles</option>
                    <option value="agotado" <?= $disponible_filter === 'agotado' ? 'selected' : '' ?>>Agotados</option>
                </select>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="libros.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Limpiar
                </a>
                
                <div class="float-end">
                    <select name="per_page" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                        <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10 por página</option>
                        <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25 por página</option>
                        <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50 por página</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de libros -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Libros Registrados
            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($libros)): ?>
            <div class="text-center py-5">
                <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No hay libros registrados</h5>
                <p class="text-muted">Comience agregando el primer libro al catálogo</p>
                <a href="libro_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Primer Libro
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="librosTable">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Autores</th>
                            <th>Categoría</th>
                            <th>ISBN</th>
                            <th>Editorial</th>
                            <th>Año</th>
                            <th>Copias</th>
                            <th>Disponibles</th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($libros as $libro): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($libro['titulo']) ?></strong>
                                    <?php if (!empty($libro['subtitulo'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($libro['subtitulo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($libro['autores'] ?? 'Sin autor') ?></td>
                                <td>
                                    <?php if ($libro['categoria_nombre']): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($libro['categoria_nombre']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin categoría</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($libro['isbn']): ?>
                                        <code><?= htmlspecialchars($libro['isbn']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">Sin ISBN</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($libro['editorial']) ?></td>
                                <td><?= $libro['año_publicacion'] ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= $libro['copias_totales'] ?></span>
                                </td>
                                <td>
                                    <?php if ($libro['copias_disponibles'] > 0): ?>
                                        <span class="badge bg-success"><?= $libro['copias_disponibles'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($libro['prestamos_activos'] > 0): ?>
                                        <span class="status-activo">
                                            <i class="bi bi-circle-fill"></i> En préstamo (<?= $libro['prestamos_activos'] ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="status-devuelto">
                                            <i class="bi bi-circle-fill"></i> Disponible
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="libro_detalle.php?id=<?= $libro['id'] ?>" 
                                           class="btn btn-outline-info"
                                           data-bs-toggle="tooltip" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="libro_form.php?id=<?= $libro['id'] ?>" 
                                           class="btn btn-outline-primary"
                                           data-bs-toggle="tooltip" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($libro['prestamos_activos'] == 0): ?>
                                            <a href="libro_delete.php?id=<?= $libro['id'] ?>" 
                                               class="btn btn-outline-danger btn-delete"
                                               data-bs-toggle="tooltip" 
                                               title="Eliminar"
                                               data-confirm="¿Está seguro de que desea eliminar este libro?">
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
                <nav aria-label="Paginación de libros">
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
                    de <?= number_format($total_records) ?> libros
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>