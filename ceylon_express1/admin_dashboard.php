<?php
// admin_dashboard.php - Enhanced with User CRUD and KG Rate Management
require_once 'config.php';
requireAdmin();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_company'])) {
        // Update company details
        $company_name = sanitize($_POST['company_name']);
        $address = sanitize($_POST['address']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $sinhala_text = sanitize($_POST['sinhala_text']);
        
        $stmt = $pdo->prepare("UPDATE company_details SET company_name = ?, address = ?, phone = ?, email = ?, sinhala_text = ? WHERE id = 1");
        if ($stmt->execute([$company_name, $address, $phone, $email, $sinhala_text])) {
            $success_message = "Company details updated successfully!";
        } else {
            $error_message = "Error updating company details!";
        }
        
    } elseif (isset($_POST['add_kg_rate'])) {
        // Add new KG rate
        $rate_name = sanitize($_POST['rate_name']);
        $rate_per_kg = (float)$_POST['rate_per_kg'];
        
        // Check if rate name already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM kg_rates WHERE rate_name = ?");
        $check_stmt->execute([$rate_name]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $error_message = "Rate name already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO kg_rates (rate_name, rate_per_kg) VALUES (?, ?)");
            if ($stmt->execute([$rate_name, $rate_per_kg])) {
                $success_message = "KG rate added successfully!";
            } else {
                $error_message = "Error adding KG rate!";
            }
        }
        
    } elseif (isset($_POST['update_kg_rate'])) {
        // Update KG rate
        $rate_id = (int)$_POST['rate_id'];
        $rate_name = sanitize($_POST['rate_name']);
        $rate_per_kg = (float)$_POST['rate_per_kg'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE kg_rates SET rate_name = ?, rate_per_kg = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$rate_name, $rate_per_kg, $is_active, $rate_id])) {
            $success_message = "KG rate updated successfully!";
        } else {
            $error_message = "Error updating KG rate!";
        }
        
    } elseif (isset($_POST['delete_kg_rate'])) {
        // Delete KG rate
        $rate_id = (int)$_POST['rate_id'];
        
        // Check if rate is being used in any shipments
        $usage_check = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE kg_rate_id = ?");
        $usage_check->execute([$rate_id]);
        
        if ($usage_check->fetchColumn() > 0) {
            $error_message = "Cannot delete rate - it's being used in existing shipments!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM kg_rates WHERE id = ?");
            if ($stmt->execute([$rate_id])) {
                $success_message = "KG rate deleted successfully!";
            } else {
                $error_message = "Error deleting KG rate!";
            }
        }
        
    } elseif (isset($_POST['add_user'])) {
        // Add new user
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $role = sanitize($_POST['role']);
        
        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $error_message = "Email already exists!";
        } else {
            $hashed_password = md5($password); // In production, use password_hash()
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
            if ($stmt->execute([$email, $hashed_password, $role])) {
                $success_message = "User added successfully!";
            } else {
                $error_message = "Error adding user!";
            }
        }
        
    } elseif (isset($_POST['update_user'])) {
        // Update user
        $user_id = (int)$_POST['user_id'];
        $email = sanitize($_POST['email']);
        $role = sanitize($_POST['role']);
        
        // Check if email is already used by another user
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $error_message = "Email already exists for another user!";
        } else {
            if (!empty($_POST['password'])) {
                // Update with new password
                $password = md5($_POST['password']);
                $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, role = ? WHERE id = ?");
                $result = $stmt->execute([$email, $password, $role, $user_id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
                $result = $stmt->execute([$email, $role, $user_id]);
            }
            
            if ($result) {
                $success_message = "User updated successfully!";
            } else {
                $error_message = "Error updating user!";
            }
        }
        
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting current user
        if ($user_id == $_SESSION['user_id']) {
            $error_message = "Cannot delete your own account!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success_message = "User deleted successfully!";
            } else {
                $error_message = "Error deleting user!";
            }
        }
        
    } elseif (isset($_POST['update_sender'])) {
        // Update sender details
        $sender_id = (int)$_POST['sender_id'];
        $name = sanitize($_POST['name']);
        $surname = sanitize($_POST['surname']);
        $address = sanitize($_POST['address']);
        $mobile = sanitize($_POST['mobile']);
        $tax_code = sanitize($_POST['tax_code']);
        $email = sanitize($_POST['email']);
        $sex = sanitize($_POST['sex']);
        $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $birth_country = sanitize($_POST['birth_country']);
        
        // Check if mobile number is already used by another sender
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM senders WHERE mobile = ? AND id != ?");
        $check_stmt->execute([$mobile, $sender_id]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $error_message = "Mobile number already exists for another sender!";
        } else {
            $stmt = $pdo->prepare("UPDATE senders SET name = ?, surname = ?, address = ?, mobile = ?, tax_code = ?, email = ?, sex = ?, dob = ?, birth_country = ? WHERE id = ?");
            if ($stmt->execute([$name, $surname, $address, $mobile, $tax_code, $email, $sex, $dob, $birth_country, $sender_id])) {
                $success_message = "Sender updated successfully!";
            } else {
                $error_message = "Error updating sender!";
            }
        }
        
    } elseif (isset($_POST['delete_sender'])) {
        // Delete sender
        $sender_id = (int)$_POST['sender_id'];
        
        // Check if sender has any shipments or receivers
        $shipments_check = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE sender_mobile = (SELECT mobile FROM senders WHERE id = ?)");
        $shipments_check->execute([$sender_id]);
        
        $receivers_check = $pdo->prepare("SELECT COUNT(*) FROM receivers WHERE sender_mobile = (SELECT mobile FROM senders WHERE id = ?)");
        $receivers_check->execute([$sender_id]);
        
        if ($shipments_check->fetchColumn() > 0 || $receivers_check->fetchColumn() > 0) {
            $error_message = "Cannot delete sender - they have existing shipments or receivers!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM senders WHERE id = ?");
            if ($stmt->execute([$sender_id])) {
                $success_message = "Sender deleted successfully!";
            } else {
                $error_message = "Error deleting sender!";
            }
        }
        
    } elseif (isset($_POST['update_receiver'])) {
        // Update receiver details
        $receiver_id = (int)$_POST['receiver_id'];
        $name = sanitize($_POST['name']);
        $address = sanitize($_POST['address']);
        $phone1 = sanitize($_POST['phone1']);
        $phone2 = sanitize($_POST['phone2']);
       
        
        
        $stmt = $pdo->prepare("UPDATE receivers SET name = ?, address = ?, phone1 = ?, phone2 = ?,  WHERE id = ?");
        if ($stmt->execute([$name, $address, $phone1, $phone2,  $receiver_id])) {
            $success_message = "Receiver updated successfully!";
        } else {
            $error_message = "Error updating receiver!";
        }
        
    } elseif (isset($_POST['delete_receiver'])) {
        // Delete receiver
        $receiver_id = (int)$_POST['receiver_id'];
        
        // Check if receiver has any shipments
        $shipments_check = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE receiver_id = ?");
        $shipments_check->execute([$receiver_id]);
        
        if ($shipments_check->fetchColumn() > 0) {
            $error_message = "Cannot delete receiver - they have existing shipments!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM receivers WHERE id = ?");
            if ($stmt->execute([$receiver_id])) {
                $success_message = "Receiver deleted successfully!";
            } else {
                $error_message = "Error deleting receiver!";
            }
        }
    }
}

// Get data for display
$company_stmt = $pdo->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
$company = $company_stmt->fetch();

$kg_rates_stmt = $pdo->query("SELECT * FROM kg_rates ORDER BY id DESC");
$kg_rates = $kg_rates_stmt->fetchAll();

$users_stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $users_stmt->fetchAll();

$senders_stmt = $pdo->query("SELECT * FROM senders ORDER BY created_at DESC LIMIT 20");
$senders = $senders_stmt->fetchAll();

$receivers_stmt = $pdo->query("SELECT r.*, s.name as sender_name FROM receivers r JOIN senders s ON r.sender_mobile = s.mobile ORDER BY r.created_at DESC LIMIT 20");
$receivers = $receivers_stmt->fetchAll();

// Get statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_senders' => $pdo->query("SELECT COUNT(*) FROM senders")->fetchColumn(),
    'total_receivers' => $pdo->query("SELECT COUNT(*) FROM receivers")->fetchColumn(),
    'total_shipments' => $pdo->query("SELECT COUNT(*) FROM shipments")->fetchColumn(),
    'active_rates' => $pdo->query("SELECT COUNT(*) FROM kg_rates WHERE is_active = 1")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- ADD SINHALA FONT SUPPORT -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .btn-admin {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            color: white;
        }
        .btn-admin:hover {
            background: linear-gradient(45deg, #c82333, #e55d00);
            color: white;
        }
        .admin-header {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
        }
        .sinhala-text, textarea[name="sinhala_text"] {
            font-family: 'Noto Sans Sinhala', 'Iskoola Pota', 'FM Malithi', serif !important;
            font-size: 16px;
            line-height: 1.6;
            direction: ltr;
            text-align: left;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table-actions {
            white-space: nowrap;
        }
        .modal-header {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            color: white;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            color: white;
            border-color: transparent;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .btn-sm {
            margin: 1px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cog"></i> Ceylon Express - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">User Dashboard</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h4><?php echo $stats['total_users']; ?></h4>
                        <small>Total Users</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-2x mb-2"></i>
                        <h4><?php echo $stats['total_senders']; ?></h4>
                        <small>Senders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-user-friends fa-2x mb-2"></i>
                        <h4><?php echo $stats['total_receivers']; ?></h4>
                        <small>Receivers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-shipping-fast fa-2x mb-2"></i>
                        <h4><?php echo $stats['total_shipments']; ?></h4>
                        <small>Total Shipments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h4><?php echo $stats['active_rates']; ?></h4>
                        <small>Active Rates</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Tabs -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">
                    <i class="fas fa-building"></i> Company Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                    <i class="fas fa-users"></i> Manage Users
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rates-tab" data-bs-toggle="tab" data-bs-target="#rates" type="button" role="tab">
                    <i class="fas fa-dollar-sign"></i> KG Rates
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="senders-tab" data-bs-toggle="tab" data-bs-target="#senders" type="button" role="tab">
                    <i class="fas fa-user-plus"></i> Manage Senders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="receivers-tab" data-bs-toggle="tab" data-bs-target="#receivers" type="button" role="tab">
                    <i class="fas fa-user-friends"></i> Manage Receivers
                </button>
            </li>
        </ul>

        <div class="tab-content mt-4" id="adminTabsContent">
            <!-- Company Details Tab -->
            <div class="tab-pane fade show active" id="company" role="tabpanel">
                <div class="card">
                    <div class="card-header admin-header text-white">
                        <h5><i class="fas fa-building"></i> Company Details Management</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($company['email']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($company['address']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Sinhala Text (Center of Document)</label>
                                <textarea class="form-control sinhala-text" name="sinhala_text" rows="4" 
                                      style="font-family: 'Noto Sans Sinhala', serif; font-size: 16px;"><?php 
                                echo htmlspecialchars($company['sinhala_text'], ENT_QUOTES, 'UTF-8'); 
                            ?></textarea>
                                <div class="form-text">This text appears in the center of shipping documents</div>
                            </div>
                            
                            <button type="submit" name="update_company" class="btn btn-admin">
                                <i class="fas fa-save"></i> Update Company Details
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Users Management Tab -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header admin-header text-white">
                                <h6><i class="fas fa-user-plus"></i> Add New User</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" name="role" required>
                                            <option value="">Select Role</option>
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="add_user" class="btn btn-admin btn-sm">
                                        <i class="fas fa-plus"></i> Add User
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header admin-header text-white">
                                <h6><i class="fas fa-users"></i> All Users</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                    <td class="table-actions">
                                                        <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KG Rates Tab -->
            <div class="tab-pane fade" id="rates" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header admin-header text-white">
                                <h6><i class="fas fa-plus"></i> Add New KG Rate</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Rate Name</label>
                                        <input type="text" class="form-control" name="rate_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Rate per KG (€.)</label>
                                        <input type="number" class="form-control" name="rate_per_kg" step="0.01" required>
                                    </div>
                                    <button type="submit" name="add_kg_rate" class="btn btn-admin btn-sm">
                                        <i class="fas fa-plus"></i> Add Rate
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header admin-header text-white">
                                <h6><i class="fas fa-list"></i> Existing KG Rates</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Rate Name</th>
                                                <th>Rate (€.)</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($kg_rates as $rate): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($rate['rate_name']); ?></td>
                                                    <td>€. <?php echo number_format($rate['rate_per_kg'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $rate['is_active'] ? 'success' : 'danger'; ?>">
                                                            <?php echo $rate['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($rate['created_at'])); ?></td>
                                                    <td class="table-actions">
                                                        <button class="btn btn-sm btn-warning" onclick="editKgRate(<?php echo $rate['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteKgRate(<?php echo $rate['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Senders Management Tab -->
            <div class="tab-pane fade" id="senders" role="tabpanel">
                <div class="card">
                    <div class="card-header admin-header text-white">
                        <h5><i class="fas fa-user-plus"></i> Manage Senders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mobile</th>
                                        <th>Name</th>
                                        <th>Tax Code</th>
                                        <!-- <th>Email</th> -->
                                        <th>Address</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($senders as $sender): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sender['mobile']); ?></td>
                                            <td><?php echo htmlspecialchars($sender['name'] . ' ' . $sender['surname']); ?></td>
                                            <td><?php echo htmlspecialchars($sender['tax_code'] ?: 'N/A'); ?></td>
                                            
                                            <td><?php echo htmlspecialchars(substr($sender['address'], 0, 30) . '...'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($sender['created_at'])); ?></td>
                                            <td class="table-actions">
                                                <button class="btn btn-sm btn-warning" onclick="editSender(<?php echo $sender['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteSender(<?php echo $sender['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receivers Management Tab -->
            <div class="tab-pane fade" id="receivers" role="tabpanel">
                <div class="card">
                    <div class="card-header admin-header text-white">
                        <h5><i class="fas fa-user-friends"></i> Manage Receivers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Receiver ID</th>
                                        <th>Name</th>
                                        <th>Sender</th>
                                        <!-- <th>Email</th> -->
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($receivers as $receiver): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($receiver['receiver_id']); ?></td>
                                            <td><?php echo htmlspecialchars($receiver['name']); ?></td>
                                            <td><?php echo htmlspecialchars($receiver['sender_name']); ?></td>
                                            
                                            <td><?php echo htmlspecialchars($receiver['phone1']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($receiver['address'], 0, 25) . '...'); ?></td>
                                            <td class="table-actions">
                                                <button class="btn btn-sm btn-warning" onclick="editReceiver(<?php echo $receiver['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteReceiver(<?php echo $receiver['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Modals -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_user_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password" id="edit_user_password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_user_role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_user" class="btn btn-admin">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> Deleting a user will permanently remove their account and access to the system.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KG Rate Management Modals -->
    <div class="modal fade" id="editKgRateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit KG Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editKgRateForm">
                    <div class="modal-body">
                        <input type="hidden" name="rate_id" id="edit_rate_id">
                        <div class="mb-3">
                            <label class="form-label">Rate Name</label>
                            <input type="text" class="form-control" name="rate_name" id="edit_rate_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rate per KG (€.)</label>
                            <input type="number" class="form-control" name="rate_per_kg" id="edit_rate_per_kg" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_rate_active">
                                <label class="form-check-label" for="edit_rate_active">
                                    Active Rate
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_kg_rate" class="btn btn-admin">
                            <i class="fas fa-save"></i> Update Rate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete KG Rate Confirmation Modal -->
    <div class="modal fade" id="deleteKgRateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteKgRateForm">
                    <div class="modal-body">
                        <input type="hidden" name="rate_id" id="delete_rate_id">
                        <p>Are you sure you want to delete this KG rate? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> You cannot delete rates that are currently being used in shipments.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_kg_rate" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Rate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sender Edit Modal -->
    <div class="modal fade" id="editSenderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit Sender Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editSenderForm">
                    <div class="modal-body">
                        <input type="hidden" name="sender_id" id="edit_sender_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile *</label>
                                <input type="text" class="form-control" name="mobile" id="edit_sender_mobile" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tax Code</label>
                                <input type="text" class="form-control" name="tax_code" id="edit_sender_tax_code">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_sender_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="surname" id="edit_sender_surname" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_sender_email">
                            </div> -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="sex" id="edit_sender_sex">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" id="edit_sender_dob">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Birth Country</label>
                                <input type="text" class="form-control" name="birth_country" id="edit_sender_birth_country">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea class="form-control" name="address" id="edit_sender_address" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_sender" class="btn btn-admin">
                            <i class="fas fa-save"></i> Update Sender
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Sender Confirmation Modal -->
    <div class="modal fade" id="deleteSenderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Sender</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteSenderForm">
                    <div class="modal-body">
                        <input type="hidden" name="sender_id" id="delete_sender_id">
                        <p>Are you sure you want to delete this sender? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> You cannot delete senders that have existing shipments or receivers.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_sender" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Sender
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Receiver Edit Modal -->
    <div class="modal fade" id="editReceiverModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit Receiver Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editReceiverForm">
                    <div class="modal-body">
                        <input type="hidden" name="receiver_id" id="edit_receiver_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_receiver_name" required>
                            </div>
                            <!-- <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_receiver_email">
                            </div> -->
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone 1</label>
                                <input type="text" class="form-control" name="phone1" id="edit_receiver_phone1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone 2</label>
                                <input type="text" class="form-control" name="phone2" id="edit_receiver_phone2">
                            </div>
                        </div>
                        
                        <!-- <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control" name="id_number" id="edit_receiver_id_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Relationship to Sender</label>
                                <input type="text" class="form-control" name="relationship" id="edit_receiver_relationship" placeholder="e.g., Family, Friend, Business">
                            </div>
                        </div> -->
                        
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea class="form-control" name="address" id="edit_receiver_address" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_receiver" class="btn btn-admin">
                            <i class="fas fa-save"></i> Update Receiver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Receiver Confirmation Modal -->
    <div class="modal fade" id="deleteReceiverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete Receiver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteReceiverForm">
                    <div class="modal-body">
                        <input type="hidden" name="receiver_id" id="delete_receiver_id">
                        <p>Are you sure you want to delete this receiver? This action cannot be undone.</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> You cannot delete receivers that have existing shipments.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_receiver" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Receiver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User Management Functions
        function editUser(userId) {
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_user&id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_user_id').value = data.user.id;
                    // document.getElementById('edit_user_email').value = data.user.email;
                    document.getElementById('edit_user_role').value = data.user.role;
                    
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                } else {
                    alert('Error loading user data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading user data');
            });
        }

        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        // KG Rate Management Functions
        function editKgRate(rateId) {
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_kg_rate&id=${rateId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_rate_id').value = data.rate.id;
                    document.getElementById('edit_rate_name').value = data.rate.rate_name;
                    document.getElementById('edit_rate_per_kg').value = data.rate.rate_per_kg;
                    document.getElementById('edit_rate_active').checked = data.rate.is_active == 1;
                    
                    new bootstrap.Modal(document.getElementById('editKgRateModal')).show();
                } else {
                    alert('Error loading rate data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading rate data');
            });
        }

        function deleteKgRate(rateId) {
            document.getElementById('delete_rate_id').value = rateId;
            new bootstrap.Modal(document.getElementById('deleteKgRateModal')).show();
        }

        // Sender Management Functions
        function editSender(senderId) {
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_sender_admin&id=${senderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const sender = data.sender;
                    document.getElementById('edit_sender_id').value = sender.id;
                    document.getElementById('edit_sender_mobile').value = sender.mobile;
                    document.getElementById('edit_sender_name').value = sender.name;
                    document.getElementById('edit_sender_surname').value = sender.surname;
                    document.getElementById('edit_sender_address').value = sender.address;
                    document.getElementById('edit_sender_tax_code').value = sender.tax_code || '';
                    // document.getElementById('edit_sender_email').value = sender.email || '';
                    document.getElementById('edit_sender_sex').value = sender.sex || '';
                    document.getElementById('edit_sender_dob').value = sender.dob || '';
                    document.getElementById('edit_sender_birth_country').value = sender.birth_country || '';
                    
                    new bootstrap.Modal(document.getElementById('editSenderModal')).show();
                } else {
                    alert('Error loading sender data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading sender data');
            });
        }

        function deleteSender(senderId) {
            document.getElementById('delete_sender_id').value = senderId;
            new bootstrap.Modal(document.getElementById('deleteSenderModal')).show();
        }

        // Receiver Management Functions
        function editReceiver(receiverId) {
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_receiver_admin&id=${receiverId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const receiver = data.receiver;
                    document.getElementById('edit_receiver_id').value = receiver.id;
                    document.getElementById('edit_receiver_name').value = receiver.name;
                    document.getElementById('edit_receiver_address').value = receiver.address;
                    document.getElementById('edit_receiver_phone1').value = receiver.phone1 || '';
                    document.getElementById('edit_receiver_phone2').value = receiver.phone2 || '';
                    // document.getElementById('edit_receiver_email').value = receiver.email || '';
                    //document.getElementById('edit_receiver_id_number').value = receiver.id_number || '';
                   // document.getElementById('edit_receiver_relationship').value = receiver.relationship_to_sender || '';
                    
                    new bootstrap.Modal(document.getElementById('editReceiverModal')).show();
                } else {
                    alert('Error loading receiver data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading receiver data');
            });
        }

        function deleteReceiver(receiverId) {
            document.getElementById('delete_receiver_id').value = receiverId;
            new bootstrap.Modal(document.getElementById('deleteReceiverModal')).show();
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });

            // Clear form validation on input
            document.querySelectorAll('input, textarea, select').forEach(function(field) {
                field.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });
        });
    </script>
</body>
</html>