<?php
require_once '../config/init.php';

requireAuth(true);

$db = getDBConnection();
$categoria = null;
$errors = [];
$librosAsociados = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: categorias.php');
    exit;
}
$categoriaId = (int)$_GET['id'];

try {
    $stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM libros l WHERE l.categoria_id = c.id) as total_libros FROM categorias c WHERE c.id = ? AND c.activo = 1");
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$categoria) {
        header('Location: categorias.php');
        exit;
    }
    if ($categoria['total_libros'] > 0) {
        $stmt = $db->prepare("SELECT id, titulo, isbn FROM libros WHERE categoria_id = ? AND activo = 1");
        $stmt->execute([$categoriaId]);
        $librosAsociados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errors[] = "Error al obtener información de la categoría: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        try {
            $db->beginTransaction();
            // Verificar que no hay libros asociados
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM libros WHERE categoria_id = ? AND activo = 1");
            $stmt->execute([$categoriaId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['total'] > 0) {
                throw new Exception('No se puede eliminar: existen libros asociados a esta categoría.');
            }
            // Eliminación lógica
            $stmt = $db->prepare("UPDATE categorias SET activo = 0 WHERE id = ?");
            $stmt->execute([$categoriaId]);
            $db->commit();
            header('Location: categorias.php?deleted=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Error al eliminar la categoría: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Eliminar Categoría';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-tags text-danger"></i> Eliminar Categoría</h1>
                <a href="categorias.php" class="btn btn-secondary" style="position: relative; z-index: 1000;">
                    <i class="bi bi-arrow-left"></i> Volver a Categorías
                </a>
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
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación de Categoría
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning" role="alert">
                                <i class="bi bi-info-circle"></i>
                                <strong>¿Estás seguro de que deseas eliminar esta categoría?</strong><br>
                                Esta acción no se puede deshacer.
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li><strong>Nombre:</strong> <?= htmlspecialchars($categoria['nombre']) ?></li>
                                <?php if ($categoria['descripcion']): ?>
                                    <li><strong>Descripción:</strong> <?= htmlspecialchars($categoria['descripcion']) ?></li>
                                <?php endif; ?>
                            </ul>
                            <?php if (!empty($librosAsociados)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-book"></i> Esta categoría tiene <?= count($librosAsociados) ?> libro(s) asociado(s):
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($librosAsociados as $libro): ?>
                                            <li><?= htmlspecialchars($libro['titulo']) ?> (ISBN: <?= htmlspecialchars($libro['isbn']) ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <div class="mt-3">
                                        <span class="text-danger"><i class="bi bi-lock"></i> No se puede eliminar mientras existan libros asociados.</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="" class="mt-4">
                                    <?= csrfTokenField() ?>
                                    <div class="d-flex justify-content-between">
                                        <a href="categorias.php" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-danger" style="position: relative; z-index: 1000;">
                                            <i class="bi bi-trash"></i> Confirmar Eliminación
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
