<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($email, $password) {
        $result = $this->db->query("SELECT * FROM users WHERE email = ?", [$email], "s");

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify Password (supports both MD5 for legacy and Bcrypt for new)
            if (password_verify($password, $user['password']) || md5($password) === $user['password']) {
                // If it was MD5, we should probably update it to Bcrypt on login
                if (md5($password) === $user['password']) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $this->update($user['id'], ['password' => $newHash]);
                }
                return $user;
            }
        }
        return false;
    }

    public function find($id) {
        $result = $this->db->query("SELECT * FROM users WHERE id = ?", [$id], "i");
        return $result->fetch_assoc();
    }

    public function update($id, $data) {
        $set = [];
        $params = [];
        $types = "";

        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $params[] = $value;
            $types .= "s";
        }
        
        $params[] = $id;
        $types .= "i";

        $setStr = implode(', ', $set);
        return $this->db->execute("UPDATE users SET $setStr WHERE id = ?", $params, $types);
    }

    public function create($data) {
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $params = array_values($data);
        $types = str_repeat('s', count($params));

        return $this->db->execute("INSERT INTO users ($keys) VALUES ($placeholders)", $params, $types);
    }

    public function getAll() {
        $result = $this->db->query("SELECT * FROM users ORDER BY name ASC");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }
    
    public function delete($id) {
        return $this->db->execute("DELETE FROM users WHERE id = ?", [$id], "i");
    }
}
?>
