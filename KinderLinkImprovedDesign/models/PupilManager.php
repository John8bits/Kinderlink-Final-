<?php
require_once '../autoload.php';

class PupilManager {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    public function addPupil($data) {
        try {
            $this->conn->beginTransaction();

            $guardian_id = $data['guardian_id'];

            if (!empty($data['contact_number'])) {
                $stmt = $this->conn->prepare("
                    UPDATE guardian
                    SET contact_number = ?
                    WHERE guardian_id = ?
                ");
                $stmt->execute([
                    $data['contact_number'],
                    $guardian_id
                ]);
            }

            $stmt = $this->conn->prepare("
                INSERT INTO pupil
                (first_name, last_name, age, gender, birthdate, home_address, health_notes, class_class_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['age'],
                $data['gender'],
                $data['birthdate'],
                $data['home_address'],
                $data['health_notes'],
                $data['class_id']
            ]);

            $pupil_id = $this->conn->lastInsertId();

            $stmt = $this->conn->prepare("
                INSERT INTO guardian_pupil
                (guardian_guardian_id, pupil_pupil_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$guardian_id, $pupil_id]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function findGuardianByName($guardian_name) {
        $stmt = $this->conn->prepare("
            SELECT guardian_id, guardian_name
            FROM guardian
            WHERE LOWER(TRIM(guardian_name)) = LOWER(TRIM(?))
            LIMIT 1
        ");
        $stmt->execute([$guardian_name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardianHasPupilLink($guardian_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM guardian_pupil
            WHERE guardian_guardian_id = ?
        ");
        $stmt->execute([$guardian_id]);
        return $stmt->fetchColumn() > 0;
    }

    public function pupilNameExists($first_name, $last_name, $class_id, $exclude_pupil_id = null) {
        $sql = "
            SELECT COUNT(*)
            FROM pupil
            WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?))
            AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
            AND class_class_id = ?
        ";
        $params = [$first_name, $last_name, $class_id];
        if ($exclude_pupil_id) {
            $sql .= " AND pupil_id <> ?";
            $params[] = $exclude_pupil_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    public function getPupilNameValidationList($class_id) {
        $stmt = $this->conn->prepare("
            SELECT pupil_id, first_name, last_name
            FROM pupil
            WHERE class_class_id = ?
            ORDER BY first_name, last_name
        ");
        $stmt->execute([$class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGuardianValidationList() {
        $stmt = $this->conn->prepare("
            SELECT
                g.guardian_id,
                g.guardian_name,
                COUNT(gp.guardian_pupil_id) AS linked_count,
                MAX(gp.pupil_pupil_id) AS linked_pupil_id
            FROM guardian g
            LEFT JOIN guardian_pupil gp
                ON g.guardian_id = gp.guardian_guardian_id
            GROUP BY g.guardian_id, g.guardian_name
            ORDER BY g.guardian_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByClass($class_id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, g.guardian_name, g.contact_number
            FROM pupil p
            LEFT JOIN guardian_pupil gp ON p.pupil_id = gp.pupil_pupil_id
            LEFT JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
            WHERE p.class_class_id = ?
        ");

        $stmt->execute([$class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePupil($pupil_id, $data) {
        try {
            $this->conn->beginTransaction();

            // Update pupil details
            $stmt = $this->conn->prepare("
                UPDATE pupil
                SET first_name = ?, last_name = ?, age = ?, gender = ?, birthdate = ?, home_address = ?, health_notes = ?
                WHERE pupil_id = ?
            ");
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['age'],
                $data['gender'],
                $data['birthdate'],
                $data['home_address'],
                $data['health_notes'],
                $pupil_id
            ]);

            if (!empty($data['guardian_id'])) {
                $stmt = $this->conn->prepare("
                    UPDATE guardian
                    SET contact_number = ?
                    WHERE guardian_id = ?
                ");
                $stmt->execute([
                    $data['contact_number'],
                    $data['guardian_id']
                ]);

                $stmt = $this->conn->prepare("
                    UPDATE guardian_pupil
                    SET guardian_guardian_id = ?
                    WHERE pupil_pupil_id = ?
                ");
                $stmt->execute([
                    $data['guardian_id'],
                    $pupil_id
                ]);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getPupilById($pupil_id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, g.guardian_name, g.contact_number
            FROM pupil p
            LEFT JOIN guardian_pupil gp ON p.pupil_id = gp.pupil_pupil_id
            LEFT JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
            WHERE p.pupil_id = ?
        ");
        $stmt->execute([$pupil_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
