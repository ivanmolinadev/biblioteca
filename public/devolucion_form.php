
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/init.php';
requireAuth(true);

$db = getDBConnection();
$errors = [];
$success = false;

$prestamo_id = isset($_GET['prestamo_id']) ? (int)$_GET['prestamo_id'] : 0;
$prestamo = null;

if ($prestamo_id > 0) {
	$prestamo = fetchOne("SELECT p.*, l.titulo, lec.nombre AS lector_nombre, lec.apellidos AS lector_apellidos FROM prestamos p JOIN libros l ON p.libro_id = l.id JOIN lectores lec ON p.lector_id = lec.id WHERE p.id = ?", [$prestamo_id]);
	if (!$prestamo) {
		$errors[] = 'Préstamo no encontrado.';
	}
} else {
	$errors[] = 'ID de préstamo no especificado.';
}

$observaciones = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$observaciones = trim($_POST['observaciones'] ?? '');
	if (empty($errors)) {
		try {
			$fecha_devolucion = date('Y-m-d');
			$sql = "INSERT INTO devoluciones (prestamo_id, fecha_devolucion, observaciones, usuario_registro_id) VALUES (?, ?, ?, ?)";
			executeQuery($sql, [
				$prestamo_id,
				$fecha_devolucion,
				$observaciones,
				getCurrentUser()['id']
			]);
			$success = true;
			header('Location: prestamos.php');
			exit;
		} catch (Exception $e) {
			$errors[] = 'Error al registrar la devolución: ' . $e->getMessage();
		}
	}
}

$page_title = 'Registrar Devolución';
include '../includes/header.php';
?>
<div class="container mt-4">
	<h2>Registrar Devolución</h2>
	<?php if ($success): ?>
		<div class="alert alert-success">Devolución registrada exitosamente.</div>
	<?php endif; ?>
	<?php if ($errors): ?>
		<div class="alert alert-danger">
			<ul class="mb-0">
				<?php foreach ($errors as $err): ?>
					<li><?= htmlspecialchars($err) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ($prestamo): ?>
	<div class="card mb-3 z-top-form">
		<div class="card-body">
			<h5 class="card-title mb-1">Libro: <?= htmlspecialchars($prestamo['titulo']) ?></h5>
			<p class="mb-1">Lector: <?= htmlspecialchars($prestamo['lector_nombre'] . ' ' . $prestamo['lector_apellidos']) ?></p>
			<p class="mb-1">Fecha préstamo: <?= htmlspecialchars($prestamo['fecha_prestamo']) ?></p>
			<p class="mb-1">Fecha vencimiento: <?= htmlspecialchars($prestamo['fecha_vencimiento']) ?></p>
		</div>
	</div>
	<form method="post">
		<div class="mb-3">
			<label for="observaciones" class="form-label">Observaciones</label>
			<input type="text" class="form-control" id="observaciones" name="observaciones" value="<?= htmlspecialchars($observaciones) ?>" autocomplete="off">
		</div>
		<button type="submit" class="btn btn-primary">Registrar devolución</button>
		<a href="prestamos.php" class="btn btn-secondary" id="cancelar-btn">Cancelar</a>
	</form>
	<?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var cancelar = document.getElementById('cancelar-btn');
	if (cancelar) {
		cancelar.addEventListener('click', function(e) {
			window.location.href = 'prestamos.php';
		});
	}
});
</script>
<?php include '../includes/footer.php'; ?>
