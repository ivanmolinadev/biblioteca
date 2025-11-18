<?php
require_once '../config/init.php';

requireAuth(true);

$db = getDBConnection();
$errors = [];
$success = false;
$lectores = [];
$libros = [];
$prestamo = [
    'lector_id' => '',
    'libro_id' => '',
    'fecha_prestamo' => date('Y-m-d'),
    'fecha_devolucion' => '',
];

// Obtener lectores activos
try {
    $lectores = $db->query("SELECT id, nombre, apellidos FROM lectores WHERE bloqueado = 0 ORDER BY nombre, apellidos")->fetchAll(PDO::FETCH_ASSOC);
    $libros = $db->query("SELECT id, titulo FROM libros WHERE activo = 1 AND copias_disponibles > 0 ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error al cargar lectores o libros: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lector_id = (int)($_POST['lector_id'] ?? 0);
    $libro_id = (int)($_POST['libro_id'] ?? 0);
    $fecha_prestamo = trim($_POST['fecha_prestamo'] ?? '');
    $fecha_devolucion = trim($_POST['fecha_devolucion'] ?? '');

    if (!$lector_id) $errors[] = 'Debe seleccionar un lector.';
    if (!$libro_id) $errors[] = 'Debe seleccionar un libro.';
    if (!$fecha_prestamo || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_prestamo)) $errors[] = 'La fecha de préstamo es obligatoria y debe tener formato válido.';
    if ($fecha_devolucion && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_devolucion)) $errors[] = 'La fecha de devolución debe tener formato válido.';

    // Validar disponibilidad de libro
    $stmt = $db->prepare('SELECT copias_disponibles FROM libros WHERE id = ?');
    $stmt->execute([$libro_id]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$libro || $libro['copias_disponibles'] < 1) {
        $errors[] = 'El libro seleccionado no está disponible.';
    }

    // Validar límite de préstamos del lector
    $stmt = $db->prepare('SELECT limite_prestamos FROM lectores WHERE id = ?');
    $stmt->execute([$lector_id]);
    $lector = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lector) {
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM prestamos WHERE lector_id = ? AND estado IN ("activo", "atrasado")');
        $stmt->execute([$lector_id]);
        $prestamos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        if ($prestamos_activos >= $lector['limite_prestamos']) {
            $errors[] = 'El lector ya alcanzó su límite de préstamos activos.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO prestamos (lector_id, libro_id, fecha_prestamo, fecha_devolucion, estado) VALUES (?, ?, ?, ?, "activo")');
            $stmt->execute([$lector_id, $libro_id, $fecha_prestamo, $fecha_devolucion ?: null]);
            $stmt = $db->prepare('UPDATE libros SET copias_disponibles = copias_disponibles - 1 WHERE id = ?');
            $stmt->execute([$libro_id]);
            $db->commit();
            setFlashMessage('success', 'Préstamo registrado correctamente.');
            header('Location: prestamos.php');
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = 'Error al registrar el préstamo: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Registrar Préstamo';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-journal-plus"></i> <?= $pageTitle ?>
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
                    <form method="POST" autocomplete="off">
                        <?= csrfTokenField() ?>
                        <div class="mb-3">
                            <label for="lector_id" class="form-label">Lector</label>
                            <select class="form-select" id="lector_id" name="lector_id" required>
                                <option value="">Seleccione un lector...</option>
                                <?php foreach ($lectores as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= $prestamo['lector_id'] == $l['id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nombre'] . ' ' . $l['apellidos']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="libro_id" class="form-label">Libro</label>
                            <select class="form-select" id="libro_id" name="libro_id" required>
                                <option value="">Seleccione un libro...</option>
                                <?php foreach ($libros as $lib): ?>
                                    <option value="<?= $lib['id'] ?>" <?= $prestamo['libro_id'] == $lib['id'] ? 'selected' : '' ?>><?= htmlspecialchars($lib['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
                            <input type="date" class="form-control" id="fecha_prestamo" name="fecha_prestamo" required value="<?= htmlspecialchars($prestamo['fecha_prestamo']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_devolucion" class="form-label">Fecha de Devolución (opcional)</label>
                            <input type="date" class="form-control" id="fecha_devolucion" name="fecha_devolucion" value="<?= htmlspecialchars($prestamo['fecha_devolucion']) ?>">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="prestamos.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
