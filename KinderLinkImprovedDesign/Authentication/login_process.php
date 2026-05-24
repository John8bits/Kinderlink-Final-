<?php   
    //session_start();
    require_once '../autoload.php';

    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        $email = trim($_POST['email']);
        $pass  = trim($_POST['password']);

        $errors = [];

        // Check if fields are empty
        if (empty($email)) {
            $errors['empty_email'] = "Email is required.";
        }

        if (empty($pass)) {
            $errors['empty_pass'] = "Password is required.";
        }

        if (empty($errors)) {

            $db   = new Database();
            $conn = $db->conn;

            // Check if the email exists in the database (admin only)
            // $stmt = $conn->prepare("SELECT * FROM USERS WHERE email = :email AND role = 'admin' LIMIT 1");
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                // Email not found
                $_SESSION['login_error'] = "No account found with that email address.";
                header("Location: login.php");
                exit;
            }

            if ($user['role'] === 'teacher') {
                $statusStmt = $conn->prepare("SELECT status FROM teacher WHERE email = :email LIMIT 1");
                $statusStmt->execute([':email' => $email]);
                $statusRow = $statusStmt->fetch();

                if (!$statusRow || $statusRow['status'] !== 'active') {
                    $_SESSION['login_error'] = "This teacher account is deactivated. Contact admin to reactivate.";
                    header("Location: login.php");
                    exit;
                }
            } elseif ($user['role'] === 'guardian') {
                $statusStmt = $conn->prepare("SELECT status FROM guardian WHERE email = :email LIMIT 1");
                $statusStmt->execute([':email' => $email]);
                $statusRow = $statusStmt->fetch();

                if (!$statusRow || $statusRow['status'] !== 'active') {
                    $_SESSION['login_error'] = "This guardian account is deactivated. Contact admin to reactivate.";
                    header("Location: login.php");
                    exit;
                }
            }

            if (!password_verify($pass, $user['password'])) {

                $_SESSION['login_error'] = "Incorrect password. Please try again.";
                header("Location: login.php");
                exit;

            } else {
               
                // session_regenerate_id(true); // Uncomment for security in production

                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = $user['role'];

                $_SESSION['login_success'] = true;

                if ($user['role'] == 'admin') {
                    header("Location: ../dashboards/admin_dashb.php");
                } elseif ($user['role'] == 'teacher') {
                    header("Location: ../dashboards/teacher_dashb.php");
                } elseif ($user['role'] == 'guardian') {
                    header("Location: ../dashboards/guardian_dashb.php");
                }

                exit;
            }

        } else {
            $_SESSION['errors'] = $errors;
            header('Location: login.php');
            exit;
        }

    }
?>