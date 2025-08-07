
<?php
// ajax_handlers.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'get_sender':
        $mobile = sanitize($_POST['mobile']);
        
        $stmt = $pdo->prepare("SELECT * FROM senders WHERE mobile = ?");
        $stmt->execute([$mobile]);
        $sender = $stmt->fetch();
        
        if ($sender) {
            echo json_encode([
                'success' => true,
                'sender' => $sender
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Sender not found'
            ]);
        }
        break;
        
    case 'get_receivers':
        $mobile = sanitize($_POST['mobile']);
        
        $stmt = $pdo->prepare("SELECT * FROM receivers WHERE sender_mobile = ?");
        $stmt->execute([$mobile]);
        $receivers = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'receivers' => $receivers
        ]);
        break;
        
    case 'check_tracking':
        $tracking_no = sanitize($_POST['tracking_no']);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shipments WHERE tracking_no = ?");
        $stmt->execute([$tracking_no]);
        $exists = $stmt->fetchColumn() > 0;
        
        echo json_encode([
            'exists' => $exists,
            'message' => $exists ? 'Tracking number already exists' : 'Tracking number available'
        ]);
        break;
        
    case 'get_sender_admin':
        $sender_id = (int)$_POST['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM senders WHERE id = ?");
        $stmt->execute([$sender_id]);
        $sender = $stmt->fetch();
        
        if ($sender) {
            echo json_encode([
                'success' => true,
                'sender' => $sender
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Sender not found'
            ]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>