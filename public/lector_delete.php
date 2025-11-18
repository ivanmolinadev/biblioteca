<?php
require_once '../config/init.php';

requireAuth(true);

$db = getDBConnection();
$errors = [];
$lector = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lectores.php');
    exit;
}
$id = (int)$_GET['id'];

try {
    $stmt = $db->prepare('SELECT id, nombre, apellidos, dni FROM lectores WHERE id = ?');
    $stmt->execute([$id]);
    $lector = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lector) {
        header('Location: lectores.php');
        exit;
    }
    // Verificar préstamos activos
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM prestamos WHERE lector_id = ? AND estado IN ("activo", "atrasado")');
    $stmt->execute([$id]);
    $prestamos = $stmt->fetch(PDO::FETCH_ASSOC);
    $tienePrestamos = $prestamos['total'] > 0;
} catch (PDOException $e) {
    $errors[] = 'Error al obtener información del lector: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } elseif ($tienePrestamos) {
        $errors[] = 'No se puede eliminar: el lector tiene préstamos activos.';
    } else {
        try {
            $stmt = $db->prepare('DELETE FROM lectores WHERE id = ?');
            $stmt->execute([$id]);
            setFlashMessage('success', 'Lector eliminado correctamente.');
            header('Location: lectores.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error al eliminar el lector: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Eliminar Lector';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card border-danger mt-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-x"></i> Eliminar Lector
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        ¿Estás seguro de que deseas eliminar este lector?
                        <ul class="mb-0 mt-2">
                            <li><strong>Nombre:</strong> <?= htmlspecialchars($lector['nombre'] . ' ' . $lector['apellidos']) ?></li>
                            <li><strong>ID:</strong> <?= htmlspecialchars($lector['dni']) ?></li>
                        </ul>
                        <span class="text-danger">Esta acción no se puede deshacer.</span>
                    </div>
                    <?php if ($tienePrestamos): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-lock"></i> No se puede eliminar: el lector tiene préstamos activos.
                        </div>
                        <a href="lectores.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    <?php else: ?>
                        <form method="POST" action="">
                            <?= csrfTokenField() ?>
                            <div class="d-flex justify-content-between">
                                <a href="lectores.php" class="btn btn-secondary">
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
<?php include '../includes/footer.php'; ?>
