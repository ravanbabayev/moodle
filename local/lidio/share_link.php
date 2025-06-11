<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check login
require_login();

// Get current user
$userid = $USER->id;

// Get merchant data
$merchant = $DB->get_record('local_lidio_merchants', array('userid' => $userid));

// Check if the user is a merchant
if (!$merchant) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$link_id = intval($input['link_id'] ?? 0);
$recipient = trim($input['recipient'] ?? '');
$method = trim($input['method'] ?? '');
$message = trim($input['message'] ?? '');

// Validate input
if (!$link_id || !$recipient) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get payment link and verify it belongs to the merchant
$payment_link = $DB->get_record('local_lidio_payment_links', 
    array('id' => $link_id, 'merchantid' => $merchant->id));

if (!$payment_link) {
    echo json_encode(['success' => false, 'message' => 'Payment link not found']);
    exit;
}

// Auto-detect method if not specified
if (empty($method)) {
    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $method = 'email';
    } elseif (preg_match('/^[+]?[0-9\s\-\(\)]+$/', $recipient)) {
        $method = 'sms';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid recipient format']);
        exit;
    }
}

// Create payment link URL
$payment_url = new moodle_url('/local/lidio/pay.php', array('code' => $payment_link->link_code));

// Prepare message content
$default_message = "Hi! Please use this link to make your payment: " . $payment_url->out();
$full_message = !empty($message) ? $message . "\n\n" . $payment_url->out() : $default_message;

// Simulate sending (in real implementation, you would integrate with SMS/Email services)
$success = false;
$response_data = '';

try {
    if ($method === 'email') {
        // Simulate email sending
        $subject = "Payment Link from " . $merchant->company_name;
        
        // In real implementation, you would use Moodle's email_to_user() or external email service
        // For now, we'll just simulate success
        $success = true;
        $response_data = 'Email sent to ' . $recipient;
        
        // You could use Moodle's built-in email system:
        /*
        $user = new stdClass();
        $user->email = $recipient;
        $user->firstname = 'Customer';
        $user->lastname = '';
        $user->maildisplay = 1;
        $user->mailformat = 1;
        $user->id = -1;
        
        $from = $USER;
        $success = email_to_user($user, $from, $subject, $full_message);
        */
        
    } elseif ($method === 'sms') {
        // Simulate SMS sending
        // In real implementation, you would integrate with SMS service like Twilio, etc.
        $success = true;
        $response_data = 'SMS sent to ' . $recipient;
        
        // Example integration with SMS service:
        /*
        $sms_service = new SmsService();
        $result = $sms_service->send($recipient, $full_message);
        $success = $result['success'];
        $response_data = $result['message'];
        */
    }
} catch (Exception $e) {
    $success = false;
    $response_data = 'Error: ' . $e->getMessage();
}

// Record the sharing attempt
$share_record = new stdClass();
$share_record->payment_link_id = $link_id;
$share_record->merchant_id = $merchant->id;
$share_record->recipient = $recipient;
$share_record->method = $method;
$share_record->status = $success ? 'sent' : 'failed';
$share_record->message_content = $full_message;
$share_record->response_data = $response_data;
$share_record->timecreated = time();

$DB->insert_record('local_lidio_link_shares', $share_record);

// Return response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Link shared successfully!' : $response_data
]);
?> 