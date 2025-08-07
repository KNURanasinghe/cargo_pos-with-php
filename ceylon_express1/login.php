
<?php
// login.php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = md5($_POST['password']); // In production, use password_hash()
    
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .logo-section {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .form-section {
            padding: 40px;
        }
        .btn-custom {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="login-container">
                    <div class="row no-gutters">
                        <div class="col-md-6">
                            <div class="logo-section d-flex flex-column justify-content-center h-100">
                                <h2 class="mb-4">Ceylon Express</h2>
                                <p class="mb-0">Shipping Management System</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-section">
                                <h3 class="text-center mb-4">Login</h3>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-custom btn-block w-100 text-white">Login</button>
                                </form>
                                
                                <div class="text-center mt-4">
                                    <small class="text-muted">
                                        Demo Accounts:<br>
                                        Admin: admin@ceylonexpress.com / admin123<br>
                                        User: user@ceylonexpress.com / user123
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>