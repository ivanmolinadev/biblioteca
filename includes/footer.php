    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light text-center py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?= APP_NAME ?></h5>
                    <p class="mb-0">Sistema de gestión bibliotecaria</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-0">
                        Versión <?= APP_VERSION ?> &copy; <?= date('Y') ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Bootstrap (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script src="../assets/js/main.js"></script>
    
    <!-- JavaScript adicional específico de página -->
    <?php if (isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>

    <script>
        // Confirmar eliminaciones
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete, .delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Está seguro de que desea eliminar este registro?')) {
                        e.preventDefault();
                    }
                });
            });

            // Auto-hide alerts después de 5 segundos
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert && alert.classList.contains('show')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });

            // Validación de formularios
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });

        // Función para formatear números como moneda
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(amount);
        }

        // Función para confirmar acciones
        function confirmAction(message = '¿Está seguro?') {
            return confirm(message);
        }
    </script>
</body>
</html>