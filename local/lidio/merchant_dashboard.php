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
$pending_transactions = $DB->count_records('local_lidio_transactions', array('merchant_id' => $merchant->id, 'status' => 'pending'));

// Get payment links count
$payment_links = $DB->count_records('local_lidio_payment_links', array('merchantid' => $merchant->id));
$active_payment_links = $DB->count_records('local_lidio_payment_links', array('merchantid' => $merchant->id, 'status' => 'active'));

// Calculate total earnings (gross amount)
$sql = "SELECT SUM(amount) as total FROM {local_lidio_transactions} 
        WHERE merchant_id = :merchantid AND status = 'completed'";
$params = array('merchantid' => $merchant->id);
$total_earnings = $DB->get_field_sql($sql, $params);
$total_earnings = $total_earnings ? $total_earnings : 0;

// Calculate commissions paid
$commission_rate = isset($merchant->commission_rate) ? $merchant->commission_rate : 2.5;
$commissions_paid = $total_earnings * ($commission_rate / 100);

// Calculate net earnings (total minus commissions)
$net_earnings = $total_earnings - $commissions_paid;

// Get current balance
$current_balance = isset($merchant->balance) ? $merchant->balance : 0;

// Calculate growth rates
$previous_month_sql = "SELECT SUM(amount) as total FROM {local_lidio_transactions} 
        WHERE merchant_id = :merchantid AND status = 'completed'
        AND timecreated BETWEEN :start_time AND :end_time";
        
$current_month_start = strtotime(date('Y-m-01 00:00:00'));
$previous_month_start = strtotime('-1 month', $current_month_start);
$previous_month_end = $current_month_start - 1;

$params = array(
    'merchantid' => $merchant->id, 
    'start_time' => $previous_month_start, 
    'end_time' => $previous_month_end
);

$previous_month_earnings = $DB->get_field_sql($previous_month_sql, $params) ?: 0;

$params = array(
    'merchantid' => $merchant->id, 
    'start_time' => $current_month_start, 
    'end_time' => time()
);

$current_month_earnings = $DB->get_field_sql($previous_month_sql, $params) ?: 0;

// Calculate month-over-month growth percentage
$monthly_growth_percentage = 0;
if ($previous_month_earnings > 0) {
    $monthly_growth_percentage = (($current_month_earnings - $previous_month_earnings) / $previous_month_earnings) * 100;
}

// Get last 5 transactions
$sql = "SELECT t.*, p.title as payment_link_title 
        FROM {local_lidio_transactions} t
        LEFT JOIN {local_lidio_payment_links} p ON t.payment_link_id = p.id
        WHERE t.merchant_id = :merchantid 
        ORDER BY t.timecreated DESC LIMIT 5";
$recent_transactions = $DB->get_records_sql($sql, array('merchantid' => $merchant->id));

// Format recent transactions for display
$formatted_transactions = array();
foreach ($recent_transactions as $transaction) {
    $formatted_transactions[] = array(
        'id' => $transaction->id,
        'amount' => number_format($transaction->amount, 2),
        'currency' => $transaction->currency,
        'customer_name' => $transaction->customer_name,
        'status' => $transaction->status,
        'date' => userdate($transaction->timecreated, get_string('strftimedatetime', 'langconfig')),
        'payment_method' => $transaction->payment_method,
        'status_class' => $transaction->status === 'completed' ? 'text-green-500' : 
                          ($transaction->status === 'failed' ? 'text-red-500' : 'text-yellow-500')
    );
}

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
        'settlement_period' => $merchant->settlement_period,
        'balance' => number_format($current_balance, 2),
        'balance_raw' => $current_balance
    ],
    'stats' => [
        'total_earnings' => number_format($total_earnings, 2),
        'total_earnings_raw' => $total_earnings,
        'commissions_paid' => number_format($commissions_paid, 2),
        'net_earnings' => number_format($net_earnings, 2),
        'total_transactions' => $total_transactions,
        'successful_transactions' => $successful_transactions,
        'failed_transactions' => $failed_transactions,
        'pending_transactions' => $pending_transactions,
        'payment_links' => $payment_links,
        'active_payment_links' => $active_payment_links,
        'success_rate' => $total_transactions > 0 ? round(($successful_transactions / $total_transactions) * 100) : 0,
        'monthly_growth' => round($monthly_growth_percentage, 1),
        'monthly_growth_positive' => $monthly_growth_percentage >= 0,
        'current_month_earnings' => number_format($current_month_earnings, 2)
    ],
    'recent_transactions' => $formatted_transactions,
    'has_transactions' => !empty($formatted_transactions),
    'strings' => [
        'welcome' => get_string('welcome', 'local_lidio'),
        'earnings' => get_string('totalearnings', 'local_lidio'),
        'withdraw' => get_string('withdraw', 'local_lidio'),
        'totalrevenue' => get_string('totalrevenue', 'local_lidio'),
        'netearnings' => get_string('netearnings', 'local_lidio'),
        'commissionspaid' => get_string('commissionspaid', 'local_lidio'),
        'currentbalance' => get_string('currentbalance', 'local_lidio'),
        'totalproducts' => get_string('totalproducts', 'local_lidio'),
        'totalsales' => get_string('totalsales', 'local_lidio'),
        'pendingtransactions' => get_string('pendingtransactions', 'local_lidio'),
        'failedtransactions' => get_string('failedtransactions', 'local_lidio'),
        'recenttransactions' => get_string('recenttransactions', 'local_lidio'),
        'viewalltransactions' => get_string('viewalltransactions', 'local_lidio'),
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
    'admin_url' => new moodle_url('/local/lidio/admin.php'),
    'create_payment_link_url' => new moodle_url('/local/lidio/create_payment_link.php'),
    'withdraw_url' => new moodle_url('/local/lidio/withdraw.php')
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