<?php
/**
 * Eliminación de Autores - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

$db = getDBConnection();
$autor = null;
$errors = [];
$librosAsociados = [];

// Verificar que se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: autores.php');
    exit;
}

$autorId = (int)$_GET['id'];

try {
    // Obtener datos del autor
    $stmt = $db->prepare("
        SELECT a.*, COUNT(la.libro_id) as total_libros
        FROM autores a
        LEFT JOIN libro_autores la ON a.id = la.autor_id
        LEFT JOIN libros l ON la.libro_id = l.id AND l.activo = 1
        WHERE a.id = ? AND a.activo = 1
        GROUP BY a.id
    ");
    $stmt->execute([$autorId]);
    $autor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$autor) {
        header('Location: autores.php');
        exit;
    }

    // Verificar si hay libros asociados activos
    if ($autor['total_libros'] > 0) {
        $stmt = $db->prepare("
            SELECT l.id, l.titulo, l.isbn, l.copias_totales,
                   COUNT(p.id) as prestamos_activos
            FROM libros l
            INNER JOIN libro_autores la ON l.id = la.libro_id
            LEFT JOIN prestamos p ON l.id = p.libro_id AND p.fecha_devolucion IS NULL
            WHERE la.autor_id = ? AND l.activo = 1
            GROUP BY l.id, l.titulo, l.isbn, l.copias_totales
        ");
        $stmt->execute([$autorId]);
        $librosAsociados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $errors[] = "Error al obtener información del autor: " . $e->getMessage();
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido.';
    } else {
        try {
            $db->beginTransaction();

            // Verificar nuevamente que no hay préstamos activos
            $stmt = $db->prepare("
                SELECT COUNT(*) as prestamos_activos
                FROM prestamos p
                INNER JOIN libro_autores la ON p.libro_id = la.libro_id
                WHERE la.autor_id = ? AND p.fecha_devolucion IS NULL
            ");
            $stmt->execute([$autorId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['prestamos_activos'] > 0) {
                throw new Exception('No se puede eliminar: existen préstamos activos de libros de este autor.');
            }

            // Eliminar relaciones libro-autor
            $stmt = $db->prepare("DELETE FROM libro_autores WHERE autor_id = ?");
            $stmt->execute([$autorId]);

            // Marcar autor como inactivo (eliminación lógica)
            $stmt = $db->prepare("UPDATE autores SET activo = 0 WHERE id = ?");
            $stmt->execute([$autorId]);

            $db->commit();
            
            // Redirigir con mensaje de éxito
            header('Location: autores.php?deleted=1');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Error al eliminar el autor: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Eliminar Autor';
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-person-x text-danger"></i>
                    Eliminar Autor
                </h1>
                <div>
                    <a href="autores.php" class="btn btn-secondary me-2" style="position: relative; z-index: 1000;">
                        <i class="bi bi-arrow-left"></i> Volver a Autores
                    </a>
                    <a href="autor_detalle.php?id=<?= $autor['id'] ?>" class="btn btn-info" style="position: relative; z-index: 1000;">
                        <i class="bi bi-eye"></i> Ver Detalles
                    </a>
                </div>
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

            <?php if ($autor): ?>
                <div class="row justify-content-center">
                    <div class="col-xl-8 col-lg-10">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Confirmar Eliminación de Autor
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Información del autor a eliminar -->
                                <div class="alert alert-warning" role="alert">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>¿Estás seguro de que deseas eliminar este autor?</strong>
                                    <br>Esta acción no se puede deshacer.
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6>Información del Autor:</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Nombre:</strong> <?= htmlspecialchars($autor['nombre'] . ' ' . $autor['apellidos']) ?></li>
                                            <?php if ($autor['nacionalidad']): ?>
                                                <li><strong>Nacionalidad:</strong> <?= htmlspecialchars($autor['nacionalidad']) ?></li>
                                            <?php endif; ?>
                                            <?php if ($autor['fecha_nacimiento']): ?>
                                                <li><strong>Fecha de Nacimiento:</strong> 
                                                    <?php
                                                    $fecha = new DateTime($autor['fecha_nacimiento']);
                                                    echo $fecha->format('d/m/Y');
                                                    ?>
                                                </li>
                                            <?php endif; ?>
                                            <li><strong>Fecha de Registro:</strong> 
                                                <?php
                                                $fecha_registro = new DateTime($autor['fecha_creacion']);
                                                echo $fecha_registro->format('d/m/Y H:i');
                                                ?>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Estadísticas:</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Total de Libros:</strong> 
                                                <span class="badge bg-info"><?= $autor['total_libros'] ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Verificación de libros asociados -->
                                <?php if (!empty($librosAsociados)): ?>
                                    <div class="alert alert-info" role="alert">
                                        <h6><i class="bi bi-book"></i> Libros Asociados (<?= count($librosAsociados) ?>):</h6>
                                        <div class="table-responsive mt-3">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Título</th>
                                                        <th>ISBN</th>
                                                        <th>Copias</th>
                                                        <th>Préstamos Activos</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($librosAsociados as $libro): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($libro['titulo']) ?></td>
                                                            <td><?= htmlspecialchars($libro['isbn'] ?? 'N/A') ?></td>
                                                            <td><?= $libro['copias_totales'] ?></td>
                                                            <td>
                                                                <span class="badge bg-<?= $libro['prestamos_activos'] > 0 ? 'warning' : 'success' ?>">
                                                                    <?= $libro['prestamos_activos'] ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($libro['prestamos_activos'] > 0): ?>
                                                                    <span class="badge bg-danger">Bloqueado</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success">Disponible</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <?php
                                    $tieneBloqueos = false;
                                    foreach ($librosAsociados as $libro) {
                                        if ($libro['prestamos_activos'] > 0) {
                                            $tieneBloqueos = true;
                                            break;
                                        }
                                    }
                                    ?>

                                    <?php if ($tieneBloqueos): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            <strong>No se puede eliminar el autor</strong><br>
                                            Existen libros de este autor que tienen préstamos activos. 
                                            Debe esperar a que se devuelvan todos los libros antes de poder eliminar el autor.
                                        </div>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="autores.php" class="btn btn-secondary" style="position: relative; z-index: 1000;">
                                                <i class="bi bi-arrow-left"></i> Volver a Autores
                                            </a>
                                            <a href="autor_detalle.php?id=<?= $autor['id'] ?>" class="btn btn-info" style="position: relative; z-index: 1000;">
                                                <i class="bi bi-eye"></i> Ver Detalles del Autor
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success" role="alert">
                                            <i class="bi bi-check-circle"></i>
                                            <strong>Eliminación permitida</strong><br>
                                            No hay préstamos activos de libros de este autor. La eliminación se puede proceder.
                                            <br><br>
                                            <small class="text-muted">
                                                <strong>Nota:</strong> Al eliminar el autor, se removerá su asociación con todos los libros, 
                                                pero los libros permanecerán en el sistema.
                                            </small>
                                        </div>

                                        <form method="POST" action="" class="mt-4">
                                            <?= csrfTokenField() ?>
                                            
                                            <div class="d-flex justify-content-between">
                                                <a href="autores.php" class="btn btn-secondary">
                                                    <i class="bi bi-x-circle"></i> Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-danger" style="position: relative; z-index: 1000;">
                                                    <i class="bi bi-trash"></i> Confirmar Eliminación
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-success" role="alert">
                                        <i class="bi bi-check-circle"></i>
                                        <strong>Eliminación permitida</strong><br>
                                        Este autor no tiene libros asociados. La eliminación se puede proceder sin problemas.
                                    </div>

                                    <form method="POST" action="" class="mt-4">
                                        <?= csrfTokenField() ?>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="autores.php" class="btn btn-secondary" style="position: relative; z-index: 1000;">
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>