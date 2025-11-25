<?php
require_once '../configs/config.php';

verificarLogin();

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    header('Content-Type: application/json');
    
    $id = $_SESSION['user_id'];
    $data = [];
    
    if (!empty($_POST['name'])) $data['name'] = $_POST['name'];
    if (!empty($_POST['email'])) $data['email'] = $_POST['email'];
    if (!empty($_POST['password'])) $data['password'] = md5($_POST['password']);
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            $data['photo'] = $fileName;
            $_SESSION['user_photo'] = $fileName;
        }
    }

    if ($userModel->update($id, $data)) {
        if(isset($data['name'])) $_SESSION['user_name'] = $data['name'];
        if(isset($data['email'])) $_SESSION['user_email'] = $data['email'];
        
        echo json_encode(['status' => 'success', 'message' => 'Perfil atualizado!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar.']);
    }
    exit;
}

$user = $userModel->find($_SESSION['user_id']);

$user_photo_url = (strpos($user['photo'], 'http') === 0) ? $user['photo'] : '../uploads/' . $user['photo'];
if ($user['photo'] == 'default.png' || empty($user['photo'])) {
    $user_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=random';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil - Sistema de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container py-5 fade-in">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="dashboard.php" class="btn btn-light btn-custom shadow-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
                <h2 class="fw-bold mb-0">Meu Perfil</h2>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <form id="profileForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="text-center mb-5 position-relative">
                            <img src="<?php echo $user_photo_url; ?>" id="preview-photo" class="avatar-circle shadow-lg" style="width: 120px; height: 120px; border: 4px solid var(--primary-color);">
                            <div class="position-absolute start-50 translate-middle-x mt-3">
                                <label for="photo" class="btn btn-sm btn-primary btn-custom shadow-sm">
                                    <i class="fas fa-camera me-2"></i>Alterar Foto
                                </label>
                                <input type="file" id="photo" name="photo" style="display: none;" accept="image/*">
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Nome</label>
                                <input type="text" class="form-control rounded-pill bg-light border-0 py-2" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Email</label>
                                <input type="email" class="form-control rounded-pill bg-light border-0 py-2" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Nova Senha</label>
                                <input type="password" class="form-control rounded-pill bg-light border-0 py-2" name="password" placeholder="Deixe em branco para manter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase">Tipo</label>
                                <input type="text" class="form-control rounded-pill bg-light border-0 py-2" value="<?php echo $user['type']; ?>" disabled>
                            </div>
                        </div>

                        <div class="text-end mt-5">
                            <button type="submit" class="btn btn-primary btn-custom px-5">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
    $(document).ready(function() {
        $('#photo').change(function() {
            const file = this.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    $('#preview-photo').attr('src', event.target.result);
                }
                reader.readAsDataURL(file);
            }
        });

        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'profile.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });
    });
</script>
</body>
</html>
