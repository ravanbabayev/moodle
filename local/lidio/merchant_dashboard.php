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

// Load Chart.js
$PAGE->requires->js_amd_inline("
    require(['jquery', 'https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js'], function($, Chart) {
        var ctx = document.getElementById('salesChart').getContext('2d');
        var salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [{$labels_js}],
                datasets: [{
                    label: 'Sales',
                    data: [{$sales_data}],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
");

// Create Tailwind CSS styled dashboard
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <!-- Welcome Section -->
    <div class="mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6">
            <h1 class="text-2xl font-bold text-white"><?php echo get_string('welcome', 'local_lidio'); ?>, <?php echo fullname($USER); ?>!</h1>
            <p class="text-blue-100 mt-2">Manage your payments and track your business growth from this dashboard.</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <div class="flex flex-wrap gap-3">
            <a href="<?php echo new moodle_url('/local/lidio/transactions.php'); ?>" class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                <i class="fas fa-exchange-alt mr-3"></i><?php echo get_string('transactions', 'local_lidio'); ?>
            </a>
            <a href="<?php echo new moodle_url('/local/lidio/payment_links.php'); ?>" class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-md text-base font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                <i class="fas fa-link mr-3"></i><?php echo get_string('paymentlinks', 'local_lidio'); ?>
            </a>
            <?php if ($merchant->kyc_status !== 'approved'): ?>
            <a href="<?php echo new moodle_url('/local/lidio/kyc.php'); ?>" class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-md text-base font-medium text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all duration-200">
                <i class="fas fa-id-card mr-3"></i><?php echo get_string('kycverification', 'local_lidio'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden rounded-xl shadow-lg transform transition duration-300 hover:scale-105">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-blue-500 p-3">
                        <i class="fas fa-money-bill-wave text-xl text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php echo get_string('totalearnings', 'local_lidio'); ?></dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_earnings, 2); ?></div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-blue-50 px-5 py-3">
                <div class="text-sm">
                    <a href="<?php echo new moodle_url('/local/lidio/transactions.php'); ?>" class="font-medium text-blue-700 hover:text-blue-900">View earnings <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden rounded-xl shadow-lg transform transition duration-300 hover:scale-105">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-indigo-500 p-3">
                        <i class="fas fa-chart-line text-xl text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php echo get_string('totaltransactions', 'local_lidio'); ?></dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $total_transactions; ?></div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-indigo-50 px-5 py-3">
                <div class="text-sm">
                    <a href="<?php echo new moodle_url('/local/lidio/transactions.php'); ?>" class="font-medium text-indigo-700 hover:text-indigo-900">View transactions <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden rounded-xl shadow-lg transform transition duration-300 hover:scale-105">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-green-500 p-3">
                        <i class="fas fa-check-circle text-xl text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php echo get_string('successfultransactions', 'local_lidio'); ?></dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $successful_transactions; ?></div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <?php if ($total_transactions > 0): ?>
                                    <span class="text-xs"><?php echo round(($successful_transactions / $total_transactions) * 100); ?>%</span>
                                    <?php endif; ?>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 px-5 py-3">
                <div class="text-sm">
                    <a href="<?php echo new moodle_url('/local/lidio/transactions.php'); ?>" class="font-medium text-green-700 hover:text-green-900">View successful <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden rounded-xl shadow-lg transform transition duration-300 hover:scale-105">
            <div class="px-6 py-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 rounded-md bg-amber-500 p-3">
                        <i class="fas fa-link text-xl text-white"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?php echo get_string('paymentlinks', 'local_lidio'); ?></dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $payment_links; ?></div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-amber-600">
                                    <span class="text-xs"><?php echo $active_payment_links; ?> active</span>
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-amber-50 px-5 py-3">
                <div class="text-sm">
                    <a href="<?php echo new moodle_url('/local/lidio/payment_links.php'); ?>" class="font-medium text-amber-700 hover:text-amber-900">Manage links <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Sales Chart -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium leading-6 text-gray-900"><?php echo get_string('salessummary', 'local_lidio'); ?></h3>
                    <div class="flex -space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Last 10 days
                        </span>
                    </div>
                </div>
            </div>
            <div class="px-5 py-6">
                <div class="h-80">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between text-sm">
                    <div class="font-medium text-gray-500">Total for period: <?php echo number_format(array_sum($sales_data), 2); ?></div>
                    <div class="flex items-center text-indigo-600 font-medium">
                        <span>View detailed report</span>
                        <i class="fas fa-chart-bar ml-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Merchant Info -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-5">
                <h3 class="text-lg font-medium leading-6 text-gray-900"><?php echo get_string('merchantinfo', 'local_lidio'); ?></h3>
            </div>
            <div class="px-6 py-6">
                <div class="flex items-center justify-center mb-5">
                    <div class="h-24 w-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold">
                        <?php echo strtoupper(substr($merchant->company_name, 0, 1)); ?>
                    </div>
                </div>
                <div class="text-center mb-6">
                    <h4 class="text-xl font-bold text-gray-900"><?php echo $merchant->company_name; ?></h4>
                    <p class="text-gray-500 text-sm">Merchant ID: <?php echo str_pad($merchant->id, 8, '0', STR_PAD_LEFT); ?></p>
                </div>
                <dl>
                    <div class="py-3 sm:grid sm:grid-cols-2 sm:gap-4 border-t border-gray-200">
                        <dt class="text-sm font-medium text-gray-500"><?php echo get_string('status', 'local_lidio'); ?></dt>
                        <dd class="mt-1 text-sm sm:mt-0 text-right">
                            <?php 
                            $status_class = '';
                            switch ($merchant->status) {
                                case 'approved':
                                    $status_class = 'bg-green-100 text-green-800';
                                    break;
                                case 'rejected':
                                    $status_class = 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                            }
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                <?php echo get_string($merchant->status, 'local_lidio'); ?>
                            </span>
                        </dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-2 sm:gap-4 border-t border-gray-200">
                        <dt class="text-sm font-medium text-gray-500"><?php echo get_string('kycstatus', 'local_lidio'); ?></dt>
                        <dd class="mt-1 text-sm sm:mt-0 text-right">
                            <?php 
                            $kyc_status_class = '';
                            switch ($merchant->kyc_status) {
                                case 'approved':
                                    $kyc_status_class = 'bg-green-100 text-green-800';
                                    break;
                                case 'rejected':
                                    $kyc_status_class = 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    $kyc_status_class = 'bg-yellow-100 text-yellow-800';
                            }
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $kyc_status_class; ?>">
                                <?php echo get_string($merchant->kyc_status, 'local_lidio'); ?>
                            </span>
                        </dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-2 sm:gap-4 border-t border-gray-200">
                        <dt class="text-sm font-medium text-gray-500"><?php echo get_string('commissionrate', 'local_lidio'); ?></dt>
                        <dd class="mt-1 text-sm font-medium text-blue-600 sm:mt-0 text-right"><?php echo $merchant->commission_rate; ?>%</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-2 sm:gap-4 border-t border-gray-200">
                        <dt class="text-sm font-medium text-gray-500"><?php echo get_string('settlementperiod', 'local_lidio'); ?></dt>
                        <dd class="mt-1 text-sm font-medium text-blue-600 sm:mt-0 text-right"><?php echo $merchant->settlement_period; ?> <?php echo get_string('days', 'local_lidio'); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="border-b border-gray-200 px-6 py-5 flex justify-between items-center">
            <h3 class="text-lg font-medium leading-6 text-gray-900"><?php echo get_string('recenttransactions', 'local_lidio'); ?></h3>
            <a href="<?php echo new moodle_url('/local/lidio/transactions.php'); ?>" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <?php echo get_string('viewall', 'local_lidio'); ?>
            </a>
        </div>
        <?php if (!empty($recent_transactions)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('reference', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('date', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('amount', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('status', 'local_lidio'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_transactions as $transaction): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo $transaction->reference; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $transaction->payment_link_title; ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500"><?php echo userdate($transaction->timecreated, get_string('strftimedatetime', 'langconfig')); ?></div>
                            <div class="text-xs text-gray-400"><?php echo human_time_diff($transaction->timecreated); ?> ago</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo number_format($transaction->amount, 2) . ' ' . $transaction->currency; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $status_class = '';
                            $status_icon = '';
                            switch ($transaction->status) {
                                case 'completed':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_icon = '<i class="fas fa-check-circle mr-1"></i>';
                                    break;
                                case 'failed':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_icon = '<i class="fas fa-times-circle mr-1"></i>';
                                    break;
                                case 'pending':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_icon = '<i class="fas fa-clock mr-1"></i>';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                            }
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                <?php echo $status_icon . get_string($transaction->status, 'local_lidio'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="py-12 text-center">
            <div class="inline-block p-6 rounded-full bg-gray-100 text-gray-500 mb-4">
                <i class="fas fa-receipt text-4xl"></i>
            </div>
            <p class="text-gray-500 mb-4"><?php echo get_string('notransactions', 'local_lidio'); ?></p>
            <a href="<?php echo new moodle_url('/local/lidio/payment_links.php'); ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-plus-circle mr-2"></i> Create payment link
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Additional Resources -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white bg-opacity-30 rounded-lg p-3">
                        <i class="fas fa-book text-xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold">Documentation</h3>
                        <p class="text-green-100 text-sm mt-1">Learn how to use all features</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="#" class="inline-flex items-center px-3 py-1.5 border border-white border-opacity-40 rounded-md text-sm font-medium text-white hover:bg-white hover:bg-opacity-10">
                        View documentation <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white bg-opacity-30 rounded-lg p-3">
                        <i class="fas fa-headset text-xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold">Support</h3>
                        <p class="text-purple-100 text-sm mt-1">Get help when you need it</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="#" class="inline-flex items-center px-3 py-1.5 border border-white border-opacity-40 rounded-md text-sm font-medium text-white hover:bg-white hover:bg-opacity-10">
                        Contact support <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white bg-opacity-30 rounded-lg p-3">
                        <i class="fas fa-star text-xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold">Upgrades</h3>
                        <p class="text-amber-100 text-sm mt-1">Explore premium features</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="#" class="inline-flex items-center px-3 py-1.5 border border-white border-opacity-40 rounded-md text-sm font-medium text-white hover:bg-white hover:bg-opacity-10">
                        View plans <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Helper function for human readable time
function human_time_diff(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - timestamp;
    
    if (diff < 60) {
        return 'just now';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return minutes + ' minute' + (minutes > 1 ? 's' : '');
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return hours + ' hour' + (hours > 1 ? 's' : '');
    } else if (diff < 604800) {
        const days = Math.floor(diff / 86400);
        return days + ' day' + (days > 1 ? 's' : '');
    } else {
        const weeks = Math.floor(diff / 604800);
        return weeks + ' week' + (weeks > 1 ? 's' : '');
    }
}
</script>

<?php
echo $OUTPUT->footer(); 