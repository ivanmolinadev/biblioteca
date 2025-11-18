<?php
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$db = getDBConnection();
$categoria = null;
$errors = [];
$success = false;
$isEdit = false;

// Si se está editando una categoría existente
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEdit = true;
    $categoriaId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM categorias WHERE id = ? AND activo = 1");
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$categoria) {
        header('Location: categorias.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        if (empty($nombre)) {
            $errors[] = 'El nombre es obligatorio.';
        } elseif (strlen($nombre) > 100) {
            $errors[] = 'El nombre no puede tener más de 100 caracteres.';
        }
        if (!empty($descripcion) && strlen($descripcion) > 255) {
            $errors[] = 'La descripción no puede tener más de 255 caracteres.';
        }
        // Verificar duplicados
        $checkStmt = $db->prepare("SELECT id FROM categorias WHERE nombre = ? AND activo = 1" . ($isEdit ? " AND id != ?" : ""));
        $params = [$nombre];
        if ($isEdit) {
            $params[] = $categoriaId;
        }
        $checkStmt->execute($params);
        if ($checkStmt->fetch()) {
            $errors[] = 'Ya existe una categoría con ese nombre.';
        }
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $stmt = $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?");
                    $stmt->execute([
                        $nombre,
                        !empty($descripcion) ? $descripcion : null,
                        $categoriaId
                    ]);
                    header('Location: categorias.php?updated=1');
                    exit;
                } else {
                    $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
                    $stmt->execute([
                        $nombre,
                        !empty($descripcion) ? $descripcion : null
                    ]);
                    header('Location: categorias.php?created=1');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Error al guardar la categoría: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Categoría' : 'Agregar Categoría';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
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
                <div class="col-xl-6 col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-tags"></i> <?= htmlspecialchars($pageTitle) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" novalidate>
                                <?= csrfTokenField() ?>
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" required
                                           value="<?= htmlspecialchars($categoria['nombre'] ?? $_POST['nombre'] ?? '') ?>">
                                    <div class="form-text">Máximo 100 caracteres</div>
                                </div>
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" maxlength="255" rows="3"
                                              placeholder="Descripción de la categoría (opcional)"><?= htmlspecialchars($categoria['descripcion'] ?? $_POST['descripcion'] ?? '') ?></textarea>
                                    <div class="form-text">Opcional - Máximo 255 caracteres</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="categorias.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary" style="position: relative; z-index: 1000;">
                                        <i class="bi bi-<?= $isEdit ? 'save' : 'plus-circle' ?>"></i>
                                        <?= $isEdit ? 'Actualizar Categoría' : 'Crear Categoría' ?>
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
