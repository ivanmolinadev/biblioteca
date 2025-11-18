<?php
/**
 * Eliminar Libro - Sistema de Biblioteca y Préstamos
 * Hard delete: eliminación física del registro
 */
require_once '../config/init.php';

// Requerir autenticación de administrador
requireAuth(true);

// Verificar que se proporcione un ID válido
$libro_id = (int)($_GET['id'] ?? 0);

if ($libro_id <= 0) {
    setFlashMessage('error', 'ID de libro no válido');
    redirect('libros.php');
}

try {
    // Verificar que el libro existe
    $libro = fetchOne("SELECT id, titulo FROM libros WHERE id = ?", [$libro_id]);
    
    if (!$libro) {
        setFlashMessage('error', 'El libro no existe o ya ha sido eliminado');
        redirect('libros.php');
    }
    
    // Verificar que no tenga préstamos activos
    $prestamos_activos = fetchOne("
        SELECT COUNT(*) as total 
        FROM prestamos 
        WHERE libro_id = ? AND estado IN ('activo', 'vencido')
    ", [$libro_id]);
    
    if ($prestamos_activos && $prestamos_activos['total'] > 0) {
        setFlashMessage('error', 'No se puede eliminar el libro porque tiene préstamos activos');
        redirect('libros.php');
    }
    
    // Procesar eliminación si es POST (confirmación)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar CSRF
        validateCSRFToken($_POST['csrf_token'] ?? '');
        
        // HARD DELETE: Eliminar físicamente el registro y sus relaciones
        
        // 1. Eliminar relaciones con autores
        executeQuery("DELETE FROM libro_autores WHERE libro_id = ?", [$libro_id]);
        
        // 2. Eliminar histórico de préstamos (si los hay)
        executeQuery("DELETE FROM prestamos WHERE libro_id = ?", [$libro_id]);
        
        // 3. Eliminar el libro
        executeQuery("DELETE FROM libros WHERE id = ?", [$libro_id]);
        
        setFlashMessage('success', "El libro '{$libro['titulo']}' ha sido eliminado permanentemente");
        redirect('libros.php');
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar libro: " . $e->getMessage());
    setFlashMessage('error', 'Error al eliminar el libro: ' . $e->getMessage());
    redirect('libros.php');
}

$page_title = 'Eliminar Libro';
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-warning"></i>
                    <strong>¡Atención!</strong> Esta acción no se puede deshacer. El libro será eliminado permanentemente.
                </div>
                
                <h6>Libro a eliminar:</h6>
                <div class="bg-light p-3 rounded mb-3">
                    <strong><?= htmlspecialchars($libro['titulo']) ?></strong><br>
                    <small class="text-muted">ID: <?= $libro['id'] ?></small>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="libros.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Cancelar
                    </a>
                    
                    <form method="POST" class="d-inline">
                        <?= csrfTokenField() ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar Permanentemente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Confirmación adicional con JavaScript
document.querySelector('form').addEventListener('submit', function(e) {
    const confirmed = confirm('¿Está completamente seguro de que desea eliminar este libro permanentemente?\n\nEsta acción no se puede deshacer.');
    if (!confirmed) {
        e.preventDefault();
    }
});
</script>

<?php include '../includes/footer.php'; ?>