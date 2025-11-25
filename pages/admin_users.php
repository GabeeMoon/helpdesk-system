<?php
require_once '../configs/config.php';

verificarLogin();

if ($_SESSION['user_permission'] != 'Root' && $_SESSION['user_permission'] != 'Adm') {
    header("Location: dashboard.php");
    exit;
}

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'delete_user') {
        $id = $_POST['user_id'];
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Você não pode se excluir.']);
            exit;
        }
        if ($userModel->delete($id)) {
            echo json_encode(['status' => 'success', 'message' => 'Usuário excluído!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir.']);
        }
        exit;
    }

    $name = $_POST['name'];
    $email = $_POST['email'];
    $type = $_POST['type'];
    $permission = $_POST['permission'];

    if ($_SESSION['user_permission'] != 'Root' && ($permission == 'Root' || $permission == 'Adm')) {
            echo json_encode(['status' => 'error', 'message' => 'Admins não podem criar usuários Root ou Admins.']);
            exit;
    }

    $data = [
        'name' => $name,
        'email' => $email,
        'type' => $type,
        'permission' => $permission
    ];

    if ($action === 'create_user') {
        $data['password'] = md5($_POST['password']);
        if ($userModel->create($data)) {
            echo json_encode(['status' => 'success', 'message' => 'Usuário criado!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao criar.']);
        }
    } elseif ($action === 'edit_user') {
        $id = $_POST['user_id'];
        if (!empty($_POST['password'])) {
            $data['password'] = md5($_POST['password']);
        }
        if ($userModel->update($id, $data)) {
            echo json_encode(['status' => 'success', 'message' => 'Usuário atualizado!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar.']);
        }
    }
    exit;
}

$users = $userModel->getAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários - Sistema de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container py-5 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="dashboard.php" class="btn btn-light btn-custom shadow-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
        <h2 class="fw-bold mb-0">Gerenciar Usuários</h2>
        <button class="btn btn-primary btn-custom shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Novo Usuário
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Permissão</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo $user['name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['type']; ?></td>
                            <td><span class="badge rounded-pill bg-<?php echo $user['permission'] == 'Root' ? 'danger' : ($user['permission'] == 'Adm' ? 'warning' : 'info'); ?>"><?php echo $user['permission']; ?></span></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light rounded-circle shadow-sm me-1" onclick='editUser(<?php echo json_encode($user); ?>)'>
                                    <i class="fas fa-edit text-primary"></i>
                                </button>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-light rounded-circle shadow-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-custom border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4">
                <form id="userForm">
                    <input type="hidden" name="action" id="formAction" value="create_user">
                    <input type="hidden" name="user_id" id="userId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Nome</label>
                        <input type="text" class="form-control rounded-pill bg-light border-0" name="name" id="userName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Email</label>
                        <input type="email" class="form-control rounded-pill bg-light border-0" name="email" id="userEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Senha</label>
                        <input type="password" class="form-control rounded-pill bg-light border-0" name="password" id="userPassword" placeholder="Deixe em branco para manter (edição)">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Tipo</label>
                            <select class="form-select rounded-pill bg-light border-0" name="type" id="userType" required>
                                <option value="E-commerce">E-commerce</option>
                                <option value="Comercial">Comercial</option>
                                <option value="Desenvolvedor">Desenvolvedor</option>
                                <option value="Estoque">Estoque</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Permissão</label>
                            <select class="form-select rounded-pill bg-light border-0" name="permission" id="userPermission" required>
                                <option value="Usuario">Usuário</option>
                                <option value="Adm">Adm</option>
                                <?php if($_SESSION['user_permission'] == 'Root'): ?>
                                <option value="Root">Root</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-custom">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
    function resetForm() {
        $('#userForm')[0].reset();
        $('#formAction').val('create_user');
        $('#modalTitle').text('Novo Usuário');
        $('#userId').val('');
        $('#userPassword').attr('required', true);
    }

    function editUser(user) {
        $('#formAction').val('edit_user');
        $('#modalTitle').text('Editar Usuário');
        $('#userId').val(user.id);
        $('#userName').val(user.name);
        $('#userEmail').val(user.email);
        $('#userType').val(user.type);
        $('#userPermission').val(user.permission);
        $('#userPassword').removeAttr('required');
        
        new bootstrap.Modal(document.getElementById('userModal')).show();
    }

    function deleteUser(id) {
        if(confirm('Tem certeza que deseja excluir este usuário?')) {
            $.ajax({
                url: 'admin_users.php',
                type: 'POST',
                data: { action: 'delete_user', user_id: id },
                dataType: 'json',
                success: function(response) {
                    alert(response.message);
                    if(response.status === 'success') location.reload();
                }
            });
        }
    }

    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'admin_users.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                alert(response.message);
                if(response.status === 'success') location.reload();
            }
        });
    });
</script>
</body>
</html>
