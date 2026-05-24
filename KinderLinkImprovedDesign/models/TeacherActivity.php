<?php

class TeacherActivity {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
        $this->ensureTable();
    }

    private function ensureTable() {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS teacher_activity_log (
                activity_id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_teacher_id INT NOT NULL,
                class_class_id INT NULL,
                action_type VARCHAR(60) NOT NULL,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                activity_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_activity_time (activity_time),
                INDEX idx_teacher_time (teacher_teacher_id, activity_time),
                INDEX idx_class_time (class_class_id, activity_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function log($teacherId, $classId, $actionType, $title, $description = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO teacher_activity_log
                    (teacher_teacher_id, class_class_id, action_type, title, description, activity_time)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                (int) $teacherId,
                $classId !== null ? (int) $classId : null,
                (string) $actionType,
                (string) $title,
                (string) $description
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function logByTeacherEmail($email, $actionType, $title, $description = '') {
        if (empty($email)) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT t.teacher_id, c.class_id
                FROM teacher t
                LEFT JOIN class c ON t.teacher_id = c.teacher_teacher_id
                WHERE t.email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                return false;
            }

            return $this->log(
                $teacher['teacher_id'],
                $teacher['class_id'] ?? null,
                $actionType,
                $title,
                $description
            );
        } catch (Exception $e) {
            return false;
        }
    }

    public function getTodayActivities($limit = 30) {
        $stmt = $this->conn->prepare("
            SELECT
                al.activity_id,
                al.action_type,
                al.title,
                al.description,
                al.activity_time,
                t.teacher_name,
                c.class_name
            FROM teacher_activity_log al
            JOIN teacher t ON al.teacher_teacher_id = t.teacher_id
            LEFT JOIN class c ON al.class_class_id = c.class_id
            WHERE DATE(al.activity_time) = CURDATE()
            ORDER BY al.activity_time DESC, al.activity_id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
