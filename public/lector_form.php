<?php
require_once '../config/init.php';

requireAuth(true);

$db = getDBConnection();
$errors = [];
$success = false;
$editMode = false;
$lector = [
    'nombre' => '',
    'apellidos' => '',
    'dni' => '',
    'telefono' => '',
    'direccion' => '',
    'fecha_nacimiento' => '',
    'limite_prestamos' => 3,
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editMode = true;
    $id = (int)$_GET['id'];
    $stmt = $db->prepare('SELECT * FROM lectores WHERE id = ?');
    $stmt->execute([$id]);
    $lector = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lector) {
        setFlashMessage('error', 'Lector no encontrado.');
        header('Location: lectores.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $limite_prestamos = (int)($_POST['limite_prestamos'] ?? 3);

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';
    if ($apellidos === '') $errors[] = 'Los apellidos son obligatorios.';
    if ($dni === '') $errors[] = 'El ID es obligatorio.';
    if (!preg_match('/^[A-Za-z0-9]{6,20}$/', $dni)) $errors[] = 'El ID debe tener entre 6 y 20 caracteres alfanuméricos.';
    if ($telefono && !preg_match('/^[0-9\-\s]{7,15}$/', $telefono)) $errors[] = 'El teléfono no es válido.';
    if ($fecha_nacimiento && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) $errors[] = 'La fecha de nacimiento no es válida.';
    if ($limite_prestamos < 1 || $limite_prestamos > 10) $errors[] = 'El límite de préstamos debe ser entre 1 y 10.';

    // Verificar duplicado de ID
    $sql = 'SELECT id FROM lectores WHERE dni = ?';
    $params = [$dni];
    if ($editMode) {
        $sql .= ' AND id != ?';
        $params[] = $lector['id'];
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        $errors[] = 'Ya existe un lector con ese ID.';
    }

    if (empty($errors)) {
        if ($editMode) {
            $stmt = $db->prepare('UPDATE lectores SET nombre=?, apellidos=?, dni=?, telefono=?, direccion=?, fecha_nacimiento=?, limite_prestamos=? WHERE id=?');
            $stmt->execute([$nombre, $apellidos, $dni, $telefono, $direccion, $fecha_nacimiento ?: null, $limite_prestamos, $lector['id']]);
            setFlashMessage('success', 'Lector actualizado correctamente.');
        } else {
            $stmt = $db->prepare('INSERT INTO lectores (nombre, apellidos, dni, telefono, direccion, fecha_nacimiento, limite_prestamos) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $apellidos, $dni, $telefono, $direccion, $fecha_nacimiento ?: null, $limite_prestamos]);
            setFlashMessage('success', 'Lector registrado correctamente.');
        }
        header('Location: lectores.php');
        exit;
    }
}

$pageTitle = $editMode ? 'Editar Lector' : 'Agregar Lector';
include '../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-plus"></i> <?= $pageTitle ?>
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
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100" value="<?= htmlspecialchars($lector['nombre']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required maxlength="100" value="<?= htmlspecialchars($lector['apellidos']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="dni" class="form-label">ID</label>
                            <input type="text" class="form-control" id="dni" name="dni" required maxlength="20" value="<?= htmlspecialchars($lector['dni']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="15" value="<?= htmlspecialchars($lector['telefono']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2" maxlength="255"><?= htmlspecialchars($lector['direccion']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($lector['fecha_nacimiento']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="limite_prestamos" class="form-label">Límite de Préstamos</label>
                            <input type="number" class="form-control" id="limite_prestamos" name="limite_prestamos" min="1" max="10" value="<?= htmlspecialchars($lector['limite_prestamos']) ?>">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="lectores.php" class="btn btn-secondary">
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
