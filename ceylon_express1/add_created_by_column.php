<?php
// add_created_by_column.php - Add missing columns to existing database
require_once 'config.php';

$success_messages = [];
$error_messages = [];

try {
    // Check if created_by column exists in shipments table
    $check_column = $pdo->prepare("SHOW COLUMNS FROM shipments LIKE 'created_by'");
    $check_column->execute();
    
    if ($check_column->rowCount() == 0) {
        // Add created_by column to shipments table
        $pdo->exec("ALTER TABLE shipments ADD COLUMN created_by INT NULL AFTER remarks");
        $success_messages[] = "Added 'created_by' column to shipments table.";
        
        // Set current user as creator for all existing shipments
        if (isset($_SESSION['user_id'])) {
            $update_stmt = $pdo->prepare("UPDATE shipments SET created_by = ? WHERE created_by IS NULL");
            $update_stmt->execute([$_SESSION['user_id']]);
            $success_messages[] = "Updated existing shipments with current user as creator.";
        }
    } else {
        $success_messages[] = "'created_by' column already exists in shipments table.";
    }
    
    // Check if activity_logs table exists
    $check_table = $pdo->prepare("SHOW TABLES LIKE 'activity_logs'");
    $check_table->execute();
    
    if ($check_table->rowCount() == 0) {
        // Create activity_logs table
        $pdo->exec("CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(50),
            record_id INT,
            old_values TEXT,
            new_values TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )");
        $success_messages[] = "Created 'activity_logs' table.";
    } else {
        $success_messages[] = "'activity_logs' table already exists.";
    }
    
    // Check if shipment_tracking table exists
    $check_tracking = $pdo->prepare("SHOW TABLES LIKE 'shipment_tracking'");
    $check_tracking->execute();
    
    if ($check_tracking->rowCount() == 0) {
        // Create shipment_tracking table
        $pdo->exec("CREATE TABLE shipment_tracking (
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
        $success_messages[] = "Created 'shipment_tracking' table.";
    } else {
        $success_messages[] = "'shipment_tracking' table already exists.";
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
    <title>Ceylon Express - Database Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .update-container {
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
        <div class="update-container">
            <div class="header text-center mb-4">
                <h1><i class="fas fa-database"></i> Ceylon Express</h1>
                <p class="lead">Database Structure Update</p>
            </div>

            <div class="text-center mb-4">
                <?php if (empty($error_messages)): ?>
                    <div class="status-icon success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-success mb-4">Database Updated Successfully!</h2>
                <?php else: ?>
                    <div class="status-icon">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                    </div>
                    <h2 class="text-danger mb-4">Update Completed with Errors</h2>
                <?php endif; ?>
            </div>

            <?php if (!empty($success_messages)): ?>
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check"></i> Update Progress</h5>
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
                    <h5><i class="fas fa-info-circle text-info"></i> What was updated:</h5>
                    <ul>
                        <li><strong>shipments table:</strong> Added 'created_by' column to track who created each shipment</li>
                        <li><strong>activity_logs table:</strong> Created for audit trail and user activity tracking</li>
                        <li><strong>shipment_tracking table:</strong> Created for detailed shipment status tracking</li>
                    </ul>
                    <p class="mt-3 mb-0">
                        <strong>Note:</strong> Your reports page now has full delete functionality with proper permissions and audit logging.
                    </p>
                </div>
            </div>

            <?php if (empty($error_messages)): ?>
                <div class="text-center mt-4">
                    <a href="reports.php" class="btn btn-success btn-lg">
                        <i class="fas fa-chart-line me-2"></i>Go to Reports
                    </a>
                    <a href="dashboard.php" class="btn btn-primary btn-lg ms-2">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <small class="text-muted">
                    <strong>Ceylon Express Database Update</strong> - Version 2.0
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>