
<?php
// setup.php - Database Setup Script
ini_set('display_errors', 1);
error_reporting(E_ALL);

$success_messages = [];
$error_messages = [];

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'shipping_system';

try {
    // Connect to MySQL server
    $pdo_server = new PDO("mysql:host=$host", $username, $password);
    $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $success_messages[] = "Database '$database' created successfully.";

    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create senders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS senders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mobile VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        surname VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        tax_code VARCHAR(50),
        sex ENUM('Male', 'Female', 'Other'),
        dob DATE,
        birth_country VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create receivers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS receivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_mobile VARCHAR(20),
        receiver_id VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        phone1 VARCHAR(20),
        phone2 VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create kg_rates table
    $pdo->exec("CREATE TABLE IF NOT EXISTS kg_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rate_name VARCHAR(100) NOT NULL,
        rate_per_kg DECIMAL(10, 2) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create shipments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tracking_no VARCHAR(100) UNIQUE NOT NULL,
        shipping_date DATE NOT NULL,
        sender_mobile VARCHAR(20),
        receiver_id INT,
        no_of_boxes INT DEFAULT 1,
        actual_weight DECIMAL(10, 2),
        chargeable_weight DECIMAL(10, 2),
        kg_rate_id INT,
        freight_amount DECIMAL(10, 2),
        tax_amount DECIMAL(10, 2) DEFAULT 0,
        total_payable DECIMAL(10, 2),
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create company_details table
    $pdo->exec("CREATE TABLE IF NOT EXISTS company_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) DEFAULT 'Ceylon Express',
    address TEXT,
    phone VARCHAR(100),
    email VARCHAR(100),
    logo_path VARCHAR(255),
    sinhala_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)");

    $success_messages[] = "All database tables created successfully.";

    // Insert default users
    $user_check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($user_check == 0) {
        $pdo->exec("INSERT INTO users (email, password, role) VALUES 
            ('admin@ceylonexpress.com', '" . md5('admin123') . "', 'admin'),
            ('user@ceylonexpress.com', '" . md5('user123') . "', 'user')");
        $success_messages[] = "Default user accounts created.";
    }

    // Insert default KG rates
    $rates_check = $pdo->query("SELECT COUNT(*) FROM kg_rates")->fetchColumn();
    if ($rates_check == 0) {
        $pdo->exec("INSERT INTO kg_rates (rate_name, rate_per_kg) VALUES
            ('Standard Rate', 150.00),
            ('Express Rate', 200.00),
            ('Priority Rate', 250.00),
            ('Economy Rate', 120.00)");
        $success_messages[] = "Default KG rates created.";
    }

    // Insert default company details
    $company_check = $pdo->query("SELECT COUNT(*) FROM company_details")->fetchColumn();
    if ($company_check == 0) {
    $sinhala_text = mb_convert_encoding("ඉතාලියේ සිට පැමිණෙන ගුවන් භාණ්ඩවල සංකීර්ණ පරීක්ෂාවන් හේතුවෙන් රේගු නිශ්කාශනයේ ප්‍රමාදයන් සිදු විය හැක. ඔබට ඔබේ බඩු පෙට්ටිය ගැන කිසියම් ගැටලුවක් හෝ අපැහැදිලි බවක් ඇත්නම් කරුණාකර අප සමග කතා කර ඉතාලියෙන් පෙට්ටිය පිටවන දින සිට මාසයක් ඇතුලත කරුණු විසදාගන්න
", 'UTF-8');        
        $address = "Via Principe Eugenio, 83\n00185 Roma,\nPiazza Vittorio.\nItaly - 320 695 0393/ 324 990 8069\nSri Lanka - +94 76 126 7433 / +94 76 074 5058";
        
        $stmt = $pdo->prepare("INSERT INTO company_details (company_name, address, phone, email, sinhala_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Ceylon Express', $address, '320 695 0393 / 324 990 8069', 'CEYLONEXPRESS83@gmail.com', $sinhala_text]);
        $success_messages[] = "Default company details created.";
    }

    // Create upload directories
    if (!file_exists('uploads/')) {
        mkdir('uploads/', 0777, true);
        $success_messages[] = "Upload directories created.";
    }

} catch (PDOException $e) {
    $error_messages[] = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .setup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin: 50px auto;
            max-width: 800px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .success-icon {
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="header text-center">
                <h1><i class="fas fa-shipping-fast"></i> Ceylon Express</h1>
                <p>Shipping Management System Setup</p>
            </div>

            <div class="text-center">
                <?php if (empty($error_messages)): ?>
                    <div class="status-icon success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-success mb-4">Setup Completed Successfully!</h2>
                <?php else: ?>
                    <div class="status-icon">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                    </div>
                    <h2 class="text-danger mb-4">Setup Completed with Errors</h2>
                <?php endif; ?>
            </div>

            <?php if (!empty($success_messages)): ?>
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check"></i> Success Messages</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($success_messages as $message): ?>
                            <div class="alert alert-success mb-2 py-2">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_messages)): ?>
                <div class="card border-danger mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Error Messages</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($error_messages as $message): ?>
                            <div class="alert alert-danger mb-2 py-2">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card bg-light">
                <div class="card-body">
                    <h5><i class="fas fa-info-circle text-info"></i> Default Login Credentials</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Administrator Account:</strong><br>
                            Email: <code>admin@ceylonexpress.com</code><br>
                            Password: <code>admin123</code>
                        </div>
                        <div class="col-md-6">
                            <strong>User Account:</strong><br>
                            Email: <code>user@ceylonexpress.com</code><br>
                            Password: <code>user123</code>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($error_messages)): ?>
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-success btn-lg">
                        <i class="fas fa-arrow-right me-2"></i>Continue to Application
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <small class="text-muted">
                    <strong>Note:</strong> Please change the default passwords after first login for security.
                </small>
            </div>
        </div>
    </div>
</body>
</html>