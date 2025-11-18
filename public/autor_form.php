<?php
/**
 * Formulario de Autores - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$db = getDBConnection();
$autor = null;
$errors = [];
$success = false;
$isEdit = false;

// Si se está editando un autor existente
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEdit = true;
    $autorId = (int)$_GET['id'];
    
    $stmt = $db->prepare("SELECT * FROM autores WHERE id = ? AND activo = 1");
    $stmt->execute([$autorId]);
    $autor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$autor) {
        header('Location: autores.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        // Validar datos
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $biografia = trim($_POST['biografia'] ?? '');
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $nacionalidad = trim($_POST['nacionalidad'] ?? '');
        
        // Validaciones
        if (empty($nombre)) {
            $errors[] = 'El nombre es obligatorio.';
        } elseif (strlen($nombre) > 100) {
            $errors[] = 'El nombre no puede tener más de 100 caracteres.';
        }
        
        if (empty($apellidos)) {
            $errors[] = 'Los apellidos son obligatorios.';
        } elseif (strlen($apellidos) > 100) {
            $errors[] = 'Los apellidos no pueden tener más de 100 caracteres.';
        }
        
        if (!empty($nacionalidad) && strlen($nacionalidad) > 50) {
            $errors[] = 'La nacionalidad no puede tener más de 50 caracteres.';
        }
        
        if (!empty($fecha_nacimiento)) {
            $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
            if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha_nacimiento) {
                $errors[] = 'La fecha de nacimiento debe tener formato válido (YYYY-MM-DD).';
            } elseif ($fecha_obj > new DateTime()) {
                $errors[] = 'La fecha de nacimiento no puede ser futura.';
            }
        }
        
        // Verificar si ya existe otro autor con el mismo nombre y apellidos (excepto el actual en edición)
        $checkStmt = $db->prepare("SELECT id FROM autores WHERE nombre = ? AND apellidos = ? AND activo = 1" . 
                                 ($isEdit ? " AND id != ?" : ""));
        $params = [$nombre, $apellidos];
        if ($isEdit) {
            $params[] = $autorId;
        }
        $checkStmt->execute($params);
        
        if ($checkStmt->fetch()) {
            $errors[] = 'Ya existe un autor con el mismo nombre y apellidos.';
        }
        
        // Si no hay errores, procesar
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    // Actualizar autor existente
                    $stmt = $db->prepare("
                        UPDATE autores 
                        SET nombre = ?, apellidos = ?, biografia = ?, fecha_nacimiento = ?, nacionalidad = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $nombre,
                        $apellidos,
                        !empty($biografia) ? $biografia : null,
                        !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                        !empty($nacionalidad) ? $nacionalidad : null,
                        $autorId
                    ]);
                    
                    // Redirigir después de actualizar
                    header('Location: autores.php?updated=1');
                    exit;
                    
                } else {
                    // Crear nuevo autor
                    $stmt = $db->prepare("
                        INSERT INTO autores (nombre, apellidos, biografia, fecha_nacimiento, nacionalidad)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $nombre,
                        $apellidos,
                        !empty($biografia) ? $biografia : null,
                        !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
                        !empty($nacionalidad) ? $nacionalidad : null
                    ]);
                    
                    $success = true;
                    
                    // Redirigir después de crear
                    header('Location: autores.php?created=1');
                    exit;
                }
                
            } catch (PDOException $e) {
                $errors[] = 'Error al ' . ($isEdit ? 'actualizar' : 'crear') . ' el autor: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Autor' : 'Agregar Autor';
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <a href="autores.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Autores
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    Autor <?= $isEdit ? 'actualizado' : 'creado' ?> correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person<?= $isEdit ? '-gear' : '-plus' ?>"></i>
                                <?= htmlspecialchars($pageTitle) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" novalidate>
                                <?= csrfTokenField() ?>
                                
                                <div class="row">
                                    <!-- Nombre -->
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">
                                            Nombre <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control <?= in_array('nombre', array_column(array_filter($errors, fn($e) => strpos($e, 'nombre') !== false), 0)) ? 'is-invalid' : '' ?>"
                                               id="nombre" 
                                               name="nombre" 
                                               value="<?= htmlspecialchars($autor['nombre'] ?? $_POST['nombre'] ?? '') ?>"
                                               maxlength="100" 
                                               required>
                                        <div class="form-text">Máximo 100 caracteres</div>
                                    </div>

                                    <!-- Apellidos -->
                                    <div class="col-md-6 mb-3">
                                        <label for="apellidos" class="form-label">
                                            Apellidos <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control <?= in_array('apellidos', array_column(array_filter($errors, fn($e) => strpos($e, 'apellidos') !== false), 0)) ? 'is-invalid' : '' ?>"
                                               id="apellidos" 
                                               name="apellidos" 
                                               value="<?= htmlspecialchars($autor['apellidos'] ?? $_POST['apellidos'] ?? '') ?>"
                                               maxlength="100" 
                                               required>
                                        <div class="form-text">Máximo 100 caracteres</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Fecha de Nacimiento -->
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_nacimiento" class="form-label">
                                            Fecha de Nacimiento
                                        </label>
                                        <input type="date" 
                                               class="form-control"
                                               id="fecha_nacimiento" 
                                               name="fecha_nacimiento" 
                                               value="<?= htmlspecialchars($autor['fecha_nacimiento'] ?? $_POST['fecha_nacimiento'] ?? '') ?>"
                                               max="<?= date('Y-m-d') ?>">
                                        <div class="form-text">Opcional</div>
                                    </div>

                                    <!-- Nacionalidad -->
                                    <div class="col-md-6 mb-3">
                                        <label for="nacionalidad" class="form-label">
                                            Nacionalidad
                                        </label>
                                        <input type="text" 
                                               class="form-control"
                                               id="nacionalidad" 
                                               name="nacionalidad" 
                                               value="<?= htmlspecialchars($autor['nacionalidad'] ?? $_POST['nacionalidad'] ?? '') ?>"
                                               maxlength="50">
                                        <div class="form-text">Opcional - Máximo 50 caracteres</div>
                                    </div>
                                </div>

                                <!-- Biografía -->
                                <div class="mb-4">
                                    <label for="biografia" class="form-label">
                                        Biografía
                                    </label>
                                    <textarea class="form-control" 
                                              id="biografia" 
                                              name="biografia" 
                                              rows="5"
                                              placeholder="Información biográfica del autor..."><?= htmlspecialchars($autor['biografia'] ?? $_POST['biografia'] ?? '') ?></textarea>
                                    <div class="form-text">Opcional - Información sobre la vida y obra del autor</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="autores.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary" style="position: relative; z-index: 1000;">
                                        <i class="bi bi-<?= $isEdit ? 'save' : 'plus-circle' ?>"></i>
                                        <?= $isEdit ? 'Actualizar Autor' : 'Crear Autor' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>