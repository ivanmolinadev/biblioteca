<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/init.php';
requireAuth(true);

// Validar ID
$prestamo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($prestamo_id <= 0) {
    setFlashMessage('error', 'ID de préstamo inválido.');
    header('Location: prestamos.php');
    exit;
}

// Obtener datos del préstamo
$sql = "
SELECT p.*, l.titulo AS libro_titulo, l.isbn, l.editorial, l.anio_publicacion,
       CONCAT(lec.nombre, ' ', lec.apellidos) AS lector_nombre, lec.dni, lec.telefono, lec.email,
       u.username AS usuario_registro
FROM prestamos p
JOIN libros l ON p.libro_id = l.id
JOIN lectores lec ON p.lector_id = lec.id
LEFT JOIN usuarios u ON p.usuario_id = u.id
WHERE p.id = ?
LIMIT 1
";
$prestamo = fetchOne($sql, [$prestamo_id]);

if (!$prestamo) {
    setFlashMessage('error', 'Préstamo no encontrado.');
    header('Location: prestamos.php');
    exit;
}

// Buscar devolución (si existe)
$devolucion = fetchOne("SELECT * FROM devoluciones WHERE prestamo_id = ? LIMIT 1", [$prestamo_id]);

$page_title = 'Detalle de Préstamo';
include '../includes/header.php';
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-eye"></i> Detalle de Préstamo #<?= $prestamo['id'] ?></h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Lector</dt>
                        <dd class="col-sm-8">
                            <strong><?= htmlspecialchars($prestamo['lector_nombre']) ?></strong><br>
                            DNI: <?= htmlspecialchars($prestamo['dni']) ?><br>
                            Tel: <?= htmlspecialchars($prestamo['telefono']) ?><br>
                            Email: <?= htmlspecialchars($prestamo['email']) ?>
                        </dd>

                        <dt class="col-sm-4">Libro</dt>
                        <dd class="col-sm-8">
                            <strong><?= htmlspecialchars($prestamo['libro_titulo']) ?></strong><br>
                            ISBN: <?= htmlspecialchars($prestamo['isbn']) ?><br>
                            Editorial: <?= htmlspecialchars($prestamo['editorial']) ?><br>
                            Año: <?= htmlspecialchars($prestamo['anio_publicacion']) ?>
                        </dd>

                        <dt class="col-sm-4">Fecha de Préstamo</dt>
                        <dd class="col-sm-8"><?= formatDate($prestamo['fecha_prestamo']) ?></dd>

                        <dt class="col-sm-4">Fecha de Vencimiento</dt>
                        <dd class="col-sm-8"><?= formatDate($prestamo['fecha_vencimiento']) ?></dd>

                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?= $prestamo['estado'] === 'activo' ? 'success' : ($prestamo['estado'] === 'atrasado' ? 'danger' : 'secondary') ?>">
                                <?= ucfirst($prestamo['estado']) ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Registrado por</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($prestamo['usuario_registro'] ?? 'N/A') ?></dd>

                        <?php if ($devolucion): ?>
                            <dt class="col-sm-4">Fecha de Devolución</dt>
                            <dd class="col-sm-8"><?= formatDate($devolucion['fecha_devolucion']) ?></dd>
                            <dt class="col-sm-4">Multa</dt>
                            <dd class="col-sm-8">
                                <?= $devolucion['multa'] > 0 ? ('$' . number_format($devolucion['multa'], 2)) : 'Sin multa' ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                    <a href="prestamos.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
