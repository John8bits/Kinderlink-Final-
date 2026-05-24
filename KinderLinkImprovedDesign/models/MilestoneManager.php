<?php
require_once '../autoload.php';

class MilestoneManager {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    //Add a new milestone for a pupil
    public function addMilestone($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO milestone 
                (title, description)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $data['title'],
                $data['description'] ?? ''
            ]);

            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error adding milestone: " . $e->getMessage());
        }
    }

    
    public function getAllMilestones() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM milestone
                ORDER BY title ASC
            ");

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching milestones: " . $e->getMessage());
        }
    }

    public function getPupilMilestones($pupil_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT m.milestone_id, m.title, m.description,
                       pm.status, pm.date_completed
                FROM milestone m
                LEFT JOIN (
                    SELECT pm_inner.MILESTONE_milestone_id,
                           pm_inner.status,
                           pm_inner.date_completed
                    FROM pupil_milestone pm_inner
                    JOIN (
                        SELECT MILESTONE_milestone_id, MAX(pupil_milestone_id) AS max_id
                        FROM pupil_milestone
                        WHERE PUPIL_pupil_id = ?
                        GROUP BY MILESTONE_milestone_id
                    ) latest ON pm_inner.pupil_milestone_id = latest.max_id
                ) pm ON m.milestone_id = pm.MILESTONE_milestone_id
                ORDER BY m.milestone_id ASC
            ");

            $stmt->execute([$pupil_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching pupil milestones: " . $e->getMessage());
        }
    }

    public function savePupilMilestones($pupil_id, $statuses) {
        try {
            $this->conn->beginTransaction();
            $selectStmt = $this->conn->prepare("
                SELECT pupil_milestone_id
                FROM pupil_milestone
                WHERE PUPIL_pupil_id = ?
                  AND MILESTONE_milestone_id = ?
                ORDER BY pupil_milestone_id DESC
            ");

            $updateStmt = $this->conn->prepare("
                UPDATE pupil_milestone
                SET status = ?, date_completed = ?
                WHERE pupil_milestone_id = ?
            ");

            $insertStmt = $this->conn->prepare("
                INSERT INTO pupil_milestone
                    (PUPIL_pupil_id, MILESTONE_milestone_id, status, date_completed)
                VALUES (?, ?, ?, ?)
            ");

            $deleteDuplicatesStmt = $this->conn->prepare("
                DELETE FROM pupil_milestone
                WHERE PUPIL_pupil_id = ?
                  AND MILESTONE_milestone_id = ?
                  AND pupil_milestone_id <> ?
            ");

            foreach ($statuses as $milestone_id => $status) {
                $dateCompleted = $status === 'Completed' ? date('Y-m-d H:i:s') : null;
                $selectStmt->execute([$pupil_id, $milestone_id]);
                $rows = $selectStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                if (!empty($rows)) {
                    $keepId = (int) $rows[0];
                    if (count($rows) > 1) {
                        $deleteDuplicatesStmt->execute([$pupil_id, $milestone_id, $keepId]);
                    }
                    $updateStmt->execute([$status, $dateCompleted, $keepId]);
                } else {
                    $insertStmt->execute([$pupil_id, $milestone_id, $status, $dateCompleted]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new Exception("Error saving pupil milestones: " . $e->getMessage());
        }
    }

    //Get milestones for a specific pupil
    public function getByPupil($pupil_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM milestone
                ORDER BY milestone_id ASC
            ");

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching pupil milestones: " . $e->getMessage());
        }
    }

    //Get a single milestone by ID
    public function getMilestoneById($milestone_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM milestone
                WHERE milestone_id = ?
            ");

            $stmt->execute([$milestone_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching milestone: " . $e->getMessage());
        }
    }

    //Get a milestone by title
    public function getMilestoneByTitle($title) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM milestone
                WHERE title = ?
            ");

            $stmt->execute([$title]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error fetching milestone by title: " . $e->getMessage());
        }
    }

    //Update milestone
    public function updateMilestone($milestone_id, $data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE milestone
                SET title = ?, description = ?
                WHERE milestone_id = ?
            ");

            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $milestone_id
            ]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Error updating milestone: " . $e->getMessage());
        }
    }

    //Delete a milestone
    public function deleteMilestone($milestone_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM milestone WHERE milestone_id = ?");
            $stmt->execute([$milestone_id]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Error deleting milestone: " . $e->getMessage());
        }
    }

    //Get milestone categories (static method for reference)
    public static function getCategories() {
        return [
            'Cognitive' => 'Cognitive Development',
            'Social' => 'Social & Emotional',
            'Physical' => 'Physical Development',
            'Language' => 'Language & Communication',
            'Creative' => 'Creative & Artistic',
            'Behavioral' => 'Behavioral Milestones'
        ];
    }

}

?>
