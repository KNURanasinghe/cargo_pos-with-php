<?php
// admin_dashboard.php
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
error_log("Updating company details: $company_name, $address, $phone, $email, $sinhala_text");        $stmt = $pdo->prepare("UPDATE company_details SET company_name = ?, address = ?, phone = ?, email = ?, sinhala_text = ? WHERE id = 1");
        if ($stmt->execute([$company_name, $address, $phone, $email, $sinhala_text])) {
            $success_message = "Company details updated successfully!";
        } else {
            $error_message = "Error updating company details!";
        }
        
    } elseif (isset($_POST['add_kg_rate'])) {
        // Add new KG rate
        $rate_name = sanitize($_POST['rate_name']);
        $rate_per_kg = (float)$_POST['rate_per_kg'];
        
        $stmt = $pdo->prepare("INSERT INTO kg_rates (rate_name, rate_per_kg) VALUES (?, ?)");
        if ($stmt->execute([$rate_name, $rate_per_kg])) {
            $success_message = "KG rate added successfully!";
        } else {
            $error_message = "Error adding KG rate!";
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
        
    } elseif (isset($_POST['update_sender'])) {
        // Update sender details
        $sender_id = (int)$_POST['sender_id'];
        $name = sanitize($_POST['name']);
        $surname = sanitize($_POST['surname']);
        $address = sanitize($_POST['address']);
        $mobile = sanitize($_POST['mobile']);
        
        $stmt = $pdo->prepare("UPDATE senders SET name = ?, surname = ?, address = ?, mobile = ? WHERE id = ?");
        if ($stmt->execute([$name, $surname, $address, $mobile, $sender_id])) {
            $success_message = "Sender updated successfully!";
        } else {
            $error_message = "Error updating sender!";
        }
        
    } elseif (isset($_POST['update_receiver'])) {
        // Update receiver details
        $receiver_id = (int)$_POST['receiver_id'];
        $name = sanitize($_POST['name']);
        $address = sanitize($_POST['address']);
        $phone1 = sanitize($_POST['phone1']);
        $phone2 = sanitize($_POST['phone2']);
        
        $stmt = $pdo->prepare("UPDATE receivers SET name = ?, address = ?, phone1 = ?, phone2 = ? WHERE id = ?");
        if ($stmt->execute([$name, $address, $phone1, $phone2, $receiver_id])) {
            $success_message = "Receiver updated successfully!";
        } else {
            $error_message = "Error updating receiver!";
        }
    }
}

// Get data for display
$company_stmt = $pdo->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
$company = $company_stmt->fetch();

$kg_rates_stmt = $pdo->query("SELECT * FROM kg_rates ORDER BY id DESC");
$kg_rates = $kg_rates_stmt->fetchAll();

$senders_stmt = $pdo->query("SELECT * FROM senders ORDER BY created_at DESC LIMIT 20");
$senders = $senders_stmt->fetchAll();

$receivers_stmt = $pdo->query("SELECT r.*, s.name as sender_name FROM receivers r JOIN senders s ON r.sender_mobile = s.mobile ORDER BY r.created_at DESC LIMIT 20");
$receivers = $receivers_stmt->fetchAll();
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
        }
        .btn-admin {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
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
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Admin Tabs -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button" role="tab">
                    <i class="fas fa-building"></i> Company Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rates-tab" data-bs-toggle="tab" data-bs-target="#rates" type="button" role="tab">
                    <i class="fas fa-dollar-sign"></i> KG Rates
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="senders-tab" data-bs-toggle="tab" data-bs-target="#senders" type="button" role="tab">
                    <i class="fas fa-users"></i> Manage Senders
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

            <!-- KG Rates Tab -->
            <div class="tab-pane fade" id="rates" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header admin-header text-white">
                                <h6>Add New KG Rate</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Rate Name</label>
                                        <input type="text" class="form-control" name="rate_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Rate per KG (Rs.)</label>
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
                                <h6>Existing KG Rates</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Rate Name</th>
                                                <th>Rate (Rs.)</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($kg_rates as $rate): ?>
                                                <tr id="rate-row-<?php echo $rate['id']; ?>">
                                                    <td>
                                                        <span class="rate-display"><?php echo $rate['rate_name']; ?></span>
                                                        <input type="text" class="form-control rate-edit d-none" value="<?php echo htmlspecialchars($rate['rate_name']); ?>">
                                                    </td>
                                                    <td>
                                                        <span class="rate-display"><?php echo number_format($rate['rate_per_kg'], 2); ?></span>
                                                        <input type="number" class="form-control rate-edit d-none" step="0.01" value="<?php echo $rate['rate_per_kg']; ?>">
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $rate['is_active'] ? 'success' : 'danger'; ?>">
                                                            <?php echo $rate['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning edit-rate" data-id="<?php echo $rate['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-success save-rate d-none" data-id="<?php echo $rate['id']; ?>">
                                                            <i class="fas fa-check"></i>
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
                        <h5>Manage Senders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mobile</th>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($senders as $sender): ?>
                                        <tr>
                                            <td><?php echo $sender['mobile']; ?></td>
                                            <td><?php echo $sender['name'] . ' ' . $sender['surname']; ?></td>
                                            <td><?php echo substr($sender['address'], 0, 50) . '...'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editSender(<?php echo $sender['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
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
                        <h5>Manage Receivers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Receiver ID</th>
                                        <th>Name</th>
                                        <th>Sender</th>
                                        <th>Address</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($receivers as $receiver): ?>
                                        <tr>
                                            <td><?php echo $receiver['receiver_id']; ?></td>
                                            <td><?php echo $receiver['name']; ?></td>
                                            <td><?php echo $receiver['sender_name']; ?></td>
                                            <td><?php echo substr($receiver['address'], 0, 30) . '...'; ?></td>
                                            <td><?php echo $receiver['phone1']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editReceiver(<?php echo $receiver['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
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

    <!-- Modals for editing -->
    <div class="modal fade" id="editSenderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Sender</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editSenderForm">
                    <div class="modal-body">
                        <input type="hidden" name="sender_id" id="edit_sender_id">
                        <div class="mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" name="mobile" id="edit_sender_mobile">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="edit_sender_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Surname</label>
                                <input type="text" class="form-control" name="surname" id="edit_sender_surname">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_sender_address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_sender" class="btn btn-admin">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit rate functionality
        document.querySelectorAll('.edit-rate').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const row = document.getElementById('rate-row-' + id);
                
                row.querySelectorAll('.rate-display').forEach(el => el.classList.add('d-none'));
                row.querySelectorAll('.rate-edit').forEach(el => el.classList.remove('d-none'));
                
                this.classList.add('d-none');
                row.querySelector('.save-rate').classList.remove('d-none');
            });
        });

        document.querySelectorAll('.save-rate').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const row = document.getElementById('rate-row-' + id);
                const inputs = row.querySelectorAll('.rate-edit');
                
                // Here you would normally send an AJAX request to update the rate
                // For now, we'll just toggle the display back
                row.querySelectorAll('.rate-display').forEach(el => el.classList.remove('d-none'));
                row.querySelectorAll('.rate-edit').forEach(el => el.classList.add('d-none'));
                
                this.classList.add('d-none');
                row.querySelector('.edit-rate').classList.remove('d-none');
            });
        });

        // Edit sender function
        function editSender(senderId) {
            // Fetch sender details and populate modal
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get_sender_admin&id=${senderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_sender_id').value = data.sender.id;
                    document.getElementById('edit_sender_mobile').value = data.sender.mobile;
                    document.getElementById('edit_sender_name').value = data.sender.name;
                    document.getElementById('edit_sender_surname').value = data.sender.surname;
                    document.getElementById('edit_sender_address').value = data.sender.address;
                    
                    new bootstrap.Modal(document.getElementById('editSenderModal')).show();
                }
            });
        }

        // Edit receiver function
        function editReceiver(receiverId) {
            // Similar implementation for receivers
            alert('Receiver editing functionality - similar to sender editing');
        }
    </script>
</body>
</html>