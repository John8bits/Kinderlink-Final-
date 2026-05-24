<?php
require_once '../autoload.php';

class teacher
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->conn;
    }

    // CREATE
    public function create($name, $email, $password, $class)
    {
        $this->conn->beginTransaction();

        try {
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);

            // insert into TEACHER
            $stmt = $this->conn->prepare("
            INSERT INTO teacher (teacher_name, email, password)
            VALUES (:name, :email, :password)
        ");

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => $hashedPass
            ]);

            $teacher_id = $this->conn->lastInsertId();

            // insert class
            $stmt2 = $this->conn->prepare("
            INSERT INTO class (class_name, teacher_teacher_id)
            VALUES (:class, :teacher_id)
        ");

            $stmt2->execute([
                ':class' => $class,
                ':teacher_id' => $teacher_id
            ]);

            // insert into USERS
            $stmt3 = $this->conn->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (:username, :email, :password, 'teacher')
        ");

            $stmt3->execute([
                ':username' => $name,
                ':email' => $email,
                ':password' => $hashedPass
            ]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // READ
    public function getAll()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                t.teacher_id,
                t.teacher_name,
                t.email,
                t.status,
                c.class_name
            FROM teacher t
            LEFT JOIN class c 
            ON t.teacher_id = c.teacher_teacher_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($id, $name, $email, $class)
    {
        $stmt = $this->conn->prepare("
            UPDATE teacher
            SET teacher_name = :name,
                email = :email
            WHERE teacher_id = :id
        ");

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':id' => $id
        ]);

        $stmt2 = $this->conn->prepare("
            UPDATE class
            SET class_name = :class
            WHERE teacher_teacher_id = :id
        ");

        $stmt2->execute([
            ':class' => $class,
            ':id' => $id
        ]);
    }

    // SOFT DELETE
    public function softDelete($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE teacher
            SET status = 'inactive'
            WHERE teacher_id = :id
        ");

        $stmt->execute([':id' => $id]);
    }

    public function activate($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE teacher
            SET status = 'active'
            WHERE teacher_id = :id
        ");

        $stmt->execute([':id' => $id]);
    }
}
?>