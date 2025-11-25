<?php
class Ticket {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        $subject = $data['subject'];
        $creator_id = (int)$data['creator_id'];
        $assigned_to_id = (int)$data['assigned_to_id'];
        $description = $data['description'];
        
        $sql = "INSERT INTO tickets (subject, creator_id, assigned_to_id, description, status) 
                VALUES (?, ?, ?, ?, 'Criado')";
        
        return $this->db->execute($sql, [$subject, $creator_id, $assigned_to_id, $description], "siis");
    }

    public function addCoparticipant($ticket_id, $user_id) {
        return $this->db->execute("INSERT INTO ticket_coparticipants (ticket_id, user_id) VALUES (?, ?)", [$ticket_id, $user_id], "ii");
    }

    public function getAssignedTo($user_id) {
        $sql = "SELECT t.*, u.name as creator_name, u.photo as creator_photo, a.name as assigned_name, a.photo as assigned_photo
                FROM tickets t 
                JOIN users u ON t.creator_id = u.id 
                JOIN users a ON t.assigned_to_id = a.id
                WHERE t.assigned_to_id = ? 
                OR t.id IN (SELECT ticket_id FROM ticket_coparticipants WHERE user_id = ?)
                ORDER BY t.updated_at DESC";
        
        $result = $this->db->query($sql, [$user_id, $user_id], "ii");
        $tickets = [];
        while($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getCreatedBy($user_id) {
        $sql = "SELECT t.*, u.name as assigned_name, u.photo as assigned_photo, c.name as creator_name, c.photo as creator_photo
                FROM tickets t 
                JOIN users u ON t.assigned_to_id = u.id 
                JOIN users c ON t.creator_id = c.id
                WHERE t.creator_id = ? 
                ORDER BY t.updated_at DESC";
        
        $result = $this->db->query($sql, [$user_id], "i");
        $tickets = [];
        while($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getById($id) {
        $sql = "SELECT t.*, c.name as creator_name, c.photo as creator_photo, a.name as assigned_name, a.photo as assigned_photo 
                FROM tickets t 
                JOIN users c ON t.creator_id = c.id 
                JOIN users a ON t.assigned_to_id = a.id 
                WHERE t.id = ?";
        
        $result = $this->db->query($sql, [$id], "i");
        return $result->fetch_assoc();
    }

    public function isCoparticipant($ticket_id, $user_id) {
        $result = $this->db->query("SELECT * FROM ticket_coparticipants WHERE ticket_id = ? AND user_id = ?", [$ticket_id, $user_id], "ii");
        return $result->num_rows > 0;
    }

    public function updateStatus($id, $status) {
        return $this->db->execute("UPDATE tickets SET status = ? WHERE id = ?", [$status, $id], "si");
    }

    public function addMessage($data) {
        $ticket_id = (int)$data['ticket_id'];
        $user_id = (int)$data['user_id'];
        $message = $data['message'];
        $type = $data['type'];
        
        $sql = "INSERT INTO messages (ticket_id, user_id, message, type) VALUES (?, ?, ?, ?)";
        $msgId = $this->db->execute($sql, [$ticket_id, $user_id, $message, $type], "iiss");
        
        if ($msgId) {
            $this->db->execute("UPDATE tickets SET updated_at = NOW() WHERE id = ?", [$ticket_id], "i");
            return $msgId;
        }
        return false;
    }

    public function addAttachment($message_id, $file_path, $file_name, $file_type) {
        return $this->db->execute(
            "INSERT INTO attachments (message_id, file_path, file_name, file_type) VALUES (?, ?, ?, ?)", 
            [$message_id, $file_path, $file_name, $file_type], 
            "isss"
        );
    }

    public function getMessages($ticket_id) {
        $sql = "SELECT m.*, u.name as user_name, u.photo as user_photo, at.file_path, at.file_name 
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                LEFT JOIN attachments at ON m.id = at.message_id 
                WHERE m.ticket_id = ? 
                ORDER BY m.created_at ASC";
        
        $result = $this->db->query($sql, [$ticket_id], "i");
        $messages = [];
        while($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        return $messages;
    }
}
?>
