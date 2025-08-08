<?php
// reports.php - Enhanced with Delete Functionality
require_once 'config.php';
requireLogin();

$success_message = '';
$error_message = '';

// Handle delete shipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_shipment'])) {
    $shipment_id = (int)$_POST['shipment_id'];
    
    try {
        // Check if user has permission to delete (admin only for now since we don't have created_by field)
        $check_stmt = $pdo->prepare("SELECT tracking_no FROM shipments WHERE id = ?");
        $check_stmt->execute([$shipment_id]);
        $shipment = $check_stmt->fetch();
        
        $company_stmt = $pdo->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
        $company = $company_stmt->fetch();
        
        if (!$shipment) {
            $error_message = "Shipment not found!";
        } elseif (!isAdmin()) {
            $error_message = "Only administrators can delete shipments!";
        } else {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete from shipment_tracking if table exists
            try {
                $pdo->prepare("DELETE FROM shipment_tracking WHERE shipment_id = ?")->execute([$shipment_id]);
            } catch (PDOException $e) {
                // Table might not exist, continue
            }
            
            // Delete the shipment
            $delete_stmt = $pdo->prepare("DELETE FROM shipments WHERE id = ?");
            $delete_stmt->execute([$shipment_id]);
            
            // Log the deletion activity if activity_logs table exists
            try {
                if (isset($_SESSION['user_id'])) {
                    $log_stmt = $pdo->prepare("
                        INSERT INTO activity_logs (user_id, action, table_name, record_id, old_values, ip_address) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $log_stmt->execute([
                        $_SESSION['user_id'],
                        'DELETE_SHIPMENT',
                        'shipments',
                        $shipment_id,
                        'Tracking: ' . $shipment['tracking_no'],
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }
            } catch (PDOException $e) {
                // Activity logs table might not exist, continue
            }
            
            $pdo->commit();
            $success_message = "Shipment {$shipment['tracking_no']} deleted successfully!";
        }
    } catch (PDOException $e) {
        $pdo->rollback();
        $error_message = "Error deleting shipment: " . $e->getMessage();
    }
}

$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$mobile_filter = $_GET['mobile_filter'] ?? '';

// Build query conditions - CHANGED TO USE created_at INSTEAD OF shipping_date
$conditions = ["1=1"];
$params = [];

if ($from_date) {
    $conditions[] = "s.created_at >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $conditions[] = "s.created_at <= ?";
    $params[] = $to_date . ' 23:59:59'; // Include all of the end date
}

if ($mobile_filter) {
    $conditions[] = "s.sender_mobile LIKE ?";
    $params[] = "%$mobile_filter%";
}

$where_clause = implode(" AND ", $conditions);

// Get shipments with all details - MODIFIED TO INCLUDE created_at
$stmt = $pdo->prepare("
    SELECT s.*, 
           snd.name as sender_name, snd.surname as sender_surname, snd.address as sender_address,snd.tax_code as sender_Tax_code,
           rcv.name as receiver_name, rcv.address as receiver_address, rcv.phone1, rcv.phone2, rcv.receiver_id,
           kr.rate_name, kr.rate_per_kg
    FROM shipments s
    JOIN senders snd ON s.sender_mobile = snd.mobile
    JOIN receivers rcv ON s.receiver_id = rcv.id
    JOIN kg_rates kr ON s.kg_rate_id = kr.id
    WHERE $where_clause
    ORDER BY s.created_at DESC, s.id DESC
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

// Get company details for print header
$company_stmt = $pdo->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
$company = $company_stmt->fetch();
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
        .action-buttons {
            white-space: nowrap;
        }
        .btn-delete {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            color: white;
        }
        .btn-delete:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            color: white;
        }
        
        /* Print-specific styles */
        .btn-print-bill {
            background: linear-gradient(45deg, #6f42c1, #8e44ad);
            border: none;
            color: white;
        }

        /* Hide print elements on screen */
.print-header,
.print-totals,
.print-footer {
    display: none !important;
}

        /* Bill-specific print styles */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.5cm;
            }
            
            body {
                background-color: white !important;
                font-family: Arial, sans-serif;
                font-size: 9px !important;
                line-height: 1.2;
                color: #000;
                margin: 0;
                padding: 0;
            }
            
            .no-print, .navbar, .card-header, .btn, .form-control, .stats-card {
                display: none !important;
            }
            
            .container {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 10px !important;
            }
            
            .card-body {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .table-responsive {
                overflow: visible !important;
                width: 100% !important;
            }
            
            /* Hide unwanted columns when in bill print mode */
            .bill-print-mode th:nth-child(1), /* Created Date */
            .bill-print-mode td:nth-child(1),
            .bill-print-mode th:nth-child(5), /* Tax Code */
            .bill-print-mode td:nth-child(5),
            .bill-print-mode th:nth-child(7), /* Receiver Id */
            .bill-print-mode td:nth-child(7),
            .bill-print-mode th:nth-child(9), /* Receiver Mobile 1 */
            .bill-print-mode td:nth-child(9),
            .bill-print-mode th:nth-child(10), /* Receiver Mobile 2 */
            .bill-print-mode td:nth-child(10),
            .bill-print-mode th:nth-child(19), /* Actions */
            .bill-print-mode td:nth-child(19) {
                display: none !important;
            }
            
            table {
                width: 100% !important;
                font-size: 8px !important;
                border-collapse: collapse !important;
                margin: 0 !important;
            }
            
            th, td {
                padding: 3px 2px !important;
                border: 1px solid #000 !important;
                word-wrap: break-word !important;
                vertical-align: top !important;
            }
            
            th {
                background-color: #f0f0f0 !important;
                font-weight: bold !important;
                text-align: center !important;
            }
            
            /* Adjust column widths for visible columns */
            th:nth-child(2), td:nth-child(2) { width: 8% !important; } /* Shipment Date */
            th:nth-child(3), td:nth-child(3) { width: 10% !important; } /* Tracking */
            th:nth-child(4), td:nth-child(4) { width: 12% !important; } /* Sender */
            th:nth-child(6), td:nth-child(6) { width: 8% !important; } /* Mobile */
            th:nth-child(8), td:nth-child(8) { width: 12% !important; } /* Receiver */
            th:nth-child(11), td:nth-child(11) { width: 15% !important; } /* Receiver Address */
            th:nth-child(12), td:nth-child(12) { width: 5% !important; } /* Boxes */
            th:nth-child(13), td:nth-child(13) { width: 6% !important; } /* Weight */
            th:nth-child(14), td:nth-child(14) { width: 6% !important; } /* Charge Weight */
            th:nth-child(15), td:nth-child(15) { width: 6% !important; } /* Rate */
            th:nth-child(16), td:nth-child(16) { width: 6% !important; } /* Freight */
            th:nth-child(17), td:nth-child(17) { width: 4% !important; } /* Tax */
            th:nth-child(18), td:nth-child(18) { width: 6% !important; } /* Total */
            
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 15px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            
            .print-company-info {
                display: flex !important;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 10px;
            }
            
            .print-company-left {
                flex: 1;
                text-align: left;
            }
            
            .print-company-right {
                flex: 1;
                text-align: right;
            }
            
            .print-company-info h3 {
                margin: 0 0 5px 0 !important;
                font-size: 16px !important;
                font-weight: bold !important;
            }
            
            .print-company-info p {
                margin: 1px 0 !important;
                font-size: 10px !important;
            }
            
            .print-date-range {
                display: block !important;
                margin: 10px 0;
                font-weight: bold;
                font-size: 11px !important;
                text-align: center;
            }
            
            .print-totals {
                display: block !important;
                margin-top: 15px;
                border-top: 2px solid #000;
                padding-top: 10px;
            }
            
            .print-totals-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin-bottom: 10px;
            }
            
            .print-total-item {
                text-align: center;
                border: 1px solid #000;
                padding: 8px 4px;
                background-color: #f5f5f5;
            }
            
            .print-total-item strong {
                display: block;
                font-size: 11px !important;
                margin-bottom: 2px;
            }
            
            .print-total-item span {
                font-size: 9px !important;
            }
            
            .print-footer {
                display: block !important;
                text-align: center;
                margin-top: 15px;
                font-size: 8px !important;
                border-top: 1px solid #ccc;
                padding-top: 5px;
            }
            
            tfoot {
                background-color: #e0e0e0 !important;
                font-weight: bold !important;
            }
            
            tfoot th {
                background-color: #e0e0e0 !important;
                font-weight: bold !important;
            }
            
            /* Hide unwanted columns in tfoot when in bill print mode */
            .bill-print-mode tfoot th:nth-child(1), /* Created Date */
            .bill-print-mode tfoot th:nth-child(5), /* Tax Code */
            .bill-print-mode tfoot th:nth-child(7), /* Receiver Id */
            .bill-print-mode tfoot th:nth-child(9), /* Receiver Mobile 1 */
            .bill-print-mode tfoot th:nth-child(10), /* Receiver Mobile 2 */
            .bill-print-mode tfoot th:nth-child(19) { /* Actions */
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
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
        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Print Header -->
        <div class="print-header">
            <div class="print-company-info">
                <div class="print-company-left">
                    <h3><?php echo $company['company_name'] ?? 'Ceylon Express'; ?></h3>
                    <p>Via Principe Eugenio, 83</p>
                    <p>00185 Roma, Piazza Vittorio.</p>
                    <p>Italy - <?php echo $company['phone'] ?? ''; ?></p>
                    <p>E-Mail: CEYLONEXPRESS83@gmail.com</p>
                </div>
                <div class="print-company-right">
                    <h3>DETAILED SHIPMENT REPORT</h3>
                    <p>Generated: <?php echo date('d/m/Y H:i'); ?></p>
                    <?php if ($from_date || $to_date): ?>
                        <div class="print-date-range">
                            <?php if ($from_date && $to_date): ?>
                                Period: <?php echo date('d/m/Y', strtotime($from_date)); ?> to <?php echo date('d/m/Y', strtotime($to_date)); ?>
                            <?php elseif ($from_date): ?>
                                From: <?php echo date('d/m/Y', strtotime($from_date)); ?>
                            <?php elseif ($to_date): ?>
                                To: <?php echo date('d/m/Y', strtotime($to_date)); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <p>Total Shipments: <strong><?php echo $total_shipments; ?></strong></p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card no-print">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-filter"></i> Report Filters</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Created Date</label>
                        <input type="text" class="form-control" name="from_date" id="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Created Date</label>
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
                            <button type="button" onclick="clearFilters()" class="btn btn-secondary" style="margin-top: 5px;">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button onclick="printBill()" class="btn btn-print-bill">
                        <i class="fas fa-print"></i> Print Report Bill
                    </button>
                    <button onclick="exportToCSV()" class="btn btn-export">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row no-print">
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
                    <h3>€ <?php echo number_format($total_payable, 2); ?></h3>
                    <p class="mb-0">Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Detailed Report -->
        <div class="card">
            <div class="card-header bg-info text-white no-print">
                <h5><i class="fas fa-table"></i> Detailed Shipment Report</h5>
                <small class="text-light">Only administrators can delete shipments</small>
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
                                    <th>Created Date</th>
                                    <th>Shipment Date</th>
                                    <th>Tracking</th>
                                    <th>Sender</th>
                                    <th>Mobile</th>
                                    <th>Tax Code</th>
                                    <th>Receiver</th>
                                    <th>Receiver Id</th>
                                    <th>Receiver Address</th>
                                    <th>Receiver Mobile 1</th>
                                    <th>Receiver Mobile 2</th>
                                    <th>Boxes</th>
                                    <th>Weight</th>
                                    <th>Charge Weight</th>
                                    <th>Rate</th>
                                    <th>Freight</th>
                                    <th>Tax</th>
                                    <th>Total</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shipments as $shipment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($shipment['created_at'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($shipment['shipping_date'])); ?></td>
                                        <td>
                                            <strong><?php echo $shipment['tracking_no']; ?></strong>
                                        </td>
                                        <td><?php echo $shipment['sender_name'] . ' ' . $shipment['sender_surname']; ?></td>
                                        <td><?php echo $shipment['sender_mobile']; ?></td>
                                        <td><?php echo $shipment['sender_Tax_code']; ?></td>
                                        <td><?php echo $shipment['receiver_name']; ?></td>
                                        <td><?php echo $shipment['receiver_id']; ?></td>
                                        <td>
                                            <small><?php echo substr($shipment['receiver_address'], 0, 50) . (strlen($shipment['receiver_address']) > 50 ? '...' : ''); ?></small>
                                        </td>
                                        <td><?php echo $shipment['phone1']; ?></td>
                                        <td><?php echo $shipment['phone2']; ?></td>
                                        <td><?php echo $shipment['no_of_boxes']; ?></td>
                                        <td><?php echo number_format($shipment['actual_weight'], 2); ?></td>
                                        <td><?php echo number_format($shipment['chargeable_weight'], 2); ?></td>
                                        <td><?php echo number_format($shipment['rate_per_kg'], 2); ?></td>
                                        <td>€ <?php echo number_format($shipment['freight_amount'], 2); ?></td>
                                        <td>€ <?php echo number_format($shipment['tax_amount'], 2); ?></td>
                                        <td><strong>€ <?php echo number_format($shipment['total_payable'], 2); ?></strong></td>
                                        <td class="no-print">
                                            <div class="action-buttons">
                                                <a href="print_shipment.php?id=<?php echo $shipment['id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Print Shipment">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <?php if (isAdmin()): ?>
                                                    <button type="button" class="btn btn-sm btn-delete" 
                                                            onclick="confirmDelete(<?php echo $shipment['id']; ?>, '<?php echo htmlspecialchars($shipment['tracking_no']); ?>')"
                                                            title="Delete Shipment">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (file_exists('edit_shipment.php')): ?>
                                                    <a href="edit_shipment.php?id=<?php echo $shipment['id']; ?>" class="btn btn-sm btn-warning" title="Edit Shipment">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="11">TOTALS:</th>
                                    <th><?php echo $total_boxes; ?></th>
                                    <th><?php echo number_format($total_weight, 2); ?></th>
                                    <th><?php echo number_format($total_chargeable_weight, 2); ?></th>
                                    <th>-</th>
                                    <th>€ <?php echo number_format($total_freight, 2); ?></th>
                                    <th>€ <?php echo number_format($total_tax, 2); ?></th>
                                    <th><strong>€ <?php echo number_format($total_payable, 2); ?></strong></th>
                                    <th class="no-print">-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="print-totals">
            <div class="print-totals-grid">
                <div class="print-total-item">
                    <strong><?php echo $total_shipments; ?></strong>
                    <span>Total Shipments</span>
                </div>
                <div class="print-total-item">
                    <strong><?php echo $total_boxes; ?></strong>
                    <span>Total Boxes</span>
                </div>
                <div class="print-total-item">
                    <strong><?php echo number_format($total_weight, 2); ?> kg</strong>
                    <span>Total Weight</span>
                </div>
                <div class="print-total-item">
                    <strong>€<?php echo number_format($total_payable, 2); ?></strong>
                    <span>Total Revenue</span>
                </div>
            </div>
        </div>

        <!-- Print Footer -->
        <div class="print-footer">
            <p>WAREHOUSE (PILIYANDALA) - 076 484 8506 | (JA-ELA) - +94117006651 / 0741131169</p>
            <p>This report contains <?php echo $total_shipments; ?> shipments | Generated on: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <p>Are you sure you want to delete shipment <strong id="delete-tracking-no"></strong>?</p>
                    <p class="text-muted">This will permanently remove:</p>
                    <ul class="text-muted">
                        <li>Shipment record and all details</li>
                        <li>Associated tracking information</li>
                        <li>Payment and billing records</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="shipment_id" id="delete-shipment-id">
                        <button type="submit" name="delete_shipment" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Shipment
                        </button>
                    </form>
                </div>
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

        // Bill print function that shows only selected columns
        function printBill() {
            // Add bill-print-mode class to body
            document.body.classList.add('bill-print-mode');
            
            // Small delay to ensure CSS is applied
            setTimeout(() => {
                // Print the page
                window.print();
                
                // Remove the class after printing
                setTimeout(() => {
                    document.body.classList.remove('bill-print-mode');
                }, 1000);
            }, 100);
        }

        // Clear filters function
        function clearFilters() {
            document.getElementById('from_date').value = '';
            document.getElementById('to_date').value = '';
            document.querySelector('input[name="mobile_filter"]').value = '';
            document.forms[0].submit();
        }

        // Confirm delete function
        function confirmDelete(shipmentId, trackingNo) {
            document.getElementById('delete-shipment-id').value = shipmentId;
            document.getElementById('delete-tracking-no').textContent = trackingNo;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Export to CSV function (for bill columns only)
       function exportToCSV() {
            // Define the specific columns we want to export
            const csvHeaders = [
                // 'Created Date',
                'Tracking No',
                'Sender Name', 
                'Tax Code',
                'Mobile No',
                'Receiver Name',
                'Receiver Address',
                'Receiver Mobile 1',
                'Receiver Mobile 2',
                'Receiver ID',
                'Boxes',
                'Weight'
            ];
            
            let csv = [];
            csv.push(csvHeaders.map(header => '"' + header + '"').join(','));
            
            // Get all data rows from the table
            const tableRows = document.querySelectorAll('table tbody tr');
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    // Extract the specific data we need
                    //const createdDate = cells[0].textContent.trim(); // Created date
                    const trackingNo = cells[2].textContent.trim(); // Tracking number
                    const senderName = cells[3].textContent.trim(); // Sender name
                    const mobileNo = cells[4].textContent.trim(); // Mobile number
                    const taxCode = cells[5].textContent.trim();
                    const receiverName = cells[6].textContent.trim(); // Receiver name
                    const receiverAddress = cells[8].textContent.trim(); // Receiver address
                    const receiverMobile1 = cells[9].textContent.trim(); 
                    const receiverMobile2 = cells[10].textContent.trim(); 
                    const boxes = cells[11].textContent.trim(); // Number of boxes
                    const weight = cells[12].textContent.trim(); // Actual weight
                    const receiverId = cells[7].textContent.trim(); // Receiver ID
                    
                    const rowData = [
                        //createdDate,
                        trackingNo,
                        senderName,
                        taxCode,
                        mobileNo,
                        receiverName,
                        receiverAddress,
                        receiverMobile1,
                        receiverMobile2,
                        receiverId,
                        boxes,
                        weight
                    ];
                    
                    // Clean and escape the data
                    const cleanedData = rowData.map(item => {
                        let text = String(item).replace(/"/g, '""');
                        text = text.replace(/\s+/g, ' ').trim();
                        return '"' + text + '"';
                    });
                    
                    csv.push(cleanedData.join(','));
                }
            });
            
            // Create and download the CSV file
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'shipment_export_' + new Date().toISOString().slice(0,10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
    </script>
</body>
</html>