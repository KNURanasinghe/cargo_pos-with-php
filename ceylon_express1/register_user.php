
<?php
// register_user.php
require_once 'config.php';
requireLogin();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register_sender'])) {
        // Register sender
        $mobile = sanitize($_POST['mobile']);
        $name = sanitize($_POST['name']);
        $surname = sanitize($_POST['surname']);
        $address = sanitize($_POST['address']);
        $tax_code = sanitize($_POST['tax_code']);
        $sex = sanitize($_POST['sex']);
        $dob = $_POST['dob'];
        $birth_country = sanitize($_POST['birth_country']);
        
        // Check if mobile already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM senders WHERE mobile = ?");
        $check_stmt->execute([$mobile]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $error_message = "Mobile number already registered!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO senders (mobile, name, surname, address, tax_code, sex, dob, birth_country) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$mobile, $name, $surname, $address, $tax_code, $sex, $dob, $birth_country])) {
                $success_message = "Sender registered successfully!";
            } else {
                $error_message = "Error registering sender!";
            }
        }
    } elseif (isset($_POST['register_receiver'])) {
        // Register receiver
        $sender_mobile = sanitize($_POST['sender_mobile']);
        $receiver_id = sanitize($_POST['receiver_id']);
        $name = sanitize($_POST['name']);
        $address = sanitize($_POST['address']);
        $phone1 = sanitize($_POST['phone1']);
        $phone2 = sanitize($_POST['phone2']);
        
        // Check if sender exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM senders WHERE mobile = ?");
        $check_stmt->execute([$sender_mobile]);
        
        if ($check_stmt->fetchColumn() == 0) {
            $error_message = "Sender mobile number not found! Please register sender first.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO receivers (sender_mobile, receiver_id, name, address, phone1, phone2) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$sender_mobile, $receiver_id, $name, $address, $phone1, $phone2])) {
                $success_message = "Receiver registered successfully!";
            } else {
                $error_message = "Error registering receiver!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border: none;
        }
        .nav-tabs .nav-link.active {
            background-color: #2a5298;
            border-color: #2a5298;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shipping-fast"></i> Ceylon Express
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
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

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-user-plus"></i> User Registration</h4>
            </div>
            <div class="card-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="registrationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sender-tab" data-bs-toggle="tab" data-bs-target="#sender" type="button" role="tab">
                            <i class="fas fa-user"></i> Register Sender
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="receiver-tab" data-bs-toggle="tab" data-bs-target="#receiver" type="button" role="tab">
                            <i class="fas fa-users"></i> Register Receiver
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-4" id="registrationTabsContent">
                    <!-- Sender Registration -->
                    <div class="tab-pane fade show active" id="sender" role="tabpanel">
                        <form method="POST">
                            <h5 class="mb-3 text-primary">Sender Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile Number *</label>
                                    <input type="text" class="form-control" name="mobile" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Surname *</label>
                                    <input type="text" class="form-control" name="surname" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Address *</label>
                                    <textarea class="form-control" name="address" rows="3" required></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tax Code</label>
                                    <input type="text" class="form-control" name="tax_code">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Sex</label>
                                    <select class="form-control" name="sex">
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="text" class="form-control" name="dob" id="dob">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Birth Country</label>
                                    <input type="text" class="form-control" name="birth_country">
                                </div>
                            </div>
                            
                            <button type="submit" name="register_sender" class="btn btn-primary">
                                <i class="fas fa-save"></i> Register Sender
                            </button>
                        </form>
                    </div>

                    <!-- Receiver Registration -->
                    <div class="tab-pane fade" id="receiver" role="tabpanel">
                        <form method="POST">
                            <h5 class="mb-3 text-primary">Receiver Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sender Mobile Number *</label>
                                    <input type="text" class="form-control" name="sender_mobile" required>
                                    <div class="form-text">The mobile number of the registered sender</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Receiver ID *</label>
                                    <input type="text" class="form-control" name="receiver_id" required>
                                    <div class="form-text">Unique identifier for this receiver</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Receiver Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Receiver Address *</label>
                                    <textarea class="form-control" name="address" rows="3" required></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number 1</label>
                                    <input type="text" class="form-control" name="phone1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number 2</label>
                                    <input type="text" class="form-control" name="phone2">
                                </div>
                            </div>
                            
                            <button type="submit" name="register_receiver" class="btn btn-primary">
                                <i class="fas fa-save"></i> Register Receiver
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("#dob", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
    </script>
</body>
</html>