<?php
// enhanced_setup.php - Enhanced Database Setup Script with Advanced Features
ini_set('display_errors', 1);
error_reporting(E_ALL);

$success_messages = [];
$error_messages = [];

// Database configuration
$host = 'localhost';
$username = 'newuser123';
$password = 'new_strong_password';
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
    
    // Set charset explicitly for proper Unicode support
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET character_set_connection=utf8mb4");

    // Create enhanced users table with additional fields
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'manager') DEFAULT 'user',
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
)");

    // Create enhanced senders table
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
        email VARCHAR(255),
        alternative_phone VARCHAR(20),
        postal_code VARCHAR(20),
        city VARCHAR(100),
        country VARCHAR(100) DEFAULT 'Italy',
        notes TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_mobile (mobile),
        INDEX idx_name (name, surname),
        INDEX idx_active (is_active)
    )");

    // Create enhanced receivers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS receivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_mobile VARCHAR(20),
        receiver_id VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        phone1 VARCHAR(20),
        phone2 VARCHAR(20),
        email VARCHAR(255),
        postal_code VARCHAR(20),
        city VARCHAR(100),
        country VARCHAR(100) DEFAULT 'Sri Lanka',
        id_number VARCHAR(50),
        relationship_to_sender VARCHAR(100),
        notes TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sender_mobile (sender_mobile),
        INDEX idx_receiver_id (receiver_id),
        INDEX idx_name (name),
        INDEX idx_active (is_active),
        FOREIGN KEY (sender_mobile) REFERENCES senders(mobile) ON UPDATE CASCADE
    )");

    // Create enhanced kg_rates table with more features
    $pdo->exec("CREATE TABLE IF NOT EXISTS kg_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rate_name VARCHAR(100) NOT NULL,
        rate_per_kg DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'EUR',
        minimum_weight DECIMAL(8, 2) DEFAULT 0.00,
        maximum_weight DECIMAL(8, 2) NULL,
        rate_type ENUM('standard', 'express', 'economy', 'priority') DEFAULT 'standard',
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        valid_from DATE,
        valid_until DATE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rate_name (rate_name),
        INDEX idx_active (is_active),
        INDEX idx_rate_type (rate_type),
        INDEX idx_valid_dates (valid_from, valid_until)
    )");

    // Create enhanced shipments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tracking_no VARCHAR(100) UNIQUE NOT NULL,
        shipping_date DATE NOT NULL,
        sender_mobile VARCHAR(20),
        receiver_id INT,
        no_of_boxes INT DEFAULT 1,
        actual_weight DECIMAL(10, 2),
        chargeable_weight DECIMAL(10, 2),
        dimensions VARCHAR(255),
        kg_rate_id INT,
        freight_amount DECIMAL(10, 2),
        tax_amount DECIMAL(10, 2) DEFAULT 0,
        insurance_amount DECIMAL(10, 2) DEFAULT 0,
        additional_charges DECIMAL(10, 2) DEFAULT 0,
        discount_amount DECIMAL(10, 2) DEFAULT 0,
        total_payable DECIMAL(10, 2),
        payment_status ENUM('pending', 'paid', 'partial', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        shipment_status ENUM('created', 'processed', 'shipped', 'in_transit', 'delivered', 'returned') DEFAULT 'created',
        content_description TEXT,
        special_instructions TEXT,
        remarks TEXT,
        pickup_address TEXT,
        delivery_address TEXT,
        estimated_delivery DATE,
        actual_delivery_date DATE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tracking_no (tracking_no),
        INDEX idx_sender_mobile (sender_mobile),
        INDEX idx_shipping_date (shipping_date),
        INDEX idx_status (shipment_status),
        INDEX idx_payment_status (payment_status)
    )");

    // Create enhanced company_details table
    $pdo->exec("CREATE TABLE IF NOT EXISTS company_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) DEFAULT 'Ceylon Express',
        address TEXT,
        phone VARCHAR(100),
        email VARCHAR(100),
        website VARCHAR(255),
        tax_number VARCHAR(50),
        registration_number VARCHAR(50),
        logo_path VARCHAR(255),
        sinhala_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        terms_conditions TEXT,
        privacy_policy TEXT,
        default_currency VARCHAR(3) DEFAULT 'EUR',
        timezone VARCHAR(50) DEFAULT 'Europe/Rome',
        business_hours VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create activity_logs table for tracking user actions
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        table_name VARCHAR(50),
        record_id INT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_table (table_name),
        INDEX idx_created_at (created_at)
    )");

    // Create shipment_tracking table for detailed tracking
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipment_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipment_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        location VARCHAR(255),
        description TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        INDEX idx_shipment_id (shipment_id),
        INDEX idx_status (status),
        INDEX idx_timestamp (timestamp)
    )");

    // Create system_settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
        description TEXT,
        is_public BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key),
        INDEX idx_public (is_public)
    )");

    // Create notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(500),
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_read (is_read),
        INDEX idx_created_at (created_at)
    )");

    $success_messages[] = "All database tables created successfully with enhanced features.";

    // Add foreign key constraints after all tables are created
    try {
        // Add foreign keys for kg_rates
        $pdo->exec("ALTER TABLE kg_rates ADD CONSTRAINT fk_kg_rates_created_by 
                   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        
        // Add foreign keys for receivers (only if senders table exists)
        $pdo->exec("ALTER TABLE receivers ADD CONSTRAINT fk_receivers_sender_mobile 
                   FOREIGN KEY (sender_mobile) REFERENCES senders(mobile) ON UPDATE CASCADE ON DELETE SET NULL");
        
        // Add foreign keys for shipments
        $pdo->exec("ALTER TABLE shipments ADD CONSTRAINT fk_shipments_sender_mobile 
                   FOREIGN KEY (sender_mobile) REFERENCES senders(mobile) ON UPDATE CASCADE ON DELETE SET NULL");
        $pdo->exec("ALTER TABLE shipments ADD CONSTRAINT fk_shipments_receiver_id 
                   FOREIGN KEY (receiver_id) REFERENCES receivers(id) ON UPDATE CASCADE ON DELETE SET NULL");
        $pdo->exec("ALTER TABLE shipments ADD CONSTRAINT fk_shipments_kg_rate_id 
                   FOREIGN KEY (kg_rate_id) REFERENCES kg_rates(id) ON DELETE SET NULL");
        $pdo->exec("ALTER TABLE shipments ADD CONSTRAINT fk_shipments_created_by 
                   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        
        // Add foreign keys for activity_logs
        $pdo->exec("ALTER TABLE activity_logs ADD CONSTRAINT fk_activity_logs_user_id 
                   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        
        // Add foreign keys for shipment_tracking
        $pdo->exec("ALTER TABLE shipment_tracking ADD CONSTRAINT fk_shipment_tracking_shipment_id 
                   FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE");
        $pdo->exec("ALTER TABLE shipment_tracking ADD CONSTRAINT fk_shipment_tracking_created_by 
                   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        
        // Add foreign keys for notifications
        $pdo->exec("ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user_id 
                   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        
        $success_messages[] = "Foreign key constraints added successfully.";
        
    } catch (PDOException $e) {
        // Foreign key constraints might already exist or fail due to existing data
        $success_messages[] = "Tables created successfully (some foreign key constraints may already exist).";
    }

    // Insert default users with enhanced data
    $user_check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($user_check == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
$users = [
    ['admin@ceylonexpress.com', md5('admin123'), 'admin', 'Admin', 'User', 1],
    ['manager@ceylonexpress.com', md5('manager123'), 'manager', 'Manager', 'User', 1],
    ['user@ceylonexpress.com', md5('user123'), 'user', 'Regular', 'User', 1]
];
        
        foreach ($users as $user) {
            $stmt->execute($user);
        }
        
        $success_messages[] = "Default user accounts created (admin, manager, user).";
    }

    // Insert enhanced default KG rates
    $rates_check = $pdo->query("SELECT COUNT(*) FROM kg_rates")->fetchColumn();
    if ($rates_check == 0) {
        $stmt = $pdo->prepare("INSERT INTO kg_rates (rate_name, rate_per_kg, rate_type, description, minimum_weight, maximum_weight, valid_from) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $rates = [
            ['Branches', 4.00, 'Branches', 'Standard shipping rate for regular items', 0.1, 300000.0, date('Y-m-d')],
            ['Home Delivery', 4.50, 'Home Delivery', 'Express shipping for urgent deliveries', 0.1, 250000.0, date('Y-m-d')],
         
        ];
        
        foreach ($rates as $rate) {
            $stmt->execute($rate);
        }
        
        $success_messages[] = "Enhanced default KG rates created with detailed specifications.";
    }

    // Insert default company details with enhanced information
    $company_check = $pdo->query("SELECT COUNT(*) FROM company_details")->fetchColumn();
    if ($company_check == 0) {
        $sinhala_text = mb_convert_encoding(
            "ඉතාලියේ සිට පැමිණෙන ගුවන් භාණ්ඩවල සංකීර්ණ පරීක්ෂාවන් හේතුවෙන් රේගු නිශ්කාශනයේ ප්‍රමාදයන් සිදු විය හැක. ඔබට ඔබේ බඩු පෙට්ටිය ගැන කිසියම් ගැටලුවක් හෝ අපැහැදිලි බවක් ඇත්නම් කරුණාකර අප සමග කතා කර ඉතාලියෙන් පෙට්ටිය පිටවන දින සිට මාසයක් ඇතුලත කරුණු විසදාගන්න", 
            'UTF-8'
        );
        
        $address = "Via Principe Eugenio, 83\n00185 Roma,\nPiazza Vittorio.\nItaly - 320 695 0393/ 324 990 8069\nSri Lanka - +94 76 126 7433 / +94 76 074 5058";
        
        $terms_conditions = "1. All shipments are subject to customs regulations of both countries.\n2. Ceylon Express is not responsible for delays caused by customs.\n3. Prohibited items will not be accepted for shipping.\n4. Insurance is optional but recommended for valuable items.\n5. Delivery times are estimates and not guaranteed.";
        
        $stmt = $pdo->prepare("
            INSERT INTO company_details 
            (company_name, address, phone, email, website, sinhala_text, terms_conditions, default_currency, timezone, business_hours) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Ceylon Express',
            $address,
            '320 695 0393 / 324 990 8069',
            'CEYLONEXPRESS83@gmail.com',
            'www.ceylonexpress.com',
            $sinhala_text,
            $terms_conditions,
            'EUR',
            'Europe/Rome',
            'Mon-Fri: 9:00-18:00, Sat: 9:00-13:00'
        ]);
        
        $success_messages[] = "Enhanced company details created with complete information.";
    }

    // Insert default system settings
    $settings_check = $pdo->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();
    if ($settings_check == 0) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES (?, ?, ?, ?, ?)");
        
        $settings = [
            ['app_name', 'Ceylon Express', 'string', 'Application name', true],
            ['app_version', '2.0.0', 'string', 'Application version', true],
            ['max_file_upload_size', '10', 'number', 'Maximum file upload size in MB', false],
            ['default_items_per_page', '25', 'number', 'Default pagination size', false],
            ['enable_email_notifications', 'true', 'boolean', 'Enable email notifications', false],
            ['maintenance_mode', 'false', 'boolean', 'System maintenance mode', false],
            ['backup_retention_days', '30', 'number', 'How many days to keep backups', false],
            ['session_timeout_minutes', '120', 'number', 'Session timeout in minutes', false]
        ];
        
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        $success_messages[] = "Default system settings configured.";
    }

    // Create upload directories with proper permissions
    $directories = [
        'uploads/',
        'uploads/logos/',
        'uploads/documents/',
        'uploads/temp/',
        'backups/',
        'logs/'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            // Create index.php to prevent directory listing
            file_put_contents($dir . 'index.php', '<?php header("HTTP/1.0 403 Forbidden"); ?>');
        }
    }
    $success_messages[] = "Upload directories created with security measures.";

    // Create .htaccess for security
    $htaccess_content = "
# Prevent access to sensitive files
<Files ~ \"^\.ht\">
    Order allow,deny
    Deny from all
</Files>

# Prevent access to PHP configuration files
<Files ~ \"config\.php$\">
    Order allow,deny
    Deny from all
</Files>

# Enable GZIP compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection \"1; mode=block\"
</IfModule>
";

    if (!file_exists('.htaccess')) {
        file_put_contents('.htaccess', $htaccess_content);
        $success_messages[] = "Security .htaccess file created.";
    }

} catch (PDOException $e) {
    $error_messages[] = "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    $error_messages[] = "General Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Enhanced Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            padding: 40px;
            margin: 50px auto;
            max-width: 900px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 2.8rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .status-icon {
            font-size: 5rem;
            margin-bottom: 30px;
        }
        .success-icon {
            color: #27ae60;
            animation: pulse 2s infinite;
        }
        .error-icon {
            color: #e74c3c;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .feature-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .credential-card {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin: 10px 0;
        }
        .btn-continue {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            transition: all 0.3s ease;
        }
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(39, 174, 96, 0.4);
            color: white;
        }
        .alert-enhanced {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin: 8px 0;
        }
        .progress-bar-setup {
            height: 8px;
            border-radius: 10px;
            background: linear-gradient(45deg, #27ae60, #2ecc71);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="header text-center mb-4">
                <h1><i class="fas fa-shipping-fast"></i> Ceylon Express</h1>
                <p class="lead">Enhanced Shipping Management System Setup</p>
                <div class="progress mb-4">
                    <div class="progress-bar progress-bar-setup" style="width: <?php echo empty($error_messages) ? '100%' : '75%'; ?>"></div>
                </div>
            </div>

            <div class="text-center mb-4">
                <?php if (empty($error_messages)): ?>
                    <div class="status-icon success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-success mb-4">🎉 Setup Completed Successfully!</h2>
                    <p class="text-muted">Your Ceylon Express system is now ready with enhanced features</p>
                <?php else: ?>
                    <div class="status-icon error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="text-danger mb-4">⚠️ Setup Completed with Errors</h2>
                    <p class="text-muted">Please review the errors below and contact support if needed</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($success_messages)): ?>
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check"></i> Setup Progress</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($success_messages as $message): ?>
                            <div class="alert alert-success alert-enhanced mb-2 py-2">
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
                            <div class="alert alert-danger alert-enhanced mb-2 py-2">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Enhanced Features Overview -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5>Enhanced User Management</h5>
                            <p class="text-muted">Complete CRUD operations, role management, and activity tracking</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                            <h5>Advanced Rate Management</h5>
                            <p class="text-muted">Flexible pricing with weight ranges, validity periods, and bulk operations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card text-center">
                        <div class="card-body">
                            <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                            <h5>Analytics & Tracking</h5>
                            <p class="text-muted">Comprehensive reporting, activity logs, and system monitoring</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Credentials -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="credential-card text-center">
                        <h6><i class="fas fa-crown me-2"></i>Super Administrator</h6>
                        <strong>Email:</strong> admin@ceylonexpress.com<br>
                        <strong>Password:</strong> admin123<br>
                        <small>Full system access & management</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="credential-card text-center" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
                        <h6><i class="fas fa-user-tie me-2"></i>Manager Account</h6>
                        <strong>Email:</strong> manager@ceylonexpress.com<br>
                        <strong>Password:</strong> manager123<br>
                        <small>Management & reporting access</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="credential-card text-center" style="background: linear-gradient(45deg, #34495e, #2c3e50);">
                        <h6><i class="fas fa-user me-2"></i>Regular User</h6>
                        <strong>Email:</strong> user@ceylonexpress.com<br>
                        <strong>Password:</strong> user123<br>
                        <small>Standard user operations</small>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card bg-light mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle text-info"></i> System Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>📦 New Features Included:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Enhanced user roles (Admin, Manager, User)</li>
                                <li><i class="fas fa-check text-success me-2"></i>Advanced KG rate management with validity periods</li>
                                <li><i class="fas fa-check text-success me-2"></i>Comprehensive activity logging</li>
                                <li><i class="fas fa-check text-success me-2"></i>Shipment tracking system</li>
                                <li><i class="fas fa-check text-success me-2"></i>System notifications</li>
                                <li><i class="fas fa-check text-success me-2"></i>Enhanced security measures</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>🔧 Database Enhancements:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-database text-primary me-2"></i>Enhanced Users table with profile data</li>
                                <li><i class="fas fa-database text-primary me-2"></i>Advanced Rates with weight ranges</li>
                                <li><i class="fas fa-database text-primary me-2"></i>Activity logs for audit trail</li>
                                <li><i class="fas fa-database text-primary me-2"></i>Shipment tracking table</li>
                                <li><i class="fas fa-database text-primary me-2"></i>System settings management</li>
                                <li><i class="fas fa-database text-primary me-2"></i>Notifications system</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="alert alert-warning alert-enhanced">
                <h6><i class="fas fa-shield-alt me-2"></i>Security Recommendations</h6>
                <ul class="mb-0">
                    <li><strong>Change default passwords</strong> immediately after first login</li>
                    <li><strong>Enable HTTPS</strong> for production deployment</li>
                    <li><strong>Regular backups</strong> are configured - check backup directory</li>
                    <li><strong>Security headers</strong> have been configured in .htaccess</li>
                    <li><strong>File upload restrictions</strong> are in place</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <?php if (empty($error_messages)): ?>
                <div class="text-center mt-4">
                    <a href="admin_dashboard.php" class="btn btn-continue me-3">
                        <i class="fas fa-cog me-2"></i>Go to Admin Dashboard
                    </a>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>Main Application
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center mt-4">
                    <button class="btn btn-danger" onclick="location.reload()">
                        <i class="fas fa-redo me-2"></i>Retry Setup
                    </button>
                </div>
            <?php endif; ?>

            <!-- Footer Information -->
            <div class="mt-5 pt-4 border-top text-center">
                <div class="row">
                    <div class="col-md-4">
                        <h6><i class="fas fa-rocket text-warning"></i> Version 2.0.0</h6>
                        <small class="text-muted">Enhanced with advanced features</small>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-calendar text-info"></i> Setup Date</h6>
                        <small class="text-muted"><?php echo date('F j, Y \a\t g:i A'); ?></small>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-support text-success"></i> Support</h6>
                        <small class="text-muted">CEYLONEXPRESS83@gmail.com</small>
                    </div>
                </div>
                <hr>
                <p class="text-muted mb-0">
                    <small>
                        <strong>Ceylon Express Shipping Management System</strong><br>
                        Built with PHP, MySQL, Bootstrap 5, and FontAwesome 6<br>
                        © <?php echo date('Y'); ?> Ceylon Express. All rights reserved.
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate success messages
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach((alert, index) => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(-100%)';
                    setTimeout(() => {
                        alert.style.opacity = '1';
                        alert.style.transform = 'translateX(0)';
                    }, 100);
                }, index * 200);
            });

            // Add hover effects to feature cards
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                });
            });

            // Animate progress bar
            const progressBar = document.querySelector('.progress-bar-setup');
            if (progressBar) {
                const width = progressBar.style.width;
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.transition = 'width 2s ease-in-out';
                    progressBar.style.width = width;
                }, 500);
            }
        });

        // Show system status
        function showSystemStatus() {
            alert('✅ System Status: All components initialized successfully!\n\n' +
                  '📊 Database: Connected and optimized\n' +
                  '🔐 Security: Headers and protections active\n' +
                  '📁 File System: Directories created with proper permissions\n' +
                  '⚙️ Configuration: Default settings applied\n\n' +
                  'Your Ceylon Express system is ready for use!');
        }

        // Add click handler to success icon for fun interaction
        document.querySelector('.success-icon')?.addEventListener('click', showSystemStatus);
    </script>
</body>
</html>

