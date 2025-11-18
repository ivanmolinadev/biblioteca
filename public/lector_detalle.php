<?php
require_once '../config/init.php';

requireAuth(true);

$db = getDBConnection();
$lector = null;
$errors = [];
$prestamos = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lectores.php');
    exit;
}
$id = (int)$_GET['id'];

try {
    $stmt = $db->prepare('SELECT * FROM lectores WHERE id = ?');
    $stmt->execute([$id]);
    $lector = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lector) {
        header('Location: lectores.php');
        exit;
    }
    // Préstamos del lector
    $stmt = $db->prepare('SELECT p.id, p.fecha_prestamo, p.fecha_devolucion, p.estado, l.titulo FROM prestamos p JOIN libros l ON p.libro_id = l.id WHERE p.lector_id = ? ORDER BY p.fecha_prestamo DESC');
    $stmt->execute([$id]);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error al obtener información del lector: ' . $e->getMessage();
}

$pageTitle = 'Detalle de Lector';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person"></i> Detalle de Lector
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
                    <?php else: ?>
                        <ul class="list-unstyled mb-4">
                            <li><strong>Nombre:</strong> <?= htmlspecialchars($lector['nombre'] . ' ' . $lector['apellidos']) ?></li>
                            <li><strong>ID:</strong> <?= htmlspecialchars($lector['dni']) ?></li>
                            <?php if ($lector['telefono']): ?>
                                <li><strong>Teléfono:</strong> <?= htmlspecialchars($lector['telefono']) ?></li>
                            <?php endif; ?>
                            <?php if ($lector['direccion']): ?>
                                <li><strong>Dirección:</strong> <?= htmlspecialchars($lector['direccion']) ?></li>
                            <?php endif; ?>
                            <?php if ($lector['fecha_nacimiento']): ?>
                                <li><strong>Fecha de nacimiento:</strong> <?= formatDate($lector['fecha_nacimiento']) ?></li>
                            <?php endif; ?>
                            <li><strong>Límite de préstamos:</strong> <?= htmlspecialchars($lector['limite_prestamos']) ?></li>
                            <li><strong>Multa total:</strong> <?= formatCurrency($lector['multa_total']) ?></li>
                            <li><strong>Estado:</strong> <?= $lector['bloqueado'] ? '<span class="badge bg-danger">Bloqueado</span>' : '<span class="badge bg-success">Activo</span>' ?></li>
                        </ul>
                        <h6 class="mt-4"><i class="bi bi-book"></i> Préstamos</h6>
                        <?php if (!empty($prestamos)): ?>
                            <ul class="mb-0">
                                <?php foreach ($prestamos as $p): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($p['titulo']) ?></strong> - <?= formatDate($p['fecha_prestamo']) ?>
                                        <?php if ($p['estado'] === 'activo' || $p['estado'] === 'atrasado'): ?>
                                            <span class="badge bg-warning text-dark">Activo</span>
                                        <?php elseif ($p['estado'] === 'devuelto'): ?>
                                            <span class="badge bg-success">Devuelto</span>
                                        <?php endif; ?>
                                        <?php if ($p['fecha_devolucion']): ?>
                                            <br><small class="text-muted">Devuelto: <?= formatDate($p['fecha_devolucion']) ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="bi bi-info-circle"></i> No hay préstamos registrados para este lector.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="lectores.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                        <a href="lector_form.php?id=<?= $lector['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
