<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'KinderLink'; ?></title>
    <link rel="stylesheet" href="css/index_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<header class="navbar">
    <div class="logo">
        <div class="logo-icon"> <img src="logo.png" width="20px;"></div>
        <span>KinderLink</span>
    </div>

    <nav>
        <a href="#" class="link">Home</a>
        <a href="#about" class="link">About</a>
        <a href="#features" class="link">Features</a>
        <a href="#contact" class="link">Contact</a>
        <a href="Authentication/login.php" class="login-btn"><i class="fa-solid fa-right-to-bracket" style="margin-right:10px;"></i>Login</a>
    </nav>
</header>

<?php echo $content; ?>

</body>
</html>