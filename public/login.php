<?php
/**
 * Página de Login - Sistema de Biblioteca y Préstamos
 */
require_once '../config/init.php';

// Redirigir si ya está autenticado
if (isAuthenticated()) {
    redirect('dashboard.php');
}

$error_message = '';
$success_message = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, complete todos los campos';
    } else {
        try {
            // Buscar usuario en la base de datos
            $sql = "SELECT id, username, email, password_hash, rol, activo 
                    FROM usuarios 
                    WHERE (username = ? OR email = ?) AND activo = 1";
            
            $user = fetchOne($sql, [$username, $username]);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login exitoso
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                $_SESSION['last_activity'] = time();
                $_SESSION['login_time'] = time();
                
                // Actualizar última sesión
                $updateSql = "UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?";
                executeQuery($updateSql, [$user['id']]);
                

                
                logActivity('login', 'Inicio de sesión exitoso');
                
                // Redirigir según el rol
                if ($user['rol'] === 'admin') {
                    redirect('dashboard.php');
                } else {
                    redirect('dashboard.php');
                }
                
            } else {
                $error_message = 'Credenciales incorrectas';
                logActivity('login_failed', "Intento fallido para usuario: $username");
            }
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $error_message = 'Error interno del sistema. Intente nuevamente.';
        }
    }
}

// Verificar mensajes de URL
if (isset($_GET['timeout'])) {
    $error_message = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
}


$page_title = 'Iniciar Sesión';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>
    
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(45deg, #0d6efd, #4dabf7);
            color: white;
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
        }
        
        .btn-login {
            background: linear-gradient(45deg, #0d6efd, #4dabf7);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-header">
                        <h1 class="h3 mb-2">
                            <i class="bi bi-book"></i> <?= APP_NAME ?>
                        </h1>
                        <p class="mb-0">Inicie sesión para acceder al sistema</p>
                    </div>
                    
                    <div class="login-form">
                        <!-- Mostrar mensajes -->
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <?= csrfTokenField() ?>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person"></i> Usuario o Email
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username"
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                       placeholder="Ingrese su usuario o email"
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password"
                                           placeholder="Ingrese su contraseña"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword"
                                            data-bs-toggle="tooltip"
                                            title="Mostrar/Ocultar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            

                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="text-muted mb-2">Usuarios de prueba:</p>
                            <small class="text-muted">
                                <strong>Admin:</strong> admin / password<br>
                                <strong>Usuario:</strong> usuario1 / password
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-white">
                        <?= APP_NAME ?> v<?= APP_VERSION ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar contraseña
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
            
            // Validación del formulario
            const form = document.querySelector('.needs-validation');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
            
            // Enfocar primer campo
            document.getElementById('username').focus();
            
            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
