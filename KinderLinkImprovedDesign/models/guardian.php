<?php
require_once '../autoload.php';

class guardian {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    public function create($name, $email, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("
            INSERT INTO guardian (guardian_name,email,password,status)
            VALUES (?,?,?,'active')
        ");
        $stmt->execute([$name,$email,$hash]);

        $stmt2 = $this->conn->prepare("
            INSERT INTO users(username,email,password,role)
            VALUES (?,?,?,'guardian')
        ");
        $stmt2->execute([$name,$email,$hash]);
    }

    public function getAll() {
        $stmt = $this->conn->prepare("
            SELECT 
                g.guardian_id,
                g.guardian_name,
                g.email,
                g.status,
                GROUP_CONCAT(CONCAT(p.first_name, ' ', p.last_name) SEPARATOR ', ') as linked_children
            FROM guardian g
            LEFT JOIN guardian_pupil gp
                ON g.guardian_id = gp.guardian_guardian_id
            LEFT JOIN pupil p
                ON gp.pupil_pupil_id = p.pupil_id
            GROUP BY g.guardian_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggle($id) {
        $stmt = $this->conn->prepare("SELECT status FROM guardian WHERE guardian_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return;
        }

        $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';

        $stmt = $this->conn->prepare("
            UPDATE guardian
            SET status = :status
            WHERE guardian_id = :id
        ");
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
    }
}
?>