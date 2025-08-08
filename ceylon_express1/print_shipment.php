<?php
// print_shipment.php
require_once 'config.php';
requireLogin();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$shipment_id = (int)$_GET['id'];

// Get shipment details with all related information
$stmt = $pdo->prepare("
    SELECT s.*, 
           snd.name as sender_name, snd.surname as sender_surname, snd.address as sender_address,
           snd.tax_code as sender_tax_code, snd.sex as sender_sex, snd.dob as sender_dob,
           rcv.name as receiver_name, rcv.address as receiver_address, rcv.phone1, rcv.phone2, rcv.receiver_id,
           kr.rate_name, kr.rate_per_kg
    FROM shipments s
    JOIN senders snd ON s.sender_mobile = snd.mobile
    JOIN receivers rcv ON s.receiver_id = rcv.id
    JOIN kg_rates kr ON s.kg_rate_id = kr.id
    WHERE s.id = ?
");
$stmt->execute([$shipment_id]);
$shipment = $stmt->fetch();

if (!$shipment) {
    header("Location: dashboard.php");
    exit();
}

// Get company details
$company_stmt = $pdo->query("SELECT * FROM company_details ORDER BY id DESC LIMIT 1");
$company = $company_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ceylon Express - Shipment <?php echo $shipment['tracking_no']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0.5cm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 19cm;
            margin: 0 auto;
            padding: 10px 15px;
            border: 1px solid #000;
            min-height: 27.7cm;
            box-sizing: border-box;
            position: relative;
            
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .company-info {
            flex: 1;
        }

        .company-info h3 {
            margin: 0 0 3px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .company-info p {
            margin: 1px 0;
            font-size: 9px;
        }

        .logo-section {
            flex: 0 0 100px;
            text-align: right;
        }

        .logo-section h2 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .tracking-section {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .tracking-no {
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #000;
            padding: 3px;
            background: #f0f0f0;
            margin: 5px 0;
        }

        .mobile-field {
            border: 1px solid #000;
            padding: 3px;
            width: 120px;
            text-align: right;
        }

        .section {
            margin: 8px 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
            font-size: 10px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            table-layout: fixed;
        }

        .form-table td,
        .form-table th {
            border: 1px solid #000;
            padding: 3px;
            vertical-align: top;
            font-size: 9px;
            word-wrap: break-word;
        }

        .form-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .form-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 15%;
        }

        .form-table .value {
            width: 35%;
        }

        .sinhala-text {
            text-align: center;
            margin: 10px 0;
            padding: 5px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            font-size: 9px;
            line-height: 1.3;
            font-family: 'Noto Sans Sinhala', Arial, sans-serif;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 6px;
            position: absolute;
            bottom: 30px;
            width: calc(100% - 70px);
        }

        .branch-section {
            position: absolute;
            bottom: 10px;
            width: calc(100% - 30px);
            text-align: center;
        }

        .signature-box {
            text-align: center;
            width: 30%;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 9px;
        }

        .signature-box-date {
            text-align: center;
            width: 30%;
            /* border-top: 1px solid #000; */
            padding-bottom: 3px;
            font-size: 9px;
        }

        .remarks-section {
            margin: 8px 0;
            min-height: 30px;
            border: 1px solid #000;
            padding: 3px;
            font-size: 9px;
        }

        .pricing-table {
            width: 50%;
            margin-left: auto;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .pricing-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 9px;
        }

        .pricing-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 60%;
        }

        .pricing-table .value {
            width: 40%;
            text-align: right;
        }

        .total-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .container {
                border: none;
            }
        }

        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info "style="margin-top: 10px;">
                <h3><?php echo $company['company_name'] ?? 'Ceylon Express'; ?></h3>
                <p>Via Principe Eugenio, 83</p>
                <p>00185 Roma,</p>
                <p>Piazza Vittorio.</p>
                <p>Italy - <?php echo $company['phone'] ?? ''; ?></p>
                <!-- <p>Sri Lanka - +94 76 126 7433 / +94 76 074 5058</p> -->
                <p>E-Mail : CEYLONEXPRESS83@gmail.com</p>
            </div>

            <div class="logo-section">
                <?php if (file_exists('assets/images/logo.jpg')): ?>
                    <h2 style="font-size: 12px; margin-top: 6px;">Ceylon Express</h2>
                    <img src="assets/images/logo.jpg" alt="Ceylon Express Logo" style="max-width: 80px; max-height: 80px; margin-top: 3px;">
                <?php else: ?>
                    <h2 style="font-size: 12px;">Ceylon Express</h2>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipping Details -->
        <div class="tracking-section">
            <div>
                <strong>Sender's Details</strong><br>
                <strong>Shipping Date:</strong> <?php echo date('Y-m-d', strtotime($shipment['shipping_date'])); ?>
            </div>
            <div>
                <strong>MOBILE NO:</strong>
                <span class="mobile-field"><?php echo $shipment['sender_mobile']; ?></span>
            </div>
        </div>

        <div class="tracking-no">
            <strong>TRACKING NO: <?php echo strtoupper($shipment['tracking_no']); ?></strong>
        </div>

        <!-- Sender Details -->
        <div class="section">
            <div class="section-title">DETTAGLI DEI MITTENTI (SENDERS DETAILS)</div>
            <table class="form-table">
                <tr>
                    <td class="label">NOME (NAME)</td>
                    <td class="value"><?php echo $shipment['sender_name']; ?></td>
                    <td class="label">COGNOME (SURNAME)</td>
                    <td class="value"><?php echo $shipment['sender_surname']; ?></td>
                </tr>
                <tr>
                    <td class="label">INDIRIZZO (ADDRESS)</td>
                    <td class="value" colspan="3"><?php echo $shipment['sender_address']; ?></td>
                </tr>
                <tr>
                    <td class="label">CODICE FISCALE (TAX CODE)</td>
                    <td class="value"><?php echo $shipment['sender_tax_code'] ?: ''; ?></td>
                    <td class="label">DATA DI NASCITA (DATE OF BIRTH)</td>
                    <td class="value"><?php echo $shipment['sender_dob'] ? date('d/m/Y', strtotime($shipment['sender_dob'])) : ''; ?></td>
                </tr>
                <tr>
                    <td class="label">SESSO (SEX)</td>
                    <td class="value"><?php echo $shipment['sender_sex'] ?: ''; ?></td>
                    <td class="label">COMUNE DI NASCITA (BIRTH PLACE)</td>
                    <td class="value"></td>
                </tr>
            </table>
        </div>

        <!-- Receiver Details -->
        <div class="section">
            <div class="section-title">RECEIVER DETAILS</div>
            <table class="form-table">
                <tr>
                    <td class="label">NAME</td>
                    <td class="value"><?php echo $shipment['receiver_name']; ?></td>
                    <td class="label">RECEIVER ID</td>
                    <td class="value"><?php echo $shipment['receiver_id']; ?></td>
                </tr>
                <tr>
                    <td class="label">ADDRESS</td>
                    <td class="value" colspan="3"><?php echo $shipment['receiver_address']; ?></td>
                </tr>
                <tr>
                    <td class="label">PHONE NO.</td>
                    <td class="value" colspan="3"><?php echo $shipment['phone1'] . ($shipment['phone2'] ? ', ' . $shipment['phone2'] : ''); ?></td>
                </tr>
            </table>
        </div>

        <!-- Sinhala Text -->
        <div class="sinhala-text">
            <?php echo htmlspecialchars_decode($company['sinhala_text'] ?? ''); ?>
        </div>

        <div class="section-title">REMARKS:</div>
        <div class="remarks-section">
            <?php echo nl2br(htmlspecialchars($shipment['remarks'])); ?>
        </div>

        <!-- Items and Weight -->
        <table class="form-table" style="margin-top: 10px;">
            <tr>
                <th>ITEMS DESCRIPTION</th>
                <th>NO. OF BOXES</th>
                <th>ACTUAL WEIGHT</th>
                <th>CHARGEABLE WEIGHT</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($shipment['items_description'] ?? ''); ?></td>
                <td><?php echo $shipment['no_of_boxes']; ?></td>
                <td><?php echo number_format($shipment['actual_weight'], 2); ?> kg</td>
                <td><?php echo number_format($shipment['chargeable_weight'], 2); ?> kg</td>
            </tr>
        </table>

        <!-- Pricing Table -->
        <table class="pricing-table">
            <tr>
                <td class="label">KG RATE</td>
                <td class="value">€ <?php echo number_format($shipment['rate_per_kg'], 2); ?></td>
            </tr>
            <tr>
                <td class="label">FREIGHT AMOUNT</td>
                <td class="value">€ <?php echo number_format($shipment['freight_amount'], 2); ?></td>
            </tr>
            <tr>
                <td class="label">TAX</td>
                <td class="value">€ <?php echo number_format($shipment['tax_amount'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td class="label">TOTAL PAYABLE</td>
                <td class="value">€ <?php echo number_format($shipment['total_payable'], 2); ?></td>
            </tr>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div>SIGNATURE</div>
            </div>
            <div class="signature-box-date">
                <div><?php echo date('d/m/Y H:i'); ?></div>
                <div>DATE & TIME</div>
                
            </div>
            <div class="signature-box">
                <div>Ceylon Express</div>
            </div>
        </div>

         <div class="branch-section">
            <div style="text-align: center; width: 100%; font-size: 9px; padding-top: 5px;">
                WAREHOUSE (PILIYANDALA) - 076 484 8506 | (JA-ELA) - +94117006651 / 0741131169 
            </div>
        </div>
    </div>
</body>
</html>