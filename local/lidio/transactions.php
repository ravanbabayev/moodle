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
 * Merchant transactions page.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/moodlelib.php');

// Check plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    throw new \core\exception\moodle_exception('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Page setup
global $PAGE, $USER, $DB, $CFG, $OUTPUT;
$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/lidio/transactions.php');
$PAGE->set_title(get_string('transactions', 'local_lidio'));
$PAGE->set_heading(get_string('transactions', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/lidio/styles.css');

// Add JavaScript
$PAGE->requires->js('/local/lidio/scripts.js');

// Check if the user is already a merchant
$merchant = local_lidio_is_merchant();

// If not a merchant, redirect to merchant application
if (!$merchant) {
    redirect(
        $CFG->wwwroot . '/local/lidio/merchant.php',
        get_string('notamerchant', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// If merchant account is not approved, redirect to pending page
if ($merchant->status !== 'approved') {
    redirect(
        $CFG->wwwroot . '/local/lidio/merchant.php?ismerchant=1',
        get_string('merchantnotapproved', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Get pagination parameters
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10; // Transactions per page

// Get filter parameters
$status = optional_param('status', '', PARAM_ALPHA);
$date_from = optional_param('date_from', '', PARAM_TEXT);
$date_to = optional_param('date_to', '', PARAM_TEXT);

// Convert date strings to timestamps if provided
$timestamp_from = empty($date_from) ? 0 : strtotime($date_from);
$timestamp_to = empty($date_to) ? 0 : strtotime($date_to . ' 23:59:59');

// Build SQL conditions for filtering
$conditions = array('merchant_id = :merchant_id');
$params = array('merchant_id' => $merchant->id);

if (!empty($status)) {
    $conditions[] = 'status = :status';
    $params['status'] = $status;
}

if (!empty($timestamp_from)) {
    $conditions[] = 'timecreated >= :date_from';
    $params['date_from'] = $timestamp_from;
}

if (!empty($timestamp_to)) {
    $conditions[] = 'timecreated <= :date_to';
    $params['date_to'] = $timestamp_to;
}

$conditions_sql = implode(' AND ', $conditions);

// Count total transactions matching the filter
$sql_count = "SELECT COUNT(*) FROM {local_lidio_transactions} WHERE $conditions_sql";
$total_transactions = $DB->count_records_sql($sql_count, $params);

// Get the transactions for the current page
$sql = "SELECT * FROM {local_lidio_transactions} 
        WHERE $conditions_sql 
        ORDER BY timecreated DESC";
$transactions = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Prepare transactions data for the template
$transactions_data = array();
if ($transactions) {
    foreach ($transactions as $transaction) {
        $status_text = '';
        $status_class = '';
        
        switch ($transaction->status) {
            case 'completed':
                $status_text = get_string('completed', 'local_lidio');
                $status_class = 'status_completed';
                break;
            case 'pending':
                $status_text = get_string('pending', 'local_lidio');
                $status_class = 'status_pending';
                break;
            case 'failed':
                $status_text = get_string('failed', 'local_lidio');
                $status_class = 'status_failed';
                break;
            case 'refunded':
                $status_text = get_string('refunded', 'local_lidio');
                $status_class = 'status_refunded';
                break;
            default:
                $status_text = $transaction->status;
                $status_class = '';
        }

        $transactions_data[] = array(
            'transaction_id' => $transaction->id,
            'formatted_date' => userdate($transaction->timecreated, get_string('strftimedatetime', 'langconfig')),
            'amount' => number_format($transaction->amount, 2),
            'payment_method' => $transaction->payment_method,
            'status_text' => $status_text,
            $status_class => true,
            'details_url' => new \moodle_url('/local/lidio/transaction_details.php', array('id' => $transaction->id)),
            'can_refund' => ($transaction->status === 'completed'),
            'refund_url' => new \moodle_url('/local/lidio/refund.php', array('id' => $transaction->id))
        );
    }
}

// Prepare pagination data
$pagination = array(
    'showing_start' => $total_transactions > 0 ? $page * $perpage + 1 : 0,
    'showing_end' => min(($page + 1) * $perpage, $total_transactions),
    'total' => $total_transactions,
    'has_prev' => $page > 0,
    'has_next' => ($page + 1) * $perpage < $total_transactions,
    'prev_url' => new \moodle_url('/local/lidio/transactions.php', array_merge(
        array('page' => $page - 1),
        !empty($status) ? array('status' => $status) : array(),
        !empty($date_from) ? array('date_from' => $date_from) : array(),
        !empty($date_to) ? array('date_to' => $date_to) : array()
    )),
    'next_url' => new \moodle_url('/local/lidio/transactions.php', array_merge(
        array('page' => $page + 1),
        !empty($status) ? array('status' => $status) : array(),
        !empty($date_from) ? array('date_from' => $date_from) : array(),
        !empty($date_to) ? array('date_to' => $date_to) : array()
    ))
);

// Prepare template context
$templatecontext = [
    'merchant' => $merchant,
    'transactions' => $transactions_data,
    'has_transactions' => !empty($transactions_data),
    'has_pagination' => $total_transactions > $perpage,
    'pagination' => $pagination,
    'dashboard_url' => new \moodle_url('/local/lidio/merchant.php', array('ismerchant' => 1)),
    'strings' => [
        // General strings
        'merchantdashboard' => get_string('merchantdashboard', 'local_lidio'),
        'welcome' => get_string('welcome', 'local_lidio'),
        'navigation' => get_string('navigation', 'local_lidio'),
        'dashboard' => get_string('dashboard', 'local_lidio'),
        'transactions' => get_string('transactions', 'local_lidio'),
        'settings' => get_string('settings', 'local_lidio'),
        'help' => get_string('help', 'local_lidio'),
        
        // Transaction-specific strings
        'transactionsintro' => get_string('transactionsintro', 'local_lidio'),
        'filtertransactions' => get_string('filtertransactions', 'local_lidio'),
        'transactionhistory' => get_string('transactionhistory', 'local_lidio'),
        'transactionid' => get_string('transactionid', 'local_lidio'),
        'date' => get_string('date', 'local_lidio'),
        'amount' => get_string('amount', 'local_lidio'),
        'paymentmethod' => get_string('paymentmethod', 'local_lidio'),
        'status' => get_string('status', 'local_lidio'),
        'actions' => get_string('actions', 'local_lidio'),
        'details' => get_string('details', 'local_lidio'),
        'refund' => get_string('refund', 'local_lidio'),
        'notransactions' => get_string('notransactions', 'local_lidio'),
        'notransactionsdesc' => get_string('notransactionsdesc', 'local_lidio'),
        'refresh' => get_string('refresh', 'local_lidio'),
        
        // Filter strings
        'datefrom' => get_string('datefrom', 'local_lidio'),
        'dateto' => get_string('dateto', 'local_lidio'),
        'allstatuses' => get_string('allstatuses', 'local_lidio'),
        'completed' => get_string('completed', 'local_lidio'),
        'pending' => get_string('pending', 'local_lidio'),
        'failed' => get_string('failed', 'local_lidio'),
        'refunded' => get_string('refunded', 'local_lidio'),
        'filter' => get_string('filter', 'local_lidio'),
        
        // Pagination strings
        'showing' => get_string('showing', 'local_lidio'),
        'to' => get_string('to', 'local_lidio'),
        'of' => get_string('of', 'local_lidio'),
        'results' => get_string('results', 'local_lidio'),
        'previous' => get_string('previous', 'local_lidio'),
        'next' => get_string('next', 'local_lidio')
    ]
];

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_lidio/merchant_transactions', $templatecontext);
echo $OUTPUT->footer(); 