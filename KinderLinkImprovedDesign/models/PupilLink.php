<?php
require_once '../autoload.php';

class PupilLink {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    public function getGuardians() {
        return $this->conn->query("
            SELECT
                g.*,
                COUNT(gp.guardian_pupil_id) AS linked_count,
                GROUP_CONCAT(CONCAT(p.first_name, ' ', p.last_name) ORDER BY p.first_name SEPARATOR ', ') AS linked_pupils
            FROM guardian g
            LEFT JOIN guardian_pupil gp ON g.guardian_id = gp.guardian_guardian_id
            LEFT JOIN pupil p ON gp.pupil_pupil_id = p.pupil_id
            GROUP BY g.guardian_id
            ORDER BY g.guardian_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPupils() {
        return $this->conn->query("
            SELECT
                p.*,
                COUNT(gp.guardian_pupil_id) AS linked_count,
                GROUP_CONCAT(g.guardian_name ORDER BY g.guardian_name SEPARATOR ', ') AS linked_guardians
            FROM pupil p
            LEFT JOIN guardian_pupil gp ON p.pupil_id = gp.pupil_pupil_id
            LEFT JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
            GROUP BY p.pupil_id
            ORDER BY p.first_name, p.last_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardianHasLink($guardian) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM guardian_pupil
            WHERE guardian_guardian_id = ?
        ");
        $stmt->execute([$guardian]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function pupilHasLink($pupil) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM guardian_pupil
            WHERE pupil_pupil_id = ?
        ");
        $stmt->execute([$pupil]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function pairExists($guardian, $pupil) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM guardian_pupil
            WHERE guardian_guardian_id = ?
            AND pupil_pupil_id = ?
        ");
        $stmt->execute([$guardian, $pupil]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function link($guardian, $pupil) {
        $stmt = $this->conn->prepare("
            INSERT INTO guardian_pupil
            (guardian_guardian_id, pupil_pupil_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$guardian, $pupil]);
    }

    public function getLinks() {
        $stmt = $this->conn->prepare("
            SELECT
                gp.guardian_pupil_id,
                g.guardian_name,
                CONCAT(p.first_name,' ',p.last_name) as pupil_name,
                COALESCE(CONCAT(t.teacher_name, ' / ', c.class_name), c.class_name, 'No class assigned') as teacher_class
            FROM guardian_pupil gp
            JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
            JOIN pupil p ON gp.pupil_pupil_id = p.pupil_id
            LEFT JOIN class c ON p.class_class_id = c.class_id
            LEFT JOIN teacher t ON c.teacher_teacher_id = t.teacher_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeLink($id) {
        $stmt = $this->conn->prepare("
            DELETE FROM guardian_pupil WHERE guardian_pupil_id = ?
        ");
        $stmt->execute([$id]);
    }
}
?>
