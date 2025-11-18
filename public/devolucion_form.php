<?php
require_once '../config/init.php';
requireAuth(true);

$db = getDBConnection();
$errors = [];
$success = false;

// Obtener el préstamo a devolver
$prestamo_id = isset($_GET['prestamo_id']) ? (int)$_GET['prestamo_id'] : 0;
$prestamo = null;
if ($prestamo_id) {
    $stmt = $db->prepare('SELECT p.*, l.titulo, l.isbn, CONCAT(le.nombre, " ", le.apellidos) as lector_nombre FROM prestamos p JOIN libros l ON p.libro_id = l.id JOIN lectores le ON p.lector_id = le.id WHERE p.id = ? AND p.estado IN ("activo", "atrasado")');
    $stmt->execute([$prestamo_id]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prestamo) {
        setFlashMessage('error', 'Préstamo no encontrado o ya devuelto.');
        header('Location: prestamos.php');
        exit;
    }
} else {
    setFlashMessage('error', 'ID de préstamo inválido.');
    header('Location: prestamos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $observaciones = trim($_POST['observaciones'] ?? '');
    $fecha_devolucion = date('Y-m-d');
    try {
        $db->beginTransaction();
        // Insertar en devoluciones
        $stmt = $db->prepare('INSERT INTO devoluciones (prestamo_id, fecha_devolucion, observaciones, usuario_registro_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$prestamo_id, $fecha_devolucion, $observaciones, getCurrentUser()['id']]);
        // Actualizar préstamo
        $stmt = $db->prepare('UPDATE prestamos SET estado = "devuelto", fecha_devolucion = ? WHERE id = ?');
        $stmt->execute([$fecha_devolucion, $prestamo_id]);
        // Devolver libro (aumentar copias disponibles)
        $stmt = $db->prepare('UPDATE libros SET copias_disponibles = copias_disponibles + 1 WHERE id = ?');
        $stmt->execute([$prestamo['libro_id']]);
        $db->commit();
        setFlashMessage('success', 'Devolución registrada correctamente.');
        header('Location: prestamos.php');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $errors[] = 'Error al registrar la devolución: ' . $e->getMessage();
    }
}

$pageTitle = 'Registrar Devolución';
include '../includes/header.php';
?>
<div class="container mt-4">
    <h2>Registrar Devolución</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <?= csrfInput() ?>
        <div class="mb-3">
            <label class="form-label">Préstamo</label>
            <input type="text" class="form-control" value="#<?= $prestamo['id'] ?> - <?= htmlspecialchars($prestamo['titulo']) ?> (<?= htmlspecialchars($prestamo['lector_nombre']) ?>)" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha de devolución</label>
            <input type="text" class="form-control" value="<?= date('Y-m-d') ?>" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Registrar devolución</button>
        <a href="prestamos.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php include '../includes/footer.php'; ?>
