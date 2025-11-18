<?php
/**
 * Formulario de Libros - Sistema de Biblioteca y Préstamos
 * Versión final completa y funcional
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$is_edit = isset($_GET['id']) && !empty($_GET['id']);
$libro_id = $is_edit ? (int)$_GET['id'] : 0;
$page_title = $is_edit ? 'Editar Libro' : 'Agregar Libro';

// Inicializar datos del libro
$libro = [
    'isbn' => '',
    'titulo' => '',
    'año_publicacion' => date('Y'),
    'editorial' => '',
    'categoria_id' => 0,
    'copias_totales' => 1,
    'autores' => []
];

// Si es edición, cargar datos del libro
if ($is_edit) {
    try {
        $libro_data = fetchOne("
            SELECT l.isbn, l.titulo, l.año_publicacion, l.editorial, l.categoria_id, 
                   l.copias_totales, GROUP_CONCAT(la.autor_id) as autor_ids
            FROM libros l
            LEFT JOIN libro_autores la ON l.id = la.libro_id
            WHERE l.id = ? AND l.activo = 1
            GROUP BY l.id
        ", [$libro_id]);
        
        if (!$libro_data) {
            setFlashMessage('error', 'El libro no existe o ha sido eliminado');
            redirect('libros.php');
        }
        
        $libro = [
            'isbn' => $libro_data['isbn'] ?? '',
            'titulo' => $libro_data['titulo'] ?? '',
            'año_publicacion' => $libro_data['año_publicacion'] ?? date('Y'),
            'editorial' => $libro_data['editorial'] ?? '',
            'categoria_id' => $libro_data['categoria_id'] ?? 0,
            'copias_totales' => $libro_data['copias_totales'] ?? 1,
            'autores' => $libro_data['autor_ids'] ? explode(',', $libro_data['autor_ids']) : []
        ];
        
    } catch (Exception $e) {
        error_log("Error al cargar libro: " . $e->getMessage());
        setFlashMessage('error', 'Error al cargar los datos del libro');
        redirect('libros.php');
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        // Procesar datos del formulario
        $isbn = sanitizeInput($_POST['isbn'] ?? '');
        $titulo = sanitizeInput($_POST['titulo'] ?? '');
        $año_publicacion = (int)($_POST['año_publicacion'] ?? date('Y'));
        $editorial = sanitizeInput($_POST['editorial'] ?? '');
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $copias_totales = (int)($_POST['copias_totales'] ?? 1);
        $autores_seleccionados = $_POST['autores'] ?? [];
        
        // Validaciones
        if (empty($titulo)) {
            setFlashMessage('error', 'El título es obligatorio');
        } else {
            // Insertar o actualizar libro
            if ($is_edit) {
                $sql = "UPDATE libros SET isbn = ?, titulo = ?, año_publicacion = ?, editorial = ?, categoria_id = ?, copias_totales = ? WHERE id = ?";
                $params = [$isbn ?: null, $titulo, $año_publicacion, $editorial ?: null, $categoria_id ?: null, $copias_totales, $libro_id];
                executeQuery($sql, $params);
                $action = 'actualizado';
                
                // En edición, eliminar relaciones anteriores de autores
                executeQuery("DELETE FROM libro_autores WHERE libro_id = ?", [$libro_id]);
            } else {
                $sql = "INSERT INTO libros (isbn, titulo, año_publicacion, editorial, categoria_id, copias_totales, copias_disponibles) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [$isbn ?: null, $titulo, $año_publicacion, $editorial ?: null, $categoria_id ?: null, $copias_totales, $copias_totales];
                executeQuery($sql, $params);
                
                // Obtener el ID del libro recién insertado
                $pdo = getDbConnection();
                $libro_id = $pdo->lastInsertId();
                $action = 'creado';
            }
            
            // Procesar relaciones con autores
            if (!empty($autores_seleccionados)) {
                foreach ($autores_seleccionados as $autor_id) {
                    $autor_id = (int)$autor_id;
                    if ($autor_id > 0) {
                        executeQuery("INSERT INTO libro_autores (libro_id, autor_id) VALUES (?, ?)", [$libro_id, $autor_id]);
                    }
                }
            }
            
            setFlashMessage('success', "Libro {$action} correctamente");
            redirect('libros.php');
        }
        
    } catch (Exception $e) {
        error_log("Error al procesar libro: " . $e->getMessage());
        setFlashMessage('error', 'Error al procesar el libro: ' . $e->getMessage());
    }
}

// Obtener datos para los selects
try {
    $categorias = fetchAll("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
    $autores = fetchAll("SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo FROM autores WHERE activo = 1 ORDER BY apellidos, nombre");
} catch (Exception $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
    $categorias = [];
    $autores = [];
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">
            <i class="bi bi-book"></i> <?= $page_title ?>
        </h1>
        <p class="text-muted mb-0">
            <?= $is_edit ? 'Edita la información del libro seleccionado' : 'Completa los datos para agregar un nuevo libro' ?>
        </p>
    </div>
    <div>
        <a href="libros.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?= generateCSRFToken() ?>
                    
                    <!-- Información básica -->
                    <h5 class="card-title mb-3">
                        <i class="bi bi-info-circle"></i> Información básica
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" 
                                   value="<?= htmlspecialchars($libro['isbn']) ?>" 
                                   placeholder="Ej: 978-84-376-0494-7">
                        </div>
                        <div class="col-md-6">
                            <label for="titulo" class="form-label">Título *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?= htmlspecialchars($libro['titulo']) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="año_publicacion" class="form-label">Año de publicación</label>
                            <input type="number" class="form-control" id="año_publicacion" name="año_publicacion" 
                                   value="<?= $libro['año_publicacion'] ?>" min="1000" max="<?= date('Y') + 1 ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="editorial" class="form-label">Editorial</label>
                            <input type="text" class="form-control" id="editorial" name="editorial" 
                                   value="<?= htmlspecialchars($libro['editorial']) ?>" 
                                   placeholder="Ej: Penguin Random House">
                        </div>
                        <div class="col-md-4">
                            <label for="categoria_id" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" 
                                            <?= $libro['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="copias_totales" class="form-label">Copias totales *</label>
                            <input type="number" class="form-control" id="copias_totales" name="copias_totales" 
                                   value="<?= $libro['copias_totales'] ?>" min="1" required>
                        </div>
                    </div>
                    
                    <!-- Autores -->
                    <h5 class="card-title mb-3 mt-4">
                        <i class="bi bi-people"></i> Autores
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="autores" class="form-label">Seleccionar autores</label>
                            <select class="form-select" id="autores" name="autores[]" multiple size="6">
                                <?php foreach ($autores as $autor): ?>
                                    <option value="<?= $autor['id'] ?>" 
                                            <?= in_array($autor['id'], $libro['autores']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($autor['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Mantén presionado Ctrl (o Cmd en Mac) para seleccionar múltiples autores
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="libros.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <?= $is_edit ? 'Actualizar' : 'Guardar' ?> Libro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>