<?php
require_once '../configs/config.php';

verificarLogin();

$ticketModel = new Ticket();
$userModel = new User();

// Handle AJAX Requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_tickets') {
        $userId = $_SESSION['user_id'];
        $assigned = $ticketModel->getAssignedTo($userId);
        $created = $ticketModel->getCreatedBy($userId);
        
        // Calculate Stats
        $stats = [
            'total_assigned' => count($assigned),
            'pending_assigned' => 0,
            'total_created' => count($created),
            'pending_created' => 0
        ];

        foreach($assigned as $t) {
            if($t['status'] != 'Finalizado') $stats['pending_assigned']++;
        }
        foreach($created as $t) {
            if($t['status'] != 'Finalizado') $stats['pending_created']++;
        }

        echo json_encode(['status' => 'success', 'assigned' => $assigned, 'created' => $created, 'stats' => $stats]);
        exit;
    }

    if ($_GET['action'] === 'get_users') {
        $users = $userModel->getAll();
        echo json_encode(['status' => 'success', 'users' => $users]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create_ticket') {
    header('Content-Type: application/json');
    
    $data = [
        'subject' => $_POST['subject'],
        'creator_id' => $_SESSION['user_id'],
        'assigned_to_id' => $_POST['assigned_to'],
        'description' => $_POST['description']
    ];

    $ticketId = $ticketModel->create($data);

    if ($ticketId) {
        // Coparticipants
        if (isset($_POST['coparticipants'])) {
            foreach ($_POST['coparticipants'] as $copId) {
                $ticketModel->addCoparticipant($ticketId, $copId);
            }
        }

        // Initial Message
        $msgId = $ticketModel->addMessage([
            'ticket_id' => $ticketId,
            'user_id' => $_SESSION['user_id'],
            'message' => $data['description'],
            'type' => 'text'
        ]);

        // Attachment
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $uploadDir = '../uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = basename($_FILES['attachment']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newName = uniqid() . '.' . $fileExt;
            $filePath = $uploadDir . $newName;
            
            // Store relative path for DB
            $dbPath = 'uploads/' . $newName;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
                $ticketModel->addAttachment($msgId, $dbPath, $fileName, $fileExt);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Chamado criado com sucesso!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao criar chamado.']);
    }
    exit;
}

// View Data
$user_name = $_SESSION['user_name'];
$user_photo = $_SESSION['user_photo'];
$user_permission = $_SESSION['user_permission'];

$user_photo_url = (strpos($user_photo, 'http') === 0) ? $user_photo : '../uploads/' . $user_photo;
if ($user_photo == 'default.png' || empty($user_photo)) {
    $user_photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=random';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="sidebar border-end" id="sidebar-wrapper" style="width: 260px; position: fixed; height: 100%; overflow-y: auto;">
        <div class="sidebar-heading text-center py-4 primary-text fs-4 fw-bold border-bottom">
            <i class="fas fa-headset me-2 text-primary"></i>Suporte
        </div>
        
        <div class="text-center py-4">
            <img src="<?php echo htmlspecialchars($user_photo_url); ?>" class="avatar-circle mb-3" style="width: 80px; height: 80px;">
            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user_name); ?></h6>
            <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_permission']); ?></small>
        </div>

        <div class="list-group list-group-flush my-3 mx-3">
            <a href="dashboard.php" class="list-group-item list-group-item-action active">
                <i class="fas fa-tachometer-alt me-3"></i>Dashboard
            </a>
            <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                <i class="fas fa-plus-circle me-3"></i>Novo Chamado
            </a>
            <?php if($user_permission == 'Root' || $user_permission == 'Adm'): ?>
            <a href="admin_users.php" class="list-group-item list-group-item-action">
                <i class="fas fa-users-cog me-3"></i>Usuários
            </a>
            <?php endif; ?>
            <a href="profile.php" class="list-group-item list-group-item-action">
                <i class="fas fa-user-cog me-3"></i>Perfil
            </a>
            <a href="login.php?logout=true" class="list-group-item list-group-item-action text-danger mt-3">
                <i class="fas fa-power-off me-3"></i>Sair
            </a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper" style="margin-left: 260px; width: calc(100% - 260px); transition: all 0.3s;">
        <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4 border-bottom">
            <div class="d-flex align-items-center">
                <button class="btn btn-light-custom me-3 d-lg-none" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <h2 class="fs-2 m-0 fw-bold">Dashboard</h2>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item me-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkModeToggle">
                            <label class="form-check-label" for="darkModeToggle"><i class="fas fa-moon"></i></label>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle second-text fw-bold d-flex align-items-center" href="#" id="navbarDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo $user_photo_url; ?>" alt="Profile" class="avatar-circle me-2" style="width: 35px; height: 35px;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-custom" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php">Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="login.php?logout=true">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid px-4 pt-4 fade-in">
            
            <!-- Stats Widgets -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3 text-primary">
                                <i class="fas fa-inbox fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Recebidos</h6>
                                <h3 class="fw-bold mb-0" id="stat-assigned">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3 text-warning">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Pendentes</h6>
                                <h3 class="fw-bold mb-0" id="stat-pending-assigned">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3 text-info">
                                <i class="fas fa-paper-plane fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Enviados</h6>
                                <h3 class="fw-bold mb-0" id="stat-created">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3 text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-0">Resolvidos (Env)</h6>
                                <h3 class="fw-bold mb-0" id="stat-resolved-created">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-pills mb-4" id="ticketTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 me-2" id="assigned-tab" data-bs-toggle="tab" data-bs-target="#assigned" type="button" role="tab">
                        <i class="fas fa-inbox me-2"></i>Recebidos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4" id="created-tab" data-bs-toggle="tab" data-bs-target="#created" type="button" role="tab">
                        <i class="fas fa-paper-plane me-2"></i>Enviados
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="ticketTabsContent">
                <div class="tab-pane fade show active" id="assigned" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-responsive-card">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Assunto</th>
                                            <th>Criado por</th>
                                            <th>Responsável</th>
                                            <th>Status</th>
                                            <th>Atualizado em</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="assigned-tickets-list">
                                        <!-- Skeleton Loader will be here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="created" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-responsive-card">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Assunto</th>
                                            <th>Criado por</th>
                                            <th>Responsável</th>
                                            <th>Status</th>
                                            <th>Atualizado em</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="created-tickets-list">
                                        <!-- Skeleton Loader will be here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Ticket Modal -->
<div class="modal fade" id="createTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Novo Chamado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4">
                <form id="createTicketForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_ticket">
                    <div class="mb-3">
                        <label class="form-label small text-uppercase text-muted">Assunto</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-uppercase text-muted">Para</label>
                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                <!-- AJAX -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-uppercase text-muted">Coparticipantes</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="coparticipant-search" placeholder="Pesquisar usuário...">
                                <div id="search-results" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <div id="selected-tags" class="d-flex flex-wrap gap-2 mt-2"></div>
                            <div id="hidden-inputs"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-uppercase text-muted">Descrição</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-uppercase text-muted">Anexo</label>
                        <input class="form-control" type="file" name="attachment">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-custom">Criar Chamado</button>
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
    $(document).ready(function() {
        // Initial Skeleton Load
        showSkeleton('#assigned-tickets-list', 5);
        showSkeleton('#created-tickets-list', 5);

        // Load Tickets
        function loadTickets() {
            $.ajax({
                url: 'dashboard.php?action=get_tickets',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        renderTable('#assigned-tickets-list', response.assigned);
                        renderTable('#created-tickets-list', response.created);
                        
                        // Update Stats
                        $('#stat-assigned').text(response.stats.total_assigned);
                        $('#stat-pending-assigned').text(response.stats.pending_assigned);
                        $('#stat-created').text(response.stats.total_created);
                        $('#stat-resolved-created').text(response.stats.total_created - response.stats.pending_created);
                    }
                }
            });
        }

        function renderTable(selector, data) {
            let html = '';
            if(data.length > 0) {
                data.forEach(t => {
                    const creatorPhoto = getPhotoUrl(t.creator_photo, t.creator_name);
                    const assignedPhoto = getPhotoUrl(t.assigned_photo, t.assigned_name);
                    const date = formatDate(t.updated_at || t.created_at);

                    html += `
                        <tr onclick="window.location='ticket_details.php?id=${t.id}'" style="cursor: pointer;">
                            <td class="ps-4 fw-bold" data-label="Assunto">${t.subject}</td>
                            <td data-label="Criado por">
                                <div class="d-flex align-items-center">
                                    <img src="${creatorPhoto}" class="avatar-circle me-2" style="width: 30px; height: 30px;">
                                    <span>${t.creator_name}</span>
                                </div>
                            </td>
                            <td data-label="Responsável">
                                <div class="d-flex align-items-center">
                                    <img src="${assignedPhoto}" class="avatar-circle me-2" style="width: 30px; height: 30px;">
                                    <span>${t.assigned_name}</span>
                                </div>
                            </td>
                            <td data-label="Status"><span class="badge bg-${getStatusColor(t.status)}">${t.status}</span></td>
                            <td class="text-muted small" data-label="Atualizado em">${date}</td>
                            <td class="text-end pe-4" data-label="Ações">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center py-4 text-muted">Nenhum chamado encontrado.</td></tr>';
            }
            $(selector).html(html);
        }

        function getPhotoUrl(photo, name) {
            if (!photo || photo === 'default.png') {
                return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(name) + '&background=random';
            }
            return (photo.startsWith('http') ? photo : '../uploads/' + photo);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }

        function getStatusColor(status) {
            switch(status) {
                case 'Criado': return 'secondary';
                case 'Em Analise': return 'warning';
                case 'Respondido': return 'info';
                case 'Aguardando Resposta': return 'primary';
                case 'Finalizado': return 'success';
                default: return 'secondary';
            }
        }

        loadTickets();

        // Load Users & Search Logic
        let allUsers = [];
        let selectedUsers = [];

        $.ajax({
            url: 'dashboard.php?action=get_users',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    allUsers = response.users;
                    
                    // Populate Assigned To (Standard Select)
                    let options = '<option value="">Selecione...</option>';
                    allUsers.forEach(user => {
                        options += `<option value="${user.id}">${user.name} (${user.type})</option>`;
                    });
                    $('#assigned_to').html(options);
                }
            }
        });

        // Search Input Handler
        $('#coparticipant-search').on('input', function() {
            const term = $(this).val().toLowerCase();
            const resultsContainer = $('#search-results');
            resultsContainer.empty().hide();

            if (term.length > 0) {
                const filtered = allUsers.filter(u => 
                    u.name.toLowerCase().includes(term) && 
                    !selectedUsers.includes(u.id) &&
                    u.id != <?php echo $_SESSION['user_id']; ?> // Exclude self
                );

                if (filtered.length > 0) {
                    filtered.forEach(u => {
                        resultsContainer.append(`
                            <a href="#" class="list-group-item list-group-item-action" onclick="addCoparticipant(${u.id}, '${u.name}')">
                                ${u.name} <small class="text-muted">(${u.type})</small>
                            </a>
                        `);
                    });
                    resultsContainer.show();
                }
            }
        });

        // Add Coparticipant
        window.addCoparticipant = function(id, name) {
            if (!selectedUsers.includes(id)) {
                selectedUsers.push(id);
                renderTags();
                $('#coparticipant-search').val('');
                $('#search-results').hide();
            }
        };

        // Remove Coparticipant
        window.removeCoparticipant = function(id) {
            selectedUsers = selectedUsers.filter(uId => uId !== id);
            renderTags();
        };

        function renderTags() {
            const tagsContainer = $('#selected-tags');
            const inputsContainer = $('#hidden-inputs');
            
            tagsContainer.empty();
            inputsContainer.empty();

            selectedUsers.forEach(id => {
                const user = allUsers.find(u => u.id == id);
                if (user) {
                    tagsContainer.append(`
                        <span class="badge bg-primary rounded-pill pe-2">
                            ${user.name}
                            <i class="fas fa-times ms-2" style="cursor: pointer;" onclick="removeCoparticipant(${id})"></i>
                        </span>
                    `);
                    inputsContainer.append(`<input type="hidden" name="coparticipants[]" value="${id}">`);
                }
            });
        }

        // Hide search on click outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#coparticipant-search, #search-results').length) {
                $('#search-results').hide();
            }
        });

        // Create Ticket
        $('#createTicketForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'dashboard.php?action=create_ticket',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        $('#createTicketModal').modal('hide');
                        $('#createTicketForm')[0].reset();
                        loadTickets();
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
