<?php
/**
 * Detalles del Autor - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación (cualquier usuario autenticado puede ver detalles)
requireAuth();

$db = getDBConnection();
$autor = null;
$libros = [];

// Verificar que se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: autores.php');
    exit;
}

$autorId = (int)$_GET['id'];

try {
    // Obtener datos del autor
    $stmt = $db->prepare("
        SELECT a.*, 
               COUNT(la.libro_id) as total_libros,
               COUNT(CASE WHEN l.activo = 1 THEN 1 END) as libros_activos
        FROM autores a
        LEFT JOIN libro_autores la ON a.id = la.autor_id
        LEFT JOIN libros l ON la.libro_id = l.id
        WHERE a.id = ? AND a.activo = 1
        GROUP BY a.id
    ");
    $stmt->execute([$autorId]);
    $autor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$autor) {
        header('Location: autores.php');
        exit;
    }

    // Obtener libros del autor
    $stmt = $db->prepare("
        SELECT l.id, l.titulo, l.subtitulo, l.isbn, l.año_publicacion, l.editorial, 
               l.copias_totales, l.copias_disponibles, c.nombre as categoria,
               l.activo,
               -- Calcular total de préstamos del libro
               (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id) as total_prestamos,
               -- Calcular préstamos activos
               (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) as prestamos_activos
        FROM libros l
        INNER JOIN libro_autores la ON l.id = la.libro_id
        LEFT JOIN categorias c ON l.categoria_id = c.id
        WHERE la.autor_id = ?
        ORDER BY l.año_publicacion DESC, l.titulo ASC
    ");
    $stmt->execute([$autorId]);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al obtener información del autor: " . $e->getMessage();
}

$pageTitle = 'Detalles del Autor';
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="bi bi-person-lines-fill"></i>
                    Detalles del Autor
                </h1>
                <div>
                    <a href="autores.php" class="btn btn-secondary me-2" style="position: relative; z-index: 1000;">
                        <i class="bi bi-arrow-left"></i> Volver a Autores
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="autor_form.php?id=<?= $autor['id'] ?>" class="btn btn-primary" style="position: relative; z-index: 1000;">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php else: ?>

            <div class="row">
                <!-- Información del Autor -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person-badge"></i>
                                Información Personal
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Nombre Completo:</strong><br>
                                <span class="fs-5 text-primary">
                                    <?= htmlspecialchars($autor['nombre'] . ' ' . $autor['apellidos']) ?>
                                </span>
                            </div>

                            <?php if ($autor['fecha_nacimiento']): ?>
                                <div class="mb-3">
                                    <strong>Fecha de Nacimiento:</strong><br>
                                    <span class="text-muted">
                                        <i class="bi bi-calendar-date"></i>
                                        <?php
                                        $fecha = new DateTime($autor['fecha_nacimiento']);
                                        echo $fecha->format('d/m/Y');
                                        
                                        // Calcular edad si aplica
                                        $hoy = new DateTime();
                                        if ($fecha < $hoy) {
                                            $edad = $hoy->diff($fecha)->y;
                                            echo " ({$edad} años)";
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if ($autor['nacionalidad']): ?>
                                <div class="mb-3">
                                    <strong>Nacionalidad:</strong><br>
                                    <span class="text-muted">
                                        <i class="bi bi-globe"></i>
                                        <?= htmlspecialchars($autor['nacionalidad']) ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <strong>Fecha de Registro:</strong><br>
                                <span class="text-muted">
                                    <i class="bi bi-calendar-plus"></i>
                                    <?php
                                    $fecha_registro = new DateTime($autor['fecha_creacion']);
                                    echo $fecha_registro->format('d/m/Y H:i');
                                    ?>
                                </span>
                            </div>

                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <div class="fs-4 fw-bold text-primary"><?= $autor['total_libros'] ?></div>
                                        <small class="text-muted">Total Libros</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <div class="fs-4 fw-bold text-success"><?= $autor['libros_activos'] ?></div>
                                        <small class="text-muted">Libros Activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biografía y Libros -->
                <div class="col-lg-8">
                    <!-- Biografía -->
                    <?php if ($autor['biografia']): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-book"></i>
                                    Biografía
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-justify">
                                    <?= nl2br(htmlspecialchars($autor['biografia'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Libros del Autor -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bookshelf"></i>
                                Libros del Autor (<?= count($libros) ?>)
                            </h5>
                            <?php if (isAdmin() && count($libros) > 0): ?>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    Haz clic en un libro para ver sus detalles
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($libros)): ?>
                                <div class="text-center py-4">
                                    <div class="display-1 text-muted">
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <h4 class="text-muted">Sin libros registrados</h4>
                                    <p class="text-muted">Este autor aún no tiene libros asociados en la biblioteca.</p>
                                    <?php if (isAdmin()): ?>
                                        <a href="libro_form.php" class="btn btn-primary" style="position: relative; z-index: 1000;">
                                            <i class="bi bi-plus-circle"></i> Agregar Libro
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Título</th>
                                                <th>Año</th>
                                                <th>Editorial</th>
                                                <th>Categoría</th>
                                                <th class="text-center">Copias</th>
                                                <th class="text-center">Préstamos</th>
                                                <th class="text-center">Estado</th>
                                                <th width="120">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($libros as $libro): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($libro['titulo']) ?></strong>
                                                        <?php if ($libro['subtitulo']): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($libro['subtitulo']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($libro['isbn']): ?>
                                                            <br><small class="text-info">ISBN: <?= htmlspecialchars($libro['isbn']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $libro['año_publicacion'] ?? '<span class="text-muted">N/A</span>' ?></td>
                                                    <td><?= htmlspecialchars($libro['editorial'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <?php if ($libro['categoria']): ?>
                                                            <span class="badge bg-secondary"><?= htmlspecialchars($libro['categoria']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin categoría</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-info">
                                                            <?= $libro['copias_disponibles'] ?>/<?= $libro['copias_totales'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?= $libro['prestamos_activos'] > 0 ? 'warning' : 'success' ?>">
                                                            <?= $libro['total_prestamos'] ?>
                                                            <?php if ($libro['prestamos_activos'] > 0): ?>
                                                                (<?= $libro['prestamos_activos'] ?> activos)
                                                            <?php endif; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($libro['activo']): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="libro_detalle.php?id=<?= $libro['id'] ?>" 
                                                               class="btn btn-outline-info" 
                                                               style="position: relative; z-index: 1000;"
                                                               title="Ver detalles">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <?php if (isAdmin()): ?>
                                                                <a href="libro_form.php?id=<?= $libro['id'] ?>" 
                                                                   class="btn btn-outline-primary" 
                                                                   style="position: relative; z-index: 1000;"
                                                                   title="Editar">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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