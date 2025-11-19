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
    'fecha_vencimiento' => date('Y-m-d', strtotime('+14 days')),
    'fecha_devolucion' => '',
];
$editMode = false;
$prestamoId = null;

// Si viene id por GET, cargar datos del préstamo
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $prestamoId = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM prestamos WHERE id = ?');
    $stmt->execute([$prestamoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $prestamo = [
            'lector_id' => $row['lector_id'],
            'libro_id' => $row['libro_id'],
            'fecha_prestamo' => $row['fecha_prestamo'],
            'fecha_vencimiento' => $row['fecha_vencimiento'],
            'fecha_devolucion' => $row['fecha_devolucion'],
        ];
        $editMode = true;
    } else {
        setFlashMessage('error', 'Préstamo no encontrado.');
        header('Location: prestamos.php');
        exit;
    }
}

// Obtener lectores activos
try {
    $lectores = $db->query("SELECT id, nombre, apellidos FROM lectores WHERE bloqueado = 0 ORDER BY nombre, apellidos")->fetchAll(PDO::FETCH_ASSOC);
    // Si es edición, incluir el libro original aunque no tenga copias disponibles
    if ($editMode && !empty($prestamo['libro_id'])) {
        $stmt = $db->prepare("SELECT id, titulo FROM libros WHERE (activo = 1 AND (copias_disponibles > 0 OR id = ?)) ORDER BY titulo");
        $stmt->execute([$prestamo['libro_id']]);
        $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $libros = $db->query("SELECT id, titulo FROM libros WHERE activo = 1 AND copias_disponibles > 0 ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errors[] = 'Error al cargar lectores o libros: ' . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lector_id = (int)($_POST['lector_id'] ?? 0);
    $libro_id = (int)($_POST['libro_id'] ?? 0);
    $fecha_prestamo = trim($_POST['fecha_prestamo'] ?? '');
    $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
    $fecha_devolucion = trim($_POST['fecha_devolucion'] ?? '');

    if (!$lector_id) $errors[] = 'Debe seleccionar un lector.';
    if (!$libro_id) $errors[] = 'Debe seleccionar un libro.';
    if (!$fecha_prestamo || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_prestamo)) $errors[] = 'La fecha de préstamo es obligatoria y debe tener formato válido.';
    if (!$fecha_vencimiento || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_vencimiento)) $errors[] = 'La fecha de vencimiento es obligatoria y debe tener formato válido.';
    if ($fecha_devolucion && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_devolucion)) $errors[] = 'La fecha de devolución debe tener formato válido.';

    // Validar disponibilidad de libro solo si es nuevo préstamo o cambia el libro
    if (!$editMode || $libro_id != $prestamo['libro_id']) {
        $stmt = $db->prepare('SELECT copias_disponibles FROM libros WHERE id = ?');
        $stmt->execute([$libro_id]);
        $libro = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$libro || $libro['copias_disponibles'] < 1) {
            $errors[] = 'El libro seleccionado no está disponible.';
        }
    }

    // Validar límite de préstamos del lector solo si es nuevo préstamo o cambia el lector
    if (!$editMode || $lector_id != $prestamo['lector_id']) {
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
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();
            if ($editMode) {
                // Solo actualizar préstamo, no modificar copias
                $stmt = $db->prepare('UPDATE prestamos SET lector_id=?, libro_id=?, fecha_prestamo=?, fecha_vencimiento=?, fecha_devolucion=? WHERE id=?');
                $stmt->execute([$lector_id, $libro_id, $fecha_prestamo, $fecha_vencimiento, $fecha_devolucion ?: null, $prestamoId]);
                setFlashMessage('success', 'Préstamo actualizado correctamente.');
            } else {
                // Verificar si ya existe un préstamo igual para evitar duplicados exactos (lector, libro, fecha_prestamo, estado activo)
                $stmt = $db->prepare('SELECT COUNT(*) FROM prestamos WHERE lector_id = ? AND libro_id = ? AND fecha_prestamo = ? AND estado = "activo"');
                $stmt->execute([$lector_id, $libro_id, $fecha_prestamo]);
                $existe = $stmt->fetchColumn();
                if ($existe > 0) {
                    $errors[] = 'Ya existe un préstamo activo para este lector, libro y fecha.';
                    $db->rollBack();
                } else {
                    // Nuevo préstamo: insertar, el trigger en la base de datos descuenta la copia
                    $stmt = $db->prepare('INSERT INTO prestamos (lector_id, libro_id, fecha_prestamo, fecha_vencimiento, fecha_devolucion, estado) VALUES (?, ?, ?, ?, ?, "activo")');
                    $stmt->execute([$lector_id, $libro_id, $fecha_prestamo, $fecha_vencimiento, $fecha_devolucion ?: null]);
                    setFlashMessage('success', 'Préstamo registrado correctamente.');
                    $db->commit();
                    header('Location: prestamos.php');
                    exit;
                }
            }
            if (empty($errors)) {
                $db->commit();
                header('Location: prestamos.php');
                exit;
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = 'Error al guardar el préstamo: ' . $e->getMessage();
        }
    }
    // Repoblar campos en caso de error
    $prestamo = [
        'lector_id' => $lector_id,
        'libro_id' => $libro_id,
        'fecha_prestamo' => $fecha_prestamo,
        'fecha_vencimiento' => $fecha_vencimiento,
        'fecha_devolucion' => $fecha_devolucion,
    ];
}

$pageTitle = $editMode ? 'Editar Préstamo' : 'Registrar Préstamo';
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
                    <form method="POST" autocomplete="off" id="prestamoForm">
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
                            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                            <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required value="<?= htmlspecialchars($prestamo['fecha_vencimiento']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="fecha_devolucion" class="form-label">Fecha de Devolución (opcional)</label>
                            <input type="date" class="form-control" id="fecha_devolucion" name="fecha_devolucion" value="<?= htmlspecialchars($prestamo['fecha_devolucion']) ?>">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="prestamos.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnGuardar">
                                <i class="bi bi-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
// Protección contra doble envío del formulario
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('prestamoForm');
    var btn = document.getElementById('btnGuardar');
    if (form && btn) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        });
    }
});
</script>
<?php include '../includes/footer.php'; ?>
