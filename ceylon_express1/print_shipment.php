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
            margin: 0.5in;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 10px;
            border: 2px solid #000;
            min-height: 10.5in;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .company-info {
            flex: 1;
        }

        .company-info h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 10px;
        }

        .logo-section {
            flex: 1;
            text-align: right;
        }

        .logo-section h2 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .tracking-section {
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
        }

        .tracking-no {
            font-size: 14px;
            font-weight: bold;
            border: 1px solid #000;
            padding: 5px;
            background: #f0f0f0;
        }

        .mobile-field {
            border: 1px solid #000;
            padding: 5px;
            width: 150px;
            text-align: right;
        }

        .section {
            margin: 15px 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .form-table td,
        .form-table th {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }

        .form-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
        }

        .form-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10px;
            width: 25%;
        }

        .form-table .value {
            width: 75%;
        }

        .sinhala-text {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            font-size: 11px;
            line-height: 1.4;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
        }

        .signature-box {
            text-align: center;
            width: 30%;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .remarks-section {
            margin: 15px 0;
            min-height: 40px;
            border: 1px solid #000;
            padding: 5px;
        }
        .sinhala-text {
    text-align: center;
    margin: 20px 0;
    padding: 10px;
    border: 1px solid #ccc;
    background-color: #f9f9f9;
    font-size: 11px;
    line-height: 1.4;
    font-family: 'Noto Sans Sinhala', Arial, sans-serif;
    direction: ltr; /* Left-to-right for proper rendering */
}

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }
        }

        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h3><?php echo $company['company_name'] ?? 'Ceylon Express'; ?></h3>
                <p>Via Principe Eugenio, 83</p>
                <p>00185 Roma,</p>
                <p>Piazza Vittorio.</p>
                <p>Italy - <?php echo $company['phone'] ?? 'Ceylon Express'; ?></p>
                <p>Sri Lanka - +94 76 126 7433 / +94 76 074 5058</p>
                <p>E-Mail : CEYLONEXPRESS83@gmail.com</p>
            </div>

            <<div class="logo-section">
                <?php if (file_exists('assets/images/logo.jpg')): ?>
                    <h2 style="font-size: 14px; margin-top: 5px;">Ceylon Express</h2>
                    <img src="assets/images/logo.jpg" alt="Ceylon Express Logo" style="max-width: 100px; max-height: 100px; margin-top: 5px;">

                <?php else: ?>
                    <h2>Ceylon Express</h2>
                    <p style="font-size: 10px; color: #666; margin: 5px 0;">Logo not found</p>
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
                <td class="label">INDIRIZZO (ADDRESS)</td>
                <td class="value"><?php echo $shipment['sender_address']; ?></td>
            </tr>
            <tr>
                <td class="label">CODICE FISCALE (TAX CODE)</td>
                <td class="value"><?php echo $shipment['sender_tax_code'] ?: ''; ?></td>
                <td class="label">DATA DI NASCITA (DATE OF BIRTH)</td>
                <td class="value"><?php echo $shipment['sender_dob'] ? date('d/m/Y', strtotime($shipment['sender_dob'])) : ''; ?></td>
                <td class="label">COMUNE DI NASCITA (BIRTH PLACE)</td>
                <td class="value"></td>
            </tr>
            <tr>
                <td class="label">SESSO (SEX)</td>
                <td class="value"><?php echo $shipment['sender_sex'] ?: ''; ?></td>
                <td colspan="4"></td>
            </tr>
        </table>
    </div>

    <!-- Receiver Details -->
    <div class="section">
        <div class="section-title">RECEIVER DETAILS</div>
        <table class="form-table">
            <tr>
                <td class="label">NAME</td>
                <td class="value" colspan="2"><?php echo $shipment['receiver_name']; ?></td>
                <td class="label">RECEIVERS ADDRESS</td>
                <td class="value"><?php echo $shipment['receiver_address']; ?></td>
                <td class="label">PHONE NO.</td>
                <td class="value"><?php echo $shipment['phone1'] . ($shipment['phone2'] ? ', ' . $shipment['phone2'] : ''); ?></td>
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
    <table class="form-table" style="margin-top: 20px;">
        <tr>
            <th>ITEMS</th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <td class="label">NO. OF BOXES</td>
            <td class="value"><?php echo $shipment['no_of_boxes']; ?></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- Pricing Table -->
    <table class="form-table" style="width: 50%; margin-left: auto; margin-top: 20px;">
        <tr>
            <td class="label">ACTUAL WEIGHT</td>
            <td class="value"><?php echo number_format($shipment['actual_weight'], 2); ?> kg</td>
        </tr>
        <tr>
            <td class="label">CHARGEABLE WEIGHT</td>
            <td class="value"><?php echo number_format($shipment['chargeable_weight'], 2); ?> kg</td>
        </tr>
        <tr>
            <td class="label">KG RATE</td>
            <td class="value">€ <?php echo number_format($shipment['rate_per_kg'], 2); ?></td>
        </tr>
        <tr>
            <td class="label">TOTAL</td>
            <td class="value">€ <?php echo number_format($shipment['freight_amount'], 2); ?></td>
        </tr>
        <tr>
            <td class="label">TAX</td>
            <td class="value">€ <?php echo number_format($shipment['tax_amount'], 2); ?></td>
        </tr>
        <tr>
            <td class="label" style="background-color: #e0e0e0;"><strong>TOTAL PAYABLE</strong></td>
            <td class="value" style="background-color: #e0e0e0;"><strong>€ <?php echo number_format($shipment['total_payable'], 2); ?></strong></td>
        </tr>
    </table>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div>SIGNATURE</div>
        </div>
        <div class="signature-box">
            <div>DATE & TIME</div>
            <div><?php echo date('d/m/Y H:i'); ?></div>
        </div>
        <div class="signature-box">
            <div>Ceylon Express</div>
        </div>
    </div>
    </div>
</body>

</html>