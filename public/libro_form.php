<?php
/**
 * Formulario de Libros - Sistema de Biblioteca y Préstamos
 * Maneja tanto creación como edición de libros
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$page_title = 'Gestión de Libros';
$is_edit = isset($_GET['id']) && !empty($_GET['id']);
$libro_id = $is_edit ? (int)$_GET['id'] : 0;

// Inicializar datos del libro
$libro = [
    'id' => 0,
    'isbn' => '',
    'titulo' => '',
    'subtitulo' => '',
    'año_publicacion' => date('Y'),
    'editorial' => '',
    'numero_paginas' => '',
    'idioma' => 'Español',
    'descripcion' => '',
    'categoria_id' => 0,
    'copias_totales' => 1,
    'copias_disponibles' => 1,
    'ubicacion' => '',
    'autores' => []
];

// Si es edición, cargar datos del libro
if ($is_edit) {
    try {
        $libro_data = fetchOne("
            SELECT l.*, 
                   GROUP_CONCAT(la.autor_id) as autor_ids
            FROM libros l
            LEFT JOIN libro_autores la ON l.id = la.libro_id
            WHERE l.id = ? AND l.activo = 1
            GROUP BY l.id
        ", [$libro_id]);
        
        if (!$libro_data) {
            setFlashMessage('error', 'El libro no existe o ha sido eliminado');
            redirect('libros.php');
        }
        
        $libro = array_merge($libro, $libro_data);
        $libro['autores'] = $libro_data['autor_ids'] ? explode(',', $libro_data['autor_ids']) : [];
        
        $page_title = 'Editar Libro';
    } catch (Exception $e) {
        error_log("Error al cargar libro: " . $e->getMessage());
        setFlashMessage('error', 'Error al cargar los datos del libro');
        redirect('libros.php');
    }
} else {
    $page_title = 'Agregar Libro';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        // Sanitizar y validar datos
        $isbn = sanitizeInput($_POST['isbn'] ?? '');
        $titulo = sanitizeInput($_POST['titulo'] ?? '');
        $subtitulo = sanitizeInput($_POST['subtitulo'] ?? '');
        $año_publicacion = (int)($_POST['año_publicacion'] ?? date('Y'));
        $editorial = sanitizeInput($_POST['editorial'] ?? '');
        $numero_paginas = (int)($_POST['numero_paginas'] ?? 0);
        $idioma = sanitizeInput($_POST['idioma'] ?? 'Español');
        $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $copias_totales = (int)($_POST['copias_totales'] ?? 1);
        $ubicacion = sanitizeInput($_POST['ubicacion'] ?? '');
        $autores_seleccionados = $_POST['autores'] ?? [];
        
        // Validaciones
        $errores = [];
        
        if (empty($titulo)) {
            $errores[] = 'El título es obligatorio';
        }
        
        if (!empty($isbn)) {
            // Verificar ISBN único (excepto en edición del mismo libro)
            $isbn_check = fetchOne("SELECT id FROM libros WHERE isbn = ? AND id != ?", [$isbn, $libro_id]);
            if ($isbn_check) {
                $errores[] = 'El ISBN ya está registrado en otro libro';
            }
        }
        
        if ($año_publicacion < 1000 || $año_publicacion > (date('Y') + 1)) {
            $errores[] = 'El año de publicación no es válido';
        }
        
        if ($copias_totales < 1) {
            $errores[] = 'Debe tener al menos 1 copia total';
        }
        
        if ($categoria_id > 0) {
            $categoria_exists = fetchOne("SELECT id FROM categorias WHERE id = ? AND activo = 1", [$categoria_id]);
            if (!$categoria_exists) {
                $errores[] = 'La categoría seleccionada no es válida';
            }
        }
        
        // Validar autores seleccionados
        if (!empty($autores_seleccionados)) {
            $autores_placeholders = str_repeat('?,', count($autores_seleccionados) - 1) . '?';
            $autores_validos = fetchAll("SELECT id FROM autores WHERE id IN ($autores_placeholders) AND activo = 1", $autores_seleccionados);
            if (count($autores_validos) !== count($autores_seleccionados)) {
                $errores[] = 'Algunos autores seleccionados no son válidos';
            }
        }
        
        if (!empty($errores)) {
            setFlashMessage('error', implode('<br>', $errores));
        } else {
            // Calcular copias disponibles para nuevo libro o ajustar si es necesario
            if ($is_edit) {
                // En edición, mantener la diferencia actual o ajustar si es necesario
                $libro_actual = fetchOne("SELECT copias_totales, copias_disponibles FROM libros WHERE id = ?", [$libro_id]);
                $copias_prestadas = $libro_actual['copias_totales'] - $libro_actual['copias_disponibles'];
                $copias_disponibles = max(0, $copias_totales - $copias_prestadas);
            } else {
                // Nuevo libro: todas las copias están disponibles
                $copias_disponibles = $copias_totales;
            }
            
            beginTransaction();
            
            if ($is_edit) {
                // Actualizar libro existente
                $sql = "
                    UPDATE libros SET 
                        isbn = ?, titulo = ?, subtitulo = ?, año_publicacion = ?, 
                        editorial = ?, numero_paginas = ?, idioma = ?, descripcion = ?,
                        categoria_id = ?, copias_totales = ?, copias_disponibles = ?, 
                        ubicacion = ?
                    WHERE id = ?
                ";
                
                $params = [
                    $isbn ?: null, $titulo, $subtitulo ?: null, $año_publicacion,
                    $editorial ?: null, $numero_paginas ?: null, $idioma, $descripcion ?: null,
                    $categoria_id ?: null, $copias_totales, $copias_disponibles, $ubicacion ?: null,
                    $libro_id
                ];
                
                executeQuery($sql, $params);
                
                // Eliminar relaciones de autores existentes
                executeQuery("DELETE FROM libro_autores WHERE libro_id = ?", [$libro_id]);
                
                $action = 'actualizado';
            } else {
                // Crear nuevo libro
                $sql = "
                    INSERT INTO libros (
                        isbn, titulo, subtitulo, año_publicacion, editorial, 
                        numero_paginas, idioma, descripcion, categoria_id,
                        copias_totales, copias_disponibles, ubicacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $params = [
                    $isbn ?: null, $titulo, $subtitulo ?: null, $año_publicacion,
                    $editorial ?: null, $numero_paginas ?: null, $idioma, $descripcion ?: null,
                    $categoria_id ?: null, $copias_totales, $copias_disponibles, $ubicacion ?: null
                ];
                
                $libro_id = executeQuery($sql, $params, true);
                $action = 'creado';
            }
            
            // Insertar nuevas relaciones con autores
            if (!empty($autores_seleccionados)) {
                $sql = "INSERT INTO libro_autores (libro_id, autor_id) VALUES ";
                $values = [];
                $params = [];
                
                foreach ($autores_seleccionados as $autor_id) {
                    $values[] = "(?, ?)";
                    $params[] = $libro_id;
                    $params[] = $autor_id;
                }
                
                $sql .= implode(', ', $values);
                executeQuery($sql, $params);
            }
            
            commitTransaction();
            
            setFlashMessage('success', "Libro {$action} correctamente");
            redirect('libros.php');
        }
        
    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Error al procesar libro: " . $e->getMessage());
        setFlashMessage('error', 'Error al procesar el libro. Intente nuevamente.');
    }
}

// Obtener datos para selects
try {
    $categorias = fetchAll("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
    $autores = fetchAll("
        SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo 
        FROM autores 
        WHERE activo = 1 
        ORDER BY apellidos, nombre
    ");
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

<!-- Formulario de libro -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <?= generateCSRFToken() ?>
                    
                    <!-- Información básica -->
                    <h5 class="card-title mb-3">
                        <i class="bi bi-info-circle"></i> Información básica
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="titulo" class="form-label">
                                Título <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="titulo" 
                                   name="titulo" 
                                   value="<?= htmlspecialchars($libro['titulo']) ?>"
                                   required
                                   maxlength="200">
                        </div>
                        <div class="col-md-4">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="isbn" 
                                   name="isbn" 
                                   value="<?= htmlspecialchars($libro['isbn']) ?>"
                                   maxlength="20"
                                   placeholder="978-84-1234-567-8">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subtitulo" class="form-label">Subtítulo</label>
                        <input type="text" 
                               class="form-control" 
                               id="subtitulo" 
                               name="subtitulo" 
                               value="<?= htmlspecialchars($libro['subtitulo']) ?>"
                               maxlength="200">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="año_publicacion" class="form-label">Año de publicación</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="año_publicacion" 
                                   name="año_publicacion" 
                                   value="<?= $libro['año_publicacion'] ?>"
                                   min="1000" 
                                   max="<?= date('Y') + 1 ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="numero_paginas" class="form-label">Número de páginas</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="numero_paginas" 
                                   name="numero_paginas" 
                                   value="<?= $libro['numero_paginas'] ?>"
                                   min="1">
                        </div>
                        <div class="col-md-4">
                            <label for="idioma" class="form-label">Idioma</label>
                            <select class="form-select" id="idioma" name="idioma">
                                <option value="Español" <?= $libro['idioma'] === 'Español' ? 'selected' : '' ?>>Español</option>
                                <option value="Inglés" <?= $libro['idioma'] === 'Inglés' ? 'selected' : '' ?>>Inglés</option>
                                <option value="Francés" <?= $libro['idioma'] === 'Francés' ? 'selected' : '' ?>>Francés</option>
                                <option value="Italiano" <?= $libro['idioma'] === 'Italiano' ? 'selected' : '' ?>>Italiano</option>
                                <option value="Portugués" <?= $libro['idioma'] === 'Portugués' ? 'selected' : '' ?>>Portugués</option>
                                <option value="Alemán" <?= $libro['idioma'] === 'Alemán' ? 'selected' : '' ?>>Alemán</option>
                                <option value="Otro" <?= !in_array($libro['idioma'], ['Español', 'Inglés', 'Francés', 'Italiano', 'Portugués', 'Alemán']) ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editorial" class="form-label">Editorial</label>
                        <input type="text" 
                               class="form-control" 
                               id="editorial" 
                               name="editorial" 
                               value="<?= htmlspecialchars($libro['editorial']) ?>"
                               maxlength="100">
                    </div>
                    
                    <!-- Categorización -->
                    <hr class="my-4">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-tags"></i> Categorización
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="categoria_id" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" 
                                            <?= $libro['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ubicacion" class="form-label">Ubicación en biblioteca</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="ubicacion" 
                                   name="ubicacion" 
                                   value="<?= htmlspecialchars($libro['ubicacion']) ?>"
                                   placeholder="Ej: Estante A-3, Sección Historia"
                                   maxlength="100">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="autores" class="form-label">Autores</label>
                        <select class="form-select" id="autores" name="autores[]" multiple size="6">
                            <?php foreach ($autores as $autor): ?>
                                <option value="<?= $autor['id'] ?>" 
                                        <?= in_array($autor['id'], $libro['autores']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($autor['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Mantén presionado Ctrl (Cmd en Mac) para seleccionar múltiples autores
                        </div>
                    </div>
                    
                    <!-- Inventario -->
                    <hr class="my-4">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-box"></i> Inventario
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="copias_totales" class="form-label">
                                Copias totales <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="copias_totales" 
                                   name="copias_totales" 
                                   value="<?= $libro['copias_totales'] ?>"
                                   min="1" 
                                   required>
                        </div>
                        <?php if ($is_edit): ?>
                        <div class="col-md-6">
                            <label class="form-label">Copias disponibles</label>
                            <div class="form-control-plaintext">
                                <strong><?= $libro['copias_disponibles'] ?></strong>
                                <small class="text-muted d-block">
                                    (Se ajustará automáticamente según préstamos activos)
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="4"
                                  placeholder="Resumen, sinopsis o descripción del libro..."><?= htmlspecialchars($libro['descripcion']) ?></textarea>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> 
                            <?= $is_edit ? 'Actualizar libro' : 'Agregar libro' ?>
                        </button>
                        <a href="libros.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para mejorar la experiencia -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación en tiempo real del ISBN
    const isbnInput = document.getElementById('isbn');
    if (isbnInput) {
        isbnInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d-X]/gi, '');
            this.value = value;
        });
    }
    
    // Ajustar copias disponibles automáticamente
    const copiasTotalesInput = document.getElementById('copias_totales');
    if (copiasTotalesInput && <?= $is_edit ? 'true' : 'false' ?>) {
        copiasTotalesInput.addEventListener('change', function() {
            const copiasActuales = <?= $libro['copias_disponibles'] ?>;
            const copiasPrestadas = <?= $libro['copias_totales'] - $libro['copias_disponibles'] ?>;
            const nuevasDisponibles = Math.max(0, parseInt(this.value) - copiasPrestadas);
            
            const availableText = document.querySelector('.col-md-6 .form-control-plaintext strong');
            if (availableText) {
                availableText.textContent = nuevasDisponibles;
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>