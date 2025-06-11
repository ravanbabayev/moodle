<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check login
require_login();

// Get current user
$userid = $USER->id;

// Set up the page
$title = get_string('merchantdashboard', 'local_lidio');
$url = new moodle_url('/local/lidio/merchant_dashboard.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add TailwindCSS
$PAGE->requires->css(new moodle_url('/local/lidio/styles.css'));

$PAGE->requires->js('/local/lidio/scripts.js');

// Get merchant data
$merchant = $DB->get_record('local_lidio_merchants', array('userid' => $userid));

// Check if the user is a merchant
if (!$merchant) {
    redirect(
        new moodle_url('/local/lidio/merchant_application.php'),
        get_string('notamerchant', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Get transaction counts
$total_transactions = $DB->count_records('local_lidio_transactions', array('merchant_id' => $merchant->id));
$successful_transactions = $DB->count_records('local_lidio_transactions', array('merchant_id' => $merchant->id, 'status' => 'completed'));
$failed_transactions = $DB->count_records('local_lidio_transactions', array('merchant_id' => $merchant->id, 'status' => 'failed'));

// Get payment links count
$payment_links = $DB->count_records('local_lidio_payment_links', array('merchantid' => $merchant->id));
$active_payment_links = $DB->count_records('local_lidio_payment_links', array('merchantid' => $merchant->id, 'status' => 'active'));

// Calculate total earnings
$sql = "SELECT SUM(amount) as total FROM {local_lidio_transactions} 
        WHERE merchant_id = :merchantid AND status = 'completed'";
$params = array('merchantid' => $merchant->id);
$total_earnings = $DB->get_field_sql($sql, $params);
$total_earnings = $total_earnings ? $total_earnings : 0;

// Get last 5 transactions
$sql = "SELECT t.*, p.title as payment_link_title 
        FROM {local_lidio_transactions} t
        LEFT JOIN {local_lidio_payment_links} p ON t.payment_link_id = p.id
        WHERE t.merchant_id = :merchantid 
        ORDER BY t.timecreated DESC LIMIT 5";
$recent_transactions = $DB->get_records_sql($sql, array('merchantid' => $merchant->id));

// Get chart data for the last 10 days
$days = 10;
$labels = array();
$sales_data = array();
$labels_js = array();

for ($i = $days - 1; $i >= 0; $i--) {
    $date = time() - ($i * 24 * 60 * 60);
    $labels[] = date('j M', $date);
    $labels_js[] = "'" . date('j M', $date) . "'";
    
    $start_of_day = strtotime(date('Y-m-d 00:00:00', $date));
    $end_of_day = strtotime(date('Y-m-d 23:59:59', $date));
    
    $sql = "SELECT SUM(amount) as daily_total FROM {local_lidio_transactions} 
            WHERE merchant_id = :merchantid AND status = 'completed'
            AND timecreated BETWEEN :start_time AND :end_time";
    $params = array('merchantid' => $merchant->id, 'start_time' => $start_of_day, 'end_time' => $end_of_day);
    $daily_total = $DB->get_field_sql($sql, $params);
    $sales_data[] = $daily_total ? $daily_total : 0;
}

// Prepare template data
$templatecontext = [
    'merchant' => [
        'company_name' => $merchant->company_name,
        'username' => $USER->username,
        'status' => $merchant->status,
        'kyc_status' => $merchant->kyc_status,
        'commission_rate' => $merchant->commission_rate,
        'settlement_period' => $merchant->settlement_period
    ],
    'stats' => [
        'total_earnings' => number_format($total_earnings, 2),
        'total_transactions' => $total_transactions,
        'successful_transactions' => $successful_transactions,
        'failed_transactions' => $failed_transactions,
        'payment_links' => $payment_links,
        'active_payment_links' => $active_payment_links,
        'success_rate' => $total_transactions > 0 ? round(($successful_transactions / $total_transactions) * 100) : 0
    ],
    'strings' => [
        'welcome' => get_string('welcome', 'local_lidio'),
        'earnings' => get_string('totalearnings', 'local_lidio'),
        'withdraw' => get_string('withdraw', 'local_lidio'),
        'totalrevenue' => get_string('totalrevenue', 'local_lidio'),
        'totalproducts' => get_string('totalproducts', 'local_lidio'),
        'totalsales' => get_string('totalsales', 'local_lidio'),
        'totalcustomers' => get_string('totalcustomers', 'local_lidio'),
        'salessummary' => get_string('salessummary', 'local_lidio'),
        'customeracquisition' => get_string('customeracquisition', 'local_lidio'),
        'eachmonth' => get_string('eachmonth', 'local_lidio'),
        'newcustomerthisyear' => get_string('newcustomerthisyear', 'local_lidio'),
        'salesfunnel' => get_string('salesfunnel', 'local_lidio'),
        'visit' => get_string('visit', 'local_lidio'),
        'click' => get_string('click', 'local_lidio'),
        'purchased' => get_string('purchased', 'local_lidio'),
        'topproduct' => get_string('topproduct', 'local_lidio'),
        'showall' => get_string('showall', 'local_lidio'),
        'totalsold' => get_string('totalsold', 'local_lidio'),
        'viewsandclick' => get_string('viewsandclick', 'local_lidio'),
        'averageviews' => get_string('averageviews', 'local_lidio'),
        'averageclick' => get_string('averageclick', 'local_lidio'),
        'agerange' => get_string('agerange', 'local_lidio'),
        'youngadults' => get_string('youngadults', 'local_lidio'),
        'ofcustomers' => get_string('ofcustomers', 'local_lidio')
    ],
    'transactions_url' => new moodle_url('/local/lidio/transactions.php'),
    'is_admin' => has_capability('local/lidio:manage', context_system::instance()),
    'admin_url' => new moodle_url('/local/lidio/admin.php')
];

// Display the page
echo $OUTPUT->header();

// If merchant status is not approved, show warning
if ($merchant->status !== 'approved') {
    echo $OUTPUT->notification(get_string('merchantstatusnotapproved', 'local_lidio'), 'warning');
}

// If KYC status is not approved, show KYC verification link
if ($merchant->kyc_status !== 'approved') {
    echo $OUTPUT->notification(
        get_string('completekycverification', 'local_lidio') . ' <a href="' . new moodle_url('/local/lidio/kyc.php') . '">' . get_string('kycverification', 'local_lidio') . '</a>', 
        'info'
    );
}

// Render the template
echo $OUTPUT->render_from_template('local_lidio/merchant_dashboard', $templatecontext);

echo $OUTPUT->footer(); 