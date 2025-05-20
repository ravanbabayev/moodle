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
 * Merchant dashboard.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/accesslib.php');

// Import necessary classes
use core\exception\moodle_exception;
// We need to explicitly import the context_system class to fix the linter error
require_once($CFG->libdir . '/accesslib.php');

// Check plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    throw new moodle_exception('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Page setup
global $PAGE;
$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/lidio/merchant_dashboard.php');
$PAGE->set_title(get_string('merchantdashboard', 'local_lidio'));
$PAGE->set_heading(get_string('merchantdashboard', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/lidio/styles.css');

// Add JavaScript
$PAGE->requires->js('/local/lidio/scripts.js');

// Check if the user is a merchant
$merchant = local_lidio_require_merchant();

// Redirect to application page if not approved
if ($merchant->status !== 'approved') {
    redirect($CFG->wwwroot . '/local/lidio/merchant_application.php', 
        get_string('merchantstatusnotapproved', 'local_lidio'), 
        null, 
        \core\output\notification::NOTIFY_WARNING);
}

// Prepare template context
$templatecontext = [
    'merchant' => $merchant,
    'dashboard_url' => $CFG->wwwroot . '/local/lidio/merchant_dashboard.php',
    'transactions_url' => $CFG->wwwroot . '/local/lidio/transactions.php',
    'kyc_url' => $CFG->wwwroot . '/local/lidio/kyc.php',
    'application_url' => $CFG->wwwroot . '/local/lidio/merchant_application.php?edit=1',
    
    // Admin capabilities
    'is_admin' => has_capability('local/lidio:managemerchants', $context),
    'admin_url' => $CFG->wwwroot . '/local/lidio/admin/merchants.php',
    'strings' => [
        // Merchant info
        'merchantinfo' => get_string('merchantinfo', 'local_lidio'),
        'dashboardstats' => get_string('dashboardstats', 'local_lidio'),
        'nodatayet' => get_string('nodatayet', 'local_lidio'),
        'status' => get_string('status', 'local_lidio'),
        'kycstatus' => get_string('kycstatus', 'local_lidio'),
        
        // Dashboard strings
        'welcome' => get_string('welcome', 'local_lidio'),
        'navigation' => get_string('navigation', 'local_lidio'),
        'dashboard' => get_string('dashboard', 'local_lidio'),
        'transactions' => get_string('transactions', 'local_lidio'),
        'settings' => get_string('settings'),
        'help' => get_string('help', 'local_lidio'),
        'merchantaccountstatus' => get_string('merchantaccountstatus', 'local_lidio'),
        'totaltransactions' => get_string('totaltransactions', 'local_lidio'),
        'totalearnings' => get_string('totalearnings', 'local_lidio'),
        'pendingpayments' => get_string('pendingpayments', 'local_lidio'),
        'transactionhistory' => get_string('transactionhistory', 'local_lidio'),
        'norecords' => get_string('norecords', 'local_lidio'),
        'merchantstatus_approved' => get_string('merchantstatus_approved', 'local_lidio'),
        'merchantdashboard' => get_string('merchantdashboard', 'local_lidio'),
        'updateapplication' => get_string('updateapplication', 'local_lidio'),
        'withdraw' => get_string('withdraw', 'local_lidio'),
        'viewall' => get_string('viewall', 'local_lidio'),
        'refresh' => get_string('refresh', 'local_lidio')
    ]
];

// Get merchant statistics
$stats = new stdClass();

// Total transactions
$stats->total_transactions = $DB->count_records('local_lidio_transactions', ['merchant_id' => $merchant->id]);

// Total earnings
$sql = "SELECT SUM(amount) as total FROM {local_lidio_transactions} 
        WHERE merchant_id = :merchant_id AND status = 'completed'";
$total_earnings = $DB->get_field_sql($sql, ['merchant_id' => $merchant->id]);
$stats->total_earnings = $total_earnings ? number_format($total_earnings, 2) : '0.00';

// Pending payments
$stats->pending_payments = $DB->count_records('local_lidio_transactions', 
    ['merchant_id' => $merchant->id, 'status' => 'pending']);

// Add stats to template context
$templatecontext['stats'] = $stats;

// Add merchant username for link generation
$templatecontext['merchant']->username = 'merhaba';

// Get recent transactions (last 5)
$transactions = $DB->get_records('local_lidio_transactions', 
    ['merchant_id' => $merchant->id], 
    'timecreated DESC', 
    '*', 
    0, 
    5);

// Format transactions for display
$formatted_transactions = [];
if ($transactions) {
    foreach ($transactions as $transaction) {
        $formatted_transactions[] = [
            'id' => $transaction->id,
            'reference' => $transaction->reference,
            'amount' => number_format($transaction->amount, 2) . ' ₺',
            'status' => $transaction->status,
            'date' => userdate($transaction->timecreated, get_string('strftimedatetime', 'core_langconfig')),
            'status_class' => 'status-' . $transaction->status
        ];
    }
}

$templatecontext['transactions'] = $formatted_transactions;
$templatecontext['has_transactions'] = !empty($formatted_transactions);

// Start output
echo $OUTPUT->header();

// Display the dashboard
echo $OUTPUT->render_from_template('local_lidio/merchant_dashboard', $templatecontext);

// End output
echo $OUTPUT->footer(); 