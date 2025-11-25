<?php
require_once '../configs/config.php';

verificarLogin();

$ticketModel = new Ticket();
$userModel = new User();

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];
$userPerm = $_SESSION['user_permission'];

// AJAX Actions
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_details') {
        $tId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $ticket = $ticketModel->getById($tId);
        $messages = $ticketModel->getMessages($tId);
        
        // Fix photo paths
        foreach ($messages as &$msg) {
             if ($msg['user_photo'] == 'default.png' || empty($msg['user_photo'])) {
                $msg['user_photo_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($msg['user_name']) . '&background=random';
            } else {
                $msg['user_photo_url'] = (strpos($msg['user_photo'], 'http') === 0) ? $msg['user_photo'] : '../uploads/' . $msg['user_photo'];
            }
            // Fix attachment path for display
            if (!empty($msg['file_path'])) {
                $msg['file_path'] = '../' . $msg['file_path'];
            }
        }

        echo json_encode(['status' => 'success', 'ticket' => $ticket, 'messages' => $messages, 'current_user_id' => $userId]);
        exit;
    }

    if ($_GET['action'] === 'send_message') {
        $tId = $_POST['ticket_id'];
        $message = $_POST['message'];
        
        if (empty($message) && (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] != 0)) {
             echo json_encode(['status' => 'error', 'message' => 'Mensagem vazia']);
             exit;
        }

        $msgId = $ticketModel->addMessage([
            'ticket_id' => $tId,
            'user_id' => $userId,
            'message' => $message,
            'type' => 'text'
        ]);

        if ($msgId) {
            // Update Status Logic
            $ticket = $ticketModel->getById($tId);
            if ($ticket['status'] != 'Finalizado') {
                if ($userId == $ticket['assigned_to_id']) {
                    $ticketModel->updateStatus($tId, 'Respondido');
                } elseif ($userId == $ticket['creator_id']) {
                    $ticketModel->updateStatus($tId, 'Aguardando Resposta');
                }
            }

            // Attachment
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
                $uploadDir = '../uploads/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = basename($_FILES['attachment']['name']);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newName = uniqid() . '.' . $fileExt;
                $filePath = $uploadDir . $newName;
                $dbPath = 'uploads/' . $newName;

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
                    $ticketModel->addAttachment($msgId, $dbPath, $fileName, $fileExt);
                }
            }

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar mensagem.']);
        }
        exit;
    }

    if ($_GET['action'] === 'update_status') {
        $tId = $_POST['ticket_id'];
        $status = $_POST['status'];
        
        $ticket = $ticketModel->getById($tId);
        
        if ($ticket['assigned_to_id'] != $userId && $userPerm != 'Root' && $userPerm != 'Adm') {
             echo json_encode(['status' => 'error', 'message' => 'Apenas o responsável pode alterar o status.']);
             exit;
        }

        if ($ticketModel->updateStatus($tId, $status)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar status.']);
        }
        exit;
    }
}

// View Logic
$ticket = $ticketModel->getById($ticketId);

if (!$ticket) {
    header("Location: dashboard.php");
    exit;
}

$isCoparticipant = $ticketModel->isCoparticipant($ticketId, $userId);

if ($ticket['creator_id'] != $userId && $ticket['assigned_to_id'] != $userId && !$isCoparticipant && $userPerm == 'Usuario') {
    header("Location: dashboard.php?error=acesso_negado");
    exit;
}

// Pass current user ID to JS
$current_user_id = $userId;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamado #<?php echo $ticket['id']; ?> - Sistema de Chamados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container py-5 fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="dashboard.php" class="btn btn-light-custom shadow-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
                <div class="d-flex align-items-center">
                    <span class="badge rounded-pill bg-secondary fs-6 me-2" id="status-badge"><?php echo $ticket['status']; ?></span>
                </div>
            </div>
                    <div class="mt-4 d-flex justify-content-end gap-2" id="action-buttons">
                        <!-- Injected via JS based on status -->
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-container shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-comments me-2 text-primary"></i>Histórico</h5>
                </div>
                
                <div class="chat-box" id="chat-box">
                    <!-- Messages -->
                </div>
                
                <div class="p-3 bg-white border-top">
                    <form id="sendMessageForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="input-group bg-light rounded-pill border p-1">
                            <button class="btn btn-light rounded-circle border-0 text-muted mx-1" type="button" onclick="document.getElementById('attachment').click()" data-bs-toggle="tooltip" title="Anexar arquivo">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="file" id="attachment" name="attachment" style="display: none;">
                            <input type="text" class="form-control border-0 bg-transparent" name="message" placeholder="Digite sua resposta..." autocomplete="off">
                            <button class="btn btn-custom rounded-circle m-1" type="submit" style="width: 40px; height: 40px; padding: 0;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="file-name-display" class="small text-muted ms-3 mt-1"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-body text-center p-5">
                <div class="mb-4 text-warning display-1">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h4 class="fw-bold mb-3">Confirmação</h4>
                <p class="text-muted mb-4" id="confirmMessage">Tem certeza?</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light-custom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-custom" id="confirmBtn">Confirmar</button>
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
        const ticketId = <?php echo $ticket['id']; ?>;
        const currentUserId = <?php echo $current_user_id; ?>;
        let currentStatus = '<?php echo $ticket['status']; ?>';
        let assignedToId = <?php echo $ticket['assigned_to_id']; ?>;

        function loadMessages() {
            $.ajax({
                url: 'ticket_details.php?action=get_details&id=' + ticketId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        renderMessages(response.messages);
                        updateStatusUI(response.ticket.status, response.ticket.assigned_to_id);
                    }
                }
            });
        }

        function renderMessages(messages) {
            let html = '';
            messages.forEach(msg => {
                const isMe = (msg.user_id == currentUserId);
                const alignClass = isMe ? 'message-sent' : 'message-received';
                const alignDiv = isMe ? 'justify-content-end' : 'justify-content-start';
                
                let attachmentHtml = '';
                if(msg.file_path) {
                    const ext = msg.file_name.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                        attachmentHtml = `
                            <div class="mt-2">
                                <a href="${msg.file_path}" target="_blank">
                                    <img src="${msg.file_path}" class="img-fluid rounded shadow-sm" style="max-height: 200px; object-fit: cover;" alt="Anexo">
                                </a>
                            </div>`;
                    } else {
                        attachmentHtml = `<div class="mt-2 p-2 bg-white bg-opacity-25 rounded"><a href="${msg.file_path}" target="_blank" class="text-reset text-decoration-none"><i class="fas fa-file-download me-2"></i>${msg.file_name}</a></div>`;
                    }
                }

                html += `
                    <div class="d-flex ${alignDiv} mb-3 fade-in">
                        ${!isMe ? `<img src="${msg.user_photo_url}" class="avatar-circle me-2" style="width: 35px; height: 35px;">` : ''}
                        <div class="message-bubble ${alignClass}">
                            <div class="fw-bold small mb-1 opacity-75">${isMe ? 'Você' : msg.user_name}</div>
                            <div>${msg.message}</div>
                            ${attachmentHtml}
                            <div class="text-end small opacity-50 mt-1" style="font-size: 0.7rem;">${msg.created_at}</div>
                        </div>
                        ${isMe ? `<img src="${msg.user_photo_url}" class="avatar-circle ms-2" style="width: 35px; height: 35px;">` : ''}
                    </div>
                `;
            });
            $('#chat-box').html(html);
            
            // Scroll to bottom
            // var chatBox = document.getElementById("chat-box");
            // chatBox.scrollTop = chatBox.scrollHeight;
        }

        function updateStatusUI(status, assignedId) {
            currentStatus = status;
            $('#status-badge').text(status).attr('class', 'badge rounded-pill fs-6 me-2 bg-' + getStatusColor(status));
            
            // Logic: Only assigned user can change status
            let buttons = '';
            if (currentUserId == assignedId && status !== 'Finalizado') {
                if (status === 'Criado' || status === 'Aguardando Resposta' || status === 'Respondido') {
                    buttons += `<button class="btn btn-warning btn-custom text-white" onclick="confirmStatusChange('Em Analise')"><i class="fas fa-search me-2"></i>Em Análise</button>`;
                }
                if (status === 'Em Analise') {
                    buttons += `<button class="btn btn-success btn-custom" onclick="confirmStatusChange('Finalizado')"><i class="fas fa-check me-2"></i>Finalizar</button>`;
                }
            }
            $('#action-buttons').html(buttons);
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

        window.confirmStatusChange = function(newStatus) {
            $('#confirmMessage').text(`Deseja alterar o status para "${newStatus}"?`);
            $('#confirmBtn').off('click').on('click', function() {
                updateStatus(newStatus);
                $('#confirmModal').modal('hide');
            });
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        };

        function updateStatus(status) {
            $.ajax({
                url: 'ticket_details.php?action=update_status',
                type: 'POST',
                data: { ticket_id: ticketId, status: status },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        loadMessages(); // Reload to update UI
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        $('#sendMessageForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'ticket_details.php?action=send_message',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        $('input[name="message"]').val('');
                        $('#attachment').val('');
                        $('#file-name-display').text('');
                        loadMessages();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });

        $('#attachment').change(function() {
            var fileName = $(this).val().split('\\').pop();
            $('#file-name-display').text(fileName ? 'Anexo: ' + fileName : '');
        });

        loadMessages();
        setInterval(loadMessages, 5000);
    });
</script>
</body>
</html>
