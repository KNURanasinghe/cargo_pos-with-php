
<?php
// reports.php
require_once 'config.php';
requireLogin();

$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$mobile_filter = $_GET['mobile_filter'] ?? '';

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($from_date) {
    $conditions[] = "s.shipping_date >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $conditions[] = "s.shipping_date <= ?";
    $params[] = $to_date;
}

if ($mobile_filter) {
    $conditions[] = "s.sender_mobile LIKE ?";
    $params[] = "%$mobile_filter%";
}

$where_clause = implode(" AND ", $conditions);

// Get shipments with all details
$stmt = $pdo->prepare("
    SELECT s.*, 
           snd.name as sender_name, snd.surname as sender_surname, snd.address as sender_address,
           rcv.name as receiver_name, rcv.address as receiver_address, rcv.phone1, rcv.phone2, rcv.receiver_id,
           kr.rate_name, kr.rate_per_kg
    FROM shipments s
    JOIN senders snd ON s.sender_mobile = snd.mobile
    JOIN receivers rcv ON s.receiver_id = rcv.id
    JOIN kg_rates kr ON s.kg_rate_id = kr.id
    WHERE $where_clause
    ORDER BY s.shipping_date DESC, s.id DESC
");

$stmt->execute($params);
$shipments = $stmt->fetchAll();

// Calculate totals
$total_shipments = count($shipments);
$total_weight = array_sum(array_column($shipments, 'actual_weight'));
$total_chargeable_weight = array_sum(array_column($shipments, 'chargeable_weight'));
$total_freight = array_sum(array_column($shipments, 'freight_amount'));
$total_tax = array_sum(array_column($shipments, 'tax_amount'));
$total_payable = array_sum(array_column($shipments, 'total_payable'));
$total_boxes = array_sum(array_column($shipments, 'no_of_boxes'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Reports</title>
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
        .stats-card {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .btn-export {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
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
                <a class="nav-link" href="register_user.php">Register User</a>
                <?php if (isAdmin()): ?>
                    <a class="nav-link" href="admin_dashboard.php">Admin</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Filters -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-filter"></i> Report Filters</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="text" class="form-control" name="from_date" id="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="text" class="form-control" name="to_date" id="to_date" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" name="mobile_filter" value="<?php echo htmlspecialchars($mobile_filter); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mt-3">
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo $total_shipments; ?></h3>
                    <p class="mb-0">Total Shipments</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo number_format($total_weight, 2); ?> kg</h3>
                    <p class="mb-0">Total Weight</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?php echo $total_boxes; ?></h3>
                    <p class="mb-0">Total Boxes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3>Rs. <?php echo number_format($total_payable, 2); ?></h3>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Detailed Report -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-table"></i> Detailed Shipment Report</h5>
            </div>
            <div class="card-body">
                <?php if (empty($shipments)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No shipments found for the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Tracking</th>
                                    <th>Sender</th>
                                    <th>Mobile</th>
                                    <th>Receiver</th>
                                    <th>Receiver Address</th>
                                    <th>Boxes</th>
                                    <th>Weight</th>
                                    <th>Charge Weight</th>
                                    <th>Rate</th>
                                    <th>Freight</th>
                                    <th>Tax</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shipments as $shipment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($shipment['shipping_date'])); ?></td>
                                        <td>
                                            <strong><?php echo $shipment['tracking_no']; ?></strong>
                                        </td>
                                        <td><?php echo $shipment['sender_name'] . ' ' . $shipment['sender_surname']; ?></td>
                                        <td><?php echo $shipment['sender_mobile']; ?></td>
                                        <td><?php echo $shipment['receiver_name']; ?></td>
                                        <td>
                                            <small><?php echo substr($shipment['receiver_address'], 0, 50) . (strlen($shipment['receiver_address']) > 50 ? '...' : ''); ?></small>
                                        </td>
                                        <td><?php echo $shipment['no_of_boxes']; ?></td>
                                        <td><?php echo number_format($shipment['actual_weight'], 2); ?></td>
                                        <td><?php echo number_format($shipment['chargeable_weight'], 2); ?></td>
                                        <td><?php echo number_format($shipment['rate_per_kg'], 2); ?></td>
                                        <td>Rs. <?php echo number_format($shipment['freight_amount'], 2); ?></td>
                                        <td>Rs. <?php echo number_format($shipment['tax_amount'], 2); ?></td>
                                        <td><strong>Rs. <?php echo number_format($shipment['total_payable'], 2); ?></strong></td>
                                        <td>
                                            <a href="print_shipment.php?id=<?php echo $shipment['id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="6">TOTALS:</th>
                                    <th><?php echo $total_boxes; ?></th>
                                    <th><?php echo number_format($total_weight, 2); ?></th>
                                    <th><?php echo number_format($total_chargeable_weight, 2); ?></th>
                                    <th>-</th>
                                    <th>Rs. <?php echo number_format($total_freight, 2); ?></th>
                                    <th>Rs. <?php echo number_format($total_tax, 2); ?></th>
                                    <th><strong>Rs. <?php echo number_format($total_payable, 2); ?></strong></th>
                                    <th>-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        flatpickr("#from_date", {
            dateFormat: "Y-m-d"
        });
        
        flatpickr("#to_date", {
            dateFormat: "Y-m-d"
        });
    </script>
</body>
</html>