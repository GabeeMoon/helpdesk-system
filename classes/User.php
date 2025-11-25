<?php
class User {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function login($email, $password) {
        $email = $this->conn->real_escape_string($email);
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify MD5
            if (md5($password) === $user['password']) {
                return $user;
            }
        }
        return false;
    }

    public function find($id) {
        $id = (int)$id;
        $result = $this->conn->query("SELECT * FROM users WHERE id = $id");
        return $result->fetch_assoc();
    }

    public function update($id, $data) {
        $id = (int)$id;
        $set = [];
        foreach ($data as $key => $value) {
            $value = $this->conn->real_escape_string($value);
            $set[] = "$key = '$value'";
        }
        $setStr = implode(', ', $set);
        return $this->conn->query("UPDATE users SET $setStr WHERE id = $id");
    }

    public function create($data) {
        $keys = implode(', ', array_keys($data));
        $values = implode("', '", array_map([$this->conn, 'real_escape_string'], array_values($data)));
        return $this->conn->query("INSERT INTO users ($keys) VALUES ('$values')");
    }

    public function getAll() {
        $result = $this->conn->query("SELECT * FROM users ORDER BY name ASC");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }
    
    public function delete($id) {
        $id = (int)$id;
        return $this->conn->query("DELETE FROM users WHERE id = $id");
    }
}
?>
