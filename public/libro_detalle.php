<?php
/**
 * Detalles de Libro - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Requerir autenticación (tanto admin como usuario pueden ver detalles)
requireAuth();

// Verificar que se proporcione un ID válido
$libro_id = (int)($_GET['id'] ?? 0);

if ($libro_id <= 0) {
    setFlashMessage('error', 'ID de libro no válido');
    redirect('libros.php');
}

try {
    // Obtener información completa del libro
    $libro = fetchOne("
        SELECT l.*, c.nombre as categoria_nombre
        FROM libros l
        LEFT JOIN categorias c ON l.categoria_id = c.id
        WHERE l.id = ? AND l.activo = 1
    ", [$libro_id]);
    
    if (!$libro) {
        setFlashMessage('error', 'El libro no existe o ha sido eliminado');
        redirect('libros.php');
    }
    
    // Obtener autores del libro
    $autores = fetchAll("
        SELECT a.nombre, a.apellidos, a.biografia, a.nacionalidad
        FROM autores a
        INNER JOIN libro_autores la ON a.id = la.autor_id
        WHERE la.libro_id = ? AND a.activo = 1
        ORDER BY a.apellidos, a.nombre
    ", [$libro_id]);
    
    // Obtener estadísticas de préstamos
    $stats_prestamos = fetchOne("
        SELECT 
            COUNT(*) as total_prestamos,
            SUM(CASE WHEN estado IN ('activo', 'vencido') THEN 1 ELSE 0 END) as prestamos_activos,
            SUM(CASE WHEN estado = 'devuelto' THEN 1 ELSE 0 END) as prestamos_devueltos
        FROM prestamos 
        WHERE libro_id = ?
    ", [$libro_id]);
    
} catch (Exception $e) {
    error_log("Error al cargar detalles del libro: " . $e->getMessage());
    setFlashMessage('error', 'Error al cargar los detalles del libro');
    redirect('libros.php');
}

$page_title = 'Detalles del Libro';
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-2">
            <i class="bi bi-book"></i> Detalles del Libro
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="libros.php" style="position: relative; z-index: 1000;">Libros</a></li>
                <li class="breadcrumb-item active">Detalles</li>
            </ol>
        </nav>
    </div>
    <div>
        <?php if (isAdmin()): ?>
            <a href="libro_form.php?id=<?= $libro['id'] ?>" class="btn btn-outline-primary me-2" style="position: relative; z-index: 1000;">
                <i class="bi bi-pencil"></i> Editar
            </a>
        <?php endif; ?>
        <a href="libros.php" class="btn btn-outline-secondary" style="position: relative; z-index: 1000;">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>

<div class="row">
    <!-- Información principal del libro -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i> Información del Libro
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4 class="text-primary mb-3"><?= htmlspecialchars($libro['titulo']) ?></h4>
                        
                        <?php if (!empty($libro['subtitulo'])): ?>
                            <p class="text-muted fs-5 mb-3"><?= htmlspecialchars($libro['subtitulo']) ?></p>
                        <?php endif; ?>
                        
                        <dl class="row">
                            <?php if (!empty($libro['isbn'])): ?>
                                <dt class="col-sm-4">ISBN:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($libro['isbn']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($libro['año_publicacion'])): ?>
                                <dt class="col-sm-4">Año de publicación:</dt>
                                <dd class="col-sm-8"><?= $libro['año_publicacion'] ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($libro['editorial'])): ?>
                                <dt class="col-sm-4">Editorial:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($libro['editorial']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($libro['categoria_nombre'])): ?>
                                <dt class="col-sm-4">Categoría:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($libro['categoria_nombre']) ?></span>
                                </dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($libro['numero_paginas'])): ?>
                                <dt class="col-sm-4">Páginas:</dt>
                                <dd class="col-sm-8"><?= number_format($libro['numero_paginas']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($libro['idioma'])): ?>
                                <dt class="col-sm-4">Idioma:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($libro['idioma']) ?></dd>
                            <?php endif; ?>
                            
                            <?php if (!empty($libro['ubicacion'])): ?>
                                <dt class="col-sm-4">Ubicación:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($libro['ubicacion']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Estado de disponibilidad -->
                        <div class="card bg-light mb-3">
                            <div class="card-body text-center">
                                <h6 class="card-title">Disponibilidad</h6>
                                <div class="row text-center">
                                    <div class="col">
                                        <div class="fs-3 <?= $libro['copias_disponibles'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $libro['copias_disponibles'] ?>
                                        </div>
                                        <small class="text-muted">Disponibles</small>
                                    </div>
                                    <div class="col">
                                        <div class="fs-3 text-info">
                                            <?= $libro['copias_totales'] ?>
                                        </div>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                                
                                <?php if ($libro['copias_disponibles'] > 0): ?>
                                    <div class="mt-3">
                                        <span class="badge bg-success fs-6">
                                            <i class="bi bi-check-circle"></i> Disponible para préstamo
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-3">
                                        <span class="badge bg-danger fs-6">
                                            <i class="bi bi-x-circle"></i> No disponible
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Estadísticas de préstamos -->
                        <?php if ($stats_prestamos && $stats_prestamos['total_prestamos'] > 0): ?>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="bi bi-graph-up"></i> Estadísticas de Préstamos
                                    </h6>
                                    <div class="row text-center">
                                        <div class="col">
                                            <div class="fs-4 text-primary"><?= $stats_prestamos['total_prestamos'] ?></div>
                                            <small class="text-muted">Total</small>
                                        </div>
                                        <div class="col">
                                            <div class="fs-4 text-warning"><?= $stats_prestamos['prestamos_activos'] ?></div>
                                            <small class="text-muted">Activos</small>
                                        </div>
                                        <div class="col">
                                            <div class="fs-4 text-success"><?= $stats_prestamos['prestamos_devueltos'] ?></div>
                                            <small class="text-muted">Devueltos</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Descripción del libro -->
                <?php if (!empty($libro['descripcion'])): ?>
                    <hr>
                    <h6><i class="bi bi-text-paragraph"></i> Descripción</h6>
                    <div class="text-justify">
                        <?= nl2br(htmlspecialchars($libro['descripcion'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Información de autores -->
    <div class="col-lg-4">
        <?php if (!empty($autores)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> 
                        Autor<?= count($autores) > 1 ? 'es' : '' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($autores as $index => $autor): ?>
                        <?php if ($index > 0): ?>
                            <hr>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <h6 class="text-primary mb-1">
                                <?= htmlspecialchars($autor['nombre'] . ' ' . $autor['apellidos']) ?>
                            </h6>
                            
                            <?php if (!empty($autor['nacionalidad'])): ?>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($autor['nacionalidad']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($autor['biografia'])): ?>
                                <div class="small">
                                    <?= nl2br(htmlspecialchars($autor['biografia'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center text-muted">
                    <i class="bi bi-person-x fs-1"></i>
                    <p class="mt-2">No se ha asignado ningún autor a este libro</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>