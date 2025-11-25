<?php
require_once '../configs/config.php';

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Erro de segurança (CSRF). Recarregue a página.']);
        exit;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $userModel = new User();
    $user = $userModel->login($email, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_photo'] = $user['photo'];
        $_SESSION['user_permission'] = $user['permission'];
        $_SESSION['user_type'] = $user['type'];

        echo json_encode(['status' => 'success', 'message' => 'Login realizado com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email ou senha incorretos.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
        }
        .bg-circles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        .circle {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--accent-color), #3b82f6);
            opacity: 0.2;
            filter: blur(60px);
        }
        .circle-1 { top: -10%; left: -10%; width: 500px; height: 500px; }
        .circle-2 { bottom: -10%; right: -10%; width: 400px; height: 400px; }
    </style>
</head>
<body>

    <div class="bg-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="glass-panel login-card fade-in-up">
        <div class="text-center mb-5">
            <div class="avatar-circle mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary text-white" style="width: 64px; height: 64px; font-size: 28px; background: var(--accent-color) !important;">
                <i class="fas fa-headset"></i>
            </div>
            <h2 class="fw-bold text-white mb-1">Bem-vindo</h2>
            <p class="text-white-50 small">Entre para acessar o suporte</p>
        </div>
        
        <div id="alert-area"></div>

        <form id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-4">
                <label for="email" class="form-label text-white-50 small text-uppercase fw-bold">Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-white-50" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control bg-transparent border-start-0 text-white" id="email" name="email" placeholder="seu@email.com" required style="border-color: rgba(255,255,255,0.1);">
                </div>
            </div>
            <div class="mb-5">
                <label for="password" class="form-label text-white-50 small text-uppercase fw-bold">Senha</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-white-50" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control bg-transparent border-start-0 text-white" id="password" name="password" placeholder="••••••••" required style="border-color: rgba(255,255,255,0.1);">
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-custom shadow-lg">Entrar</button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.text();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Entrando...');
                
                $.ajax({
                    url: 'login.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            window.location.href = 'dashboard.php';
                        } else {
                            $('#alert-area').html('<div class="alert alert-danger bg-danger bg-opacity-25 text-white border-0 rounded-3 fade-in" role="alert"><i class="fas fa-exclamation-circle me-2"></i>' + response.message + '</div>');
                            btn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Erro ao conectar com o servidor.';
                        if(xhr.responseText) msg += '<br><small>' + xhr.responseText + '</small>';
                        $('#alert-area').html('<div class="alert alert-danger bg-danger bg-opacity-25 text-white border-0 rounded-3 fade-in" role="alert"><i class="fas fa-exclamation-circle me-2"></i>' + msg + '</div>');
                        btn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    </script>
</body>
</html>
