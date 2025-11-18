<?php
require_once '../config/init.php';

requireAuth();

$db = getDBConnection();
$categoria = null;
$librosAsociados = [];
$errors = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: categorias.php');
    exit;
}
$categoriaId = (int)$_GET['id'];

try {
    $stmt = $db->prepare("SELECT * FROM categorias WHERE id = ? AND activo = 1");
    $stmt->execute([$categoriaId]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$categoria) {
        header('Location: categorias.php');
        exit;
    }
    // Libros asociados
    $stmt = $db->prepare("SELECT id, titulo, isbn FROM libros WHERE categoria_id = ? AND activo = 1");
    $stmt->execute([$categoriaId]);
    $librosAsociados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error al obtener información de la categoría: " . $e->getMessage();
}

$pageTitle = 'Detalle de Categoría';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="bi bi-tag text-primary"></i> Detalle de Categoría</h1>
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
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle"></i> Información de la Categoría
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-4">
                                <li><strong>Nombre:</strong> <?= htmlspecialchars($categoria['nombre']) ?></li>
                                <?php if ($categoria['descripcion']): ?>
                                    <li><strong>Descripción:</strong> <?= htmlspecialchars($categoria['descripcion']) ?></li>
                                <?php endif; ?>
                            </ul>
                            <h6 class="mt-4"><i class="bi bi-book"></i> Libros Asociados</h6>
                            <?php if (!empty($librosAsociados)): ?>
                                <ul class="mb-0">
                                    <?php foreach ($librosAsociados as $libro): ?>
                                        <li><?= htmlspecialchars($libro['titulo']) ?> (ISBN: <?= htmlspecialchars($libro['isbn']) ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info mt-2 mb-0">
                                    <i class="bi bi-info-circle"></i> No hay libros asociados a esta categoría.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
