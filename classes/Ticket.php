<?php
class Ticket {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function create($data) {
        $subject = $this->conn->real_escape_string($data['subject']);
        $creator_id = (int)$data['creator_id'];
        $assigned_to_id = (int)$data['assigned_to_id'];
        $description = $this->conn->real_escape_string($data['description']);
        
        $sql = "INSERT INTO tickets (subject, creator_id, assigned_to_id, description, status) 
                VALUES ('$subject', $creator_id, $assigned_to_id, '$description', 'Criado')";
        
        if ($this->conn->query($sql)) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function addCoparticipant($ticket_id, $user_id) {
        $ticket_id = (int)$ticket_id;
        $user_id = (int)$user_id;
        return $this->conn->query("INSERT INTO ticket_coparticipants (ticket_id, user_id) VALUES ($ticket_id, $user_id)");
    }

    public function getAssignedTo($user_id) {
        $user_id = (int)$user_id;
        $sql = "SELECT t.*, u.name as creator_name, u.photo as creator_photo, a.name as assigned_name, a.photo as assigned_photo
                FROM tickets t 
                JOIN users u ON t.creator_id = u.id 
                JOIN users a ON t.assigned_to_id = a.id
                WHERE t.assigned_to_id = $user_id 
                OR t.id IN (SELECT ticket_id FROM ticket_coparticipants WHERE user_id = $user_id)
                ORDER BY t.updated_at DESC";
        $result = $this->conn->query($sql);
        $tickets = [];
        while($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getCreatedBy($user_id) {
        $user_id = (int)$user_id;
        $sql = "SELECT t.*, u.name as assigned_name, u.photo as assigned_photo, c.name as creator_name, c.photo as creator_photo
                FROM tickets t 
                JOIN users u ON t.assigned_to_id = u.id 
                JOIN users c ON t.creator_id = c.id
                WHERE t.creator_id = $user_id 
                ORDER BY t.updated_at DESC";
        $result = $this->conn->query($sql);
        $tickets = [];
        while($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT t.*, c.name as creator_name, c.photo as creator_photo, a.name as assigned_name, a.photo as assigned_photo 
                FROM tickets t 
                JOIN users c ON t.creator_id = c.id 
                JOIN users a ON t.assigned_to_id = a.id 
                WHERE t.id = $id";
        return $this->conn->query($sql)->fetch_assoc();
    }

    public function isCoparticipant($ticket_id, $user_id) {
        $ticket_id = (int)$ticket_id;
        $user_id = (int)$user_id;
        $result = $this->conn->query("SELECT * FROM ticket_coparticipants WHERE ticket_id = $ticket_id AND user_id = $user_id");
        return $result->num_rows > 0;
    }

    public function updateStatus($id, $status) {
        $id = (int)$id;
        $status = $this->conn->real_escape_string($status);
        return $this->conn->query("UPDATE tickets SET status = '$status' WHERE id = $id");
    }

    public function addMessage($data) {
        $ticket_id = (int)$data['ticket_id'];
        $user_id = (int)$data['user_id'];
        $message = $this->conn->real_escape_string($data['message']);
        $type = $this->conn->real_escape_string($data['type']);
        
        $sql = "INSERT INTO messages (ticket_id, user_id, message, type) VALUES ($ticket_id, $user_id, '$message', '$type')";
        if ($this->conn->query($sql)) {
            $this->conn->query("UPDATE tickets SET updated_at = NOW() WHERE id = $ticket_id");
            return $this->conn->insert_id;
        }
        return false;
    }

    public function addAttachment($message_id, $file_path, $file_name, $file_type) {
        $message_id = (int)$message_id;
        $file_path = $this->conn->real_escape_string($file_path);
        $file_name = $this->conn->real_escape_string($file_name);
        $file_type = $this->conn->real_escape_string($file_type);
        
        return $this->conn->query("INSERT INTO attachments (message_id, file_path, file_name, file_type) VALUES ($message_id, '$file_path', '$file_name', '$file_type')");
    }

    public function getMessages($ticket_id) {
        $ticket_id = (int)$ticket_id;
        $sql = "SELECT m.*, u.name as user_name, u.photo as user_photo, at.file_path, at.file_name 
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                LEFT JOIN attachments at ON m.id = at.message_id 
                WHERE m.ticket_id = $ticket_id 
                ORDER BY m.created_at ASC";
        $result = $this->conn->query($sql);
        $messages = [];
        while($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        return $messages;
    }
}
?>
