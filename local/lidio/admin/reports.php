<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin page for Lidio reports and analytics.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
use core\output\html_writer;
use moodle_url;

// Check access
admin_externalpage_setup('local_lidio_reports');

// Set up the page
$title = get_string('reports', 'local_lidio');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add JavaScript for chart.js
$PAGE->requires->js_call_amd('local_lidio/chart', 'init');

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Get statistics
$total_merchants = $DB->count_records('local_lidio_merchants');
$active_merchants = $DB->count_records('local_lidio_merchants', ['status' => 'approved']);
$total_transactions = $DB->count_records('local_lidio_transactions');
$successful_transactions = $DB->count_records('local_lidio_transactions', ['status' => 'completed']);
$failed_transactions = $DB->count_records('local_lidio_transactions', ['status' => 'failed']);
$pending_transactions = $DB->count_records('local_lidio_transactions', ['status' => 'pending']);

// Get total revenue
$total_revenue = $DB->get_field_sql("SELECT SUM(amount) FROM {local_lidio_transactions} WHERE status = 'completed'") ?: 0;

// Calculate total commissions
$total_commissions = 0;
$transactions = $DB->get_records_sql("
    SELECT t.amount, m.commission_rate 
    FROM {local_lidio_transactions} t
    JOIN {local_lidio_merchants} m ON t.merchant_id = m.id
    WHERE t.status = 'completed'
");

foreach ($transactions as $transaction) {
    $commission_rate = isset($transaction->commission_rate) ? $transaction->commission_rate : 2.5;
    $total_commissions += $transaction->amount * ($commission_rate / 100);
}

// Get transaction count by payment method
$payment_methods = $DB->get_records_sql("
    SELECT payment_method, COUNT(*) as count
    FROM {local_lidio_transactions}
    WHERE status = 'completed'
    GROUP BY payment_method
");

// Dashboard cards
echo '<div class="row mb-4">';

// Total Revenue card
echo '<div class="col-md-3">';
echo '<div class="card text-white bg-primary h-100">';
echo '<div class="card-header">Total Revenue</div>';
echo '<div class="card-body">';
echo '<h4 class="mb-0">' . number_format($total_revenue, 2) . ' TL</h4>';
echo '</div>';
echo '</div>';
echo '</div>';

// Active Merchants card
echo '<div class="col-md-3">';
echo '<div class="card text-white bg-success h-100">';
echo '<div class="card-header">Active Merchants</div>';
echo '<div class="card-body">';
echo '<h4 class="mb-0">' . $active_merchants . ' / ' . $total_merchants . '</h4>';
echo '</div>';
echo '</div>';
echo '</div>';

// Successful Transactions card
echo '<div class="col-md-3">';
echo '<div class="card text-white bg-info h-100">';
echo '<div class="card-header">Successful Transactions</div>';
echo '<div class="card-body">';
echo '<h4 class="mb-0">' . $successful_transactions . ' / ' . $total_transactions . '</h4>';
echo '</div>';
echo '</div>';
echo '</div>';

// Commission Revenue card
echo '<div class="col-md-3">';
echo '<div class="card text-white bg-warning h-100">';
echo '<div class="card-header">Commission Revenue</div>';
echo '<div class="card-body">';
echo '<h4 class="mb-0">' . number_format($total_commissions, 2) . ' TL</h4>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End row

// Transaction statistics row
echo '<div class="row mb-4">';

// Transaction Status Breakdown
echo '<div class="col-md-6">';
echo '<div class="card h-100">';
echo '<div class="card-header bg-secondary text-white">Transaction Status Breakdown</div>';
echo '<div class="card-body">';

// Create status chart container
echo '<canvas id="transaction-status-chart" width="400" height="200"></canvas>';

// Generate chart data
$status_labels = ['Completed', 'Failed', 'Pending'];
$status_data = [$successful_transactions, $failed_transactions, $pending_transactions];
$status_colors = ['#28a745', '#dc3545', '#ffc107'];

// JavaScript for status chart
$status_chart_js = "
    var ctx = document.getElementById('transaction-status-chart').getContext('2d');
    var statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: " . json_encode($status_labels) . ",
            datasets: [{
                data: " . json_encode($status_data) . ",
                backgroundColor: " . json_encode($status_colors) . "
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
";
$PAGE->requires->js_init_code($status_chart_js);

echo '</div>'; // End card-body
echo '</div>'; // End card
echo '</div>'; // End col

// Payment Method Breakdown
echo '<div class="col-md-6">';
echo '<div class="card h-100">';
echo '<div class="card-header bg-secondary text-white">Payment Method Breakdown</div>';
echo '<div class="card-body">';

// Create payment method chart container
echo '<canvas id="payment-method-chart" width="400" height="200"></canvas>';

// Generate chart data
$method_labels = [];
$method_data = [];
$method_colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
$color_index = 0;

foreach ($payment_methods as $method) {
    $method_labels[] = $method->payment_method;
    $method_data[] = $method->count;
    $color_index = ($color_index + 1) % count($method_colors);
}

// JavaScript for payment method chart
$method_chart_js = "
    var ctx = document.getElementById('payment-method-chart').getContext('2d');
    var methodChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: " . json_encode($method_labels) . ",
            datasets: [{
                data: " . json_encode($method_data) . ",
                backgroundColor: " . json_encode(array_slice($method_colors, 0, count($method_data))) . "
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
";
$PAGE->requires->js_init_code($method_chart_js);

echo '</div>'; // End card-body
echo '</div>'; // End card
echo '</div>'; // End col

echo '</div>'; // End row

// Monthly Revenue Trend
echo '<div class="row mb-4">';
echo '<div class="col-md-12">';
echo '<div class="card">';
echo '<div class="card-header bg-secondary text-white">Monthly Revenue Trend</div>';
echo '<div class="card-body">';

// Create monthly revenue chart container
echo '<canvas id="monthly-revenue-chart" width="400" height="200"></canvas>';

// Get monthly revenue data for the last 6 months
$monthly_revenue = [];
$month_labels = [];

for ($i = 5; $i >= 0; $i--) {
    $month_start = strtotime(date('Y-m-01', strtotime("-$i months")));
    $month_end = strtotime(date('Y-m-t', strtotime("-$i months"))) + 86399; // Last second of the day
    
    $month_labels[] = date('M Y', $month_start);
    
    $sql = "SELECT SUM(amount) as total 
            FROM {local_lidio_transactions} 
            WHERE status = 'completed' 
            AND timecreated BETWEEN :start_time AND :end_time";
    $params = ['start_time' => $month_start, 'end_time' => $month_end];
    
    $monthly_revenue[] = $DB->get_field_sql($sql, $params) ?: 0;
}

// JavaScript for monthly revenue chart
$monthly_chart_js = "
    var ctx = document.getElementById('monthly-revenue-chart').getContext('2d');
    var monthlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: " . json_encode($month_labels) . ",
            datasets: [{
                label: 'Monthly Revenue (TL)',
                data: " . json_encode($monthly_revenue) . ",
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: 'rgba(78, 115, 223, 1)',
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
";
$PAGE->requires->js_init_code($monthly_chart_js);

echo '</div>'; // End card-body
echo '</div>'; // End card
echo '</div>'; // End col
echo '</div>'; // End row

// Top Merchants by Revenue
echo '<div class="row mb-4">';
echo '<div class="col-md-12">';
echo '<div class="card">';
echo '<div class="card-header bg-secondary text-white">Top Merchants by Revenue</div>';
echo '<div class="card-body">';

// Get top 5 merchants by revenue
$top_merchants = $DB->get_records_sql("
    SELECT m.id, m.company_name, SUM(t.amount) as total_revenue, COUNT(t.id) as transaction_count
    FROM {local_lidio_merchants} m
    JOIN {local_lidio_transactions} t ON m.id = t.merchant_id
    WHERE t.status = 'completed'
    GROUP BY m.id, m.company_name
    ORDER BY total_revenue DESC
    LIMIT 5
");

if (!empty($top_merchants)) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Merchant</th>';
    echo '<th>Total Revenue</th>';
    echo '<th>Transaction Count</th>';
    echo '<th>Average Transaction</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($top_merchants as $merchant) {
        $avg_transaction = $merchant->transaction_count > 0 ? 
            number_format($merchant->total_revenue / $merchant->transaction_count, 2) : 0;
            
        echo '<tr>';
        echo '<td>';
        echo html_writer::link(
            new moodle_url('/local/lidio/admin/view_merchant.php', ['id' => $merchant->id]),
            $merchant->company_name
        );
        echo '</td>';
        echo '<td>' . number_format($merchant->total_revenue, 2) . ' TL</td>';
        echo '<td>' . $merchant->transaction_count . '</td>';
        echo '<td>' . $avg_transaction . ' TL</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // End table-responsive
} else {
    echo '<p class="alert alert-info">No transaction data available yet.</p>';
}

echo '</div>'; // End card-body
echo '</div>'; // End card
echo '</div>'; // End col
echo '</div>'; // End row

// Add link to transaction list
echo '<div class="text-center mb-4">';
echo html_writer::link(
    new moodle_url('/local/lidio/admin/transactions.php'),
    get_string('viewalltransactions', 'local_lidio'),
    ['class' => 'btn btn-primary']
);
echo '</div>';

echo $OUTPUT->footer(); 