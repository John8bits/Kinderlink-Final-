<?php
session_start();

$errors = $_SESSION['errors'] ?? [];
$login_error = $_SESSION['login_error'] ?? '';
$login_success = $_SESSION['login_success'] ?? false;

session_unset();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KinderLink</title>

    <link rel="stylesheet" href="../css/login_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>

    <div class="login-wrapper">

        <a href="../index.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
        </a>

        <div class="login-container">

            <div class="logo">
                <img src="../logo.png">
                <h1>KinderLink</h1>
            </div>

            <p class="subtitle">Welcome back! Sign in to continue</p>

            <div class="login-box">
                <h2>Sign In</h2>
                <p class="desc">Enter your credentials to access your account</p>

                <?php if (!empty($login_error)): ?>
                    <div class="error-box">
                        <p><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($login_error); ?></p>
                    </div>
                <?php endif; ?>

                <form action="login_process.php" method="POST">

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Enter your email">
                    </div>

                    <?php if (!empty($errors['empty_email'])): ?>
                        <span class="field-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?= htmlspecialchars($errors['empty_email']); ?>
                        </span>
                    <?php endif; ?>

                    <div class="form-group password-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" placeholder="Enter your password">
                            <i class="fa-solid fa-eye toggle-password" id="eye" onclick="togglePassword()"></i>
                        </div>
                    </div>

                    <?php if (!empty($errors['empty_pass'])): ?>
                        <span class="field-error">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <?= htmlspecialchars($errors['empty_pass']); ?>
                        </span>
                    <?php endif; ?>

                    <button type="submit" class="sign-in">Sign In</button>

                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eye = document.getElementById("eye");
        
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eye.classList.remove("fa-eye");
                eye.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eye.classList.remove("fa-eye-slash");
                eye.classList.add("fa-eye");
            }
        }

        <?php if (!empty($login_error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: <?= json_encode($login_error, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
                confirmButtonColor: '#4361ee'
            });
        <?php endif; ?>
    </script>
</body>

</html>
