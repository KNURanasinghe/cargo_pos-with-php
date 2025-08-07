
<?php
// dashboard.php
require_once 'config.php';
requireLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_shipment'])) {
    $tracking_no = sanitize($_POST['tracking_no']);
    $shipping_date = $_POST['shipping_date'];
    $sender_mobile = sanitize($_POST['sender_mobile']);
    $receiver_id = (int)$_POST['receiver_id'];
    $no_of_boxes = (int)$_POST['no_of_boxes'];
    $actual_weight = (float)$_POST['actual_weight'];
    $chargeable_weight = (float)$_POST['chargeable_weight'];
    $kg_rate_id = (int)$_POST['kg_rate_id'];
    $tax_amount = (float)($_POST['tax_amount'] ?? 0);
    $remarks = sanitize($_POST['remarks'] ?? '');
    
    // Calculate freight and total
    $rate_stmt = $pdo->prepare("SELECT rate_per_kg FROM kg_rates WHERE id = ?");
    $rate_stmt->execute([$kg_rate_id]);
    $rate = $rate_stmt->fetchColumn();
    
    $freight_amount = $chargeable_weight * $rate;
    $total_payable = $freight_amount + $tax_amount;
    
    // Insert shipment
    $stmt = $pdo->prepare("INSERT INTO shipments (tracking_no, shipping_date, sender_mobile, receiver_id, no_of_boxes, actual_weight, chargeable_weight, kg_rate_id, freight_amount, tax_amount, total_payable, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$tracking_no, $shipping_date, $sender_mobile, $receiver_id, $no_of_boxes, $actual_weight, $chargeable_weight, $kg_rate_id, $freight_amount, $tax_amount, $total_payable, $remarks])) {
        $success_message = "Shipment created successfully! <a href='print_shipment.php?id=".$pdo->lastInsertId()."' target='_blank' class='btn btn-sm btn-primary'>Print</a>";
    } else {
        $error_message = "Error creating shipment!";
    }
}

// Get KG rates for dropdown
$rates_stmt = $pdo->query("SELECT * FROM kg_rates WHERE is_active = 1");
$kg_rates = $rates_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Dashboard</title>
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
        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border: none;
        }
        .receiver-item {
            cursor: pointer;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin: 5px 0;
        }
        .receiver-item:hover {
            background-color: #f8f9fa;
        }
        .receiver-item.selected {
            background-color: #e3f2fd;
            border-color: #2a5298;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shipping-fast"></i> Ceylon Express
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="register_user.php">Register User</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <?php if (isAdmin()): ?>
                    <a class="nav-link" href="admin_dashboard.php">Admin</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-plus-circle"></i> Create New Shipment</h4>
            </div>
            <div class="card-body">
                <form method="POST" id="shipmentForm">
                    <div class="row">
                        <!-- Tracking & Date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tracking Number</label>
                            <input type="text" class="form-control" name="tracking_no" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shipping Date</label>
                            <input type="text" class="form-control" name="shipping_date" id="shipping_date" required>
                        </div>
                    </div>

                    <!-- Sender Details -->
                    <h5 class="mt-4 mb-3 text-primary">Sender Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sender Mobile Number</label>
                            <input type="text" class="form-control" name="sender_mobile" id="sender_mobile" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sender Name</label>
                            <input type="text" class="form-control" id="sender_name" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Sender Address</label>
                            <textarea class="form-control" id="sender_address" rows="2" readonly></textarea>
                        </div>
                    </div>

                    <!-- Receiver Selection -->
                    <h5 class="mt-4 mb-3 text-primary">Receiver Details</h5>
                    <div id="receiver_list" class="mb-3" style="display: none;">
                        <label class="form-label">Select Receiver</label>
                        <div id="receiver_options"></div>
                    </div>

                    <input type="hidden" name="receiver_id" id="receiver_id" required>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Receiver Name</label>
                            <input type="text" class="form-control" id="receiver_name" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Receiver Phone</label>
                            <input type="text" class="form-control" id="receiver_phone" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Receiver Address</label>
                            <textarea class="form-control" id="receiver_address" rows="2" readonly></textarea>
                        </div>
                    </div>

                    <!-- Weight & Pricing -->
                    <h5 class="mt-4 mb-3 text-primary">Weight & Pricing</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">No. of Boxes</label>
                            <input type="number" class="form-control" name="no_of_boxes" value="1" min="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Actual Weight (kg)</label>
                            <input type="number" class="form-control" name="actual_weight" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Chargeable Weight (kg)</label>
                            <input type="number" class="form-control" name="chargeable_weight" id="chargeable_weight" step="0.01" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">KG Rate</label>
                            <select class="form-control" name="kg_rate_id" id="kg_rate" required>
                                <option value="">Select Rate</option>
                                <?php foreach ($kg_rates as $rate): ?>
                                    <option value="<?php echo $rate['id']; ?>" data-rate="<?php echo $rate['rate_per_kg']; ?>">
                                        <?php echo $rate['rate_name']; ?> - Rs. <?php echo number_format($rate['rate_per_kg'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Freight Amount</label>
                            <input type="text" class="form-control" id="freight_amount" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" class="form-control" name="tax_amount" id="tax_amount" step="0.01" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Payable</label>
                            <input type="text" class="form-control" id="total_payable" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="3"></textarea>
                    </div>

                    <button type="submit" name="create_shipment" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Create Shipment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("#shipping_date", {
            dateFormat: "Y-m-d",
            defaultDate: new Date()
        });

        // Handle sender mobile number input
        document.getElementById('sender_mobile').addEventListener('blur', function() {
            const mobile = this.value.trim();
            if (mobile) {
                fetchSenderDetails(mobile);
                fetchReceivers(mobile);
            }
        });

        // Calculate freight and total
        function calculateAmounts() {
            const chargeableWeight = parseFloat(document.getElementById('chargeable_weight').value) || 0;
            const rateSelect = document.getElementById('kg_rate');
            const selectedOption = rateSelect.options[rateSelect.selectedIndex];
            const rate = parseFloat(selectedOption.getAttribute('data-rate')) || 0;
            const taxAmount = parseFloat(document.getElementById('tax_amount').value) || 0;

            const freightAmount = chargeableWeight * rate;
            const totalPayable = freightAmount + taxAmount;

            document.getElementById('freight_amount').value = freightAmount.toFixed(2);
            document.getElementById('total_payable').value = totalPayable.toFixed(2);
        }

        document.getElementById('chargeable_weight').addEventListener('input', calculateAmounts);
        document.getElementById('kg_rate').addEventListener('change', calculateAmounts);
        document.getElementById('tax_amount').addEventListener('input', calculateAmounts);

        // Fetch sender details
        function fetchSenderDetails(mobile) {
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_sender&mobile=' + mobile
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('sender_name').value = data.sender.name + ' ' + data.sender.surname;
                    document.getElementById('sender_address').value = data.sender.address;
                } else {
                    document.getElementById('sender_name').value = '';
                    document.getElementById('sender_address').value = '';
                    alert('Sender not found! Please register the sender first.');
                }
            });
        }

        // Fetch receivers for mobile number
        function fetchReceivers(mobile) {
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_receivers&mobile=' + mobile
            })
            .then(response => response.json())
            .then(data => {
                const receiverList = document.getElementById('receiver_list');
                const receiverOptions = document.getElementById('receiver_options');
                
                if (data.success && data.receivers.length > 0) {
                    receiverList.style.display = 'block';
                    receiverOptions.innerHTML = '';
                    
                    data.receivers.forEach(receiver => {
                        const div = document.createElement('div');
                        div.className = 'receiver-item';
                        div.innerHTML = `<strong>${receiver.name}</strong><br><small>${receiver.address}</small>`;
                        div.onclick = () => selectReceiver(receiver, div);
                        receiverOptions.appendChild(div);
                    });
                } else {
                    receiverList.style.display = 'none';
                    alert('No receivers found for this mobile number!');
                }
            });
        }

        // Select receiver
        function selectReceiver(receiver, element) {
            // Remove selected class from all items
            document.querySelectorAll('.receiver-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selected class to clicked item
            element.classList.add('selected');
            
            // Fill receiver details
            document.getElementById('receiver_id').value = receiver.id;
            document.getElementById('receiver_name').value = receiver.name;
            document.getElementById('receiver_phone').value = receiver.phone1 + (receiver.phone2 ? ', ' + receiver.phone2 : '');
            document.getElementById('receiver_address').value = receiver.address;
        }
    </script>
</body>
</html>