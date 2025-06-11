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
 * Admin page to view detailed transaction information.
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
admin_externalpage_setup('local_lidio_transactions');

// Get transaction ID
$id = required_param('id', PARAM_INT);

// Get transaction details
$sql = "SELECT t.*, m.company_name, m.userid, u.firstname, u.lastname, pl.title as payment_link_title
        FROM {local_lidio_transactions} t
        LEFT JOIN {local_lidio_merchants} m ON t.merchant_id = m.id
        LEFT JOIN {user} u ON m.userid = u.id
        LEFT JOIN {local_lidio_payment_links} pl ON t.payment_link_id = pl.id
        WHERE t.id = :id";

$transaction = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

// Set up the page
$title = get_string('transactiondetails', 'local_lidio');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add CSS for better styling
$PAGE->requires->css('/local/lidio/styles.css');

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Back button
echo html_writer::start_div('mb-4');
echo html_writer::link(
    new moodle_url('/local/lidio/admin/transactions.php'),
    get_string('backtotransactions', 'local_lidio'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

// Format the transaction status with badge
$statusclass = '';
switch ($transaction->status) {
    case 'completed':
        $statusclass = 'badge badge-success';
        break;
    case 'failed':
        $statusclass = 'badge badge-danger';
        break;
    case 'pending':
        $statusclass = 'badge badge-warning';
        break;
    case 'refunded':
        $statusclass = 'badge badge-info';
        break;
    default:
        $statusclass = 'badge badge-secondary';
}
$status = html_writer::tag('span', ucfirst($transaction->status), ['class' => $statusclass]);

// Transaction details card
echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('transactioninfo', 'local_lidio'), 'card-header bg-primary text-white');
echo html_writer::start_div('card-body');

// Transaction ID and status
echo html_writer::start_div('row mb-3');
echo html_writer::div(get_string('transactionid', 'local_lidio') . ': <strong>' . $transaction->id . '</strong>', 'col-md-6');
echo html_writer::div(get_string('status', 'local_lidio') . ': ' . $status, 'col-md-6');
echo html_writer::end_div();

// Amount and date
echo html_writer::start_div('row mb-3');
echo html_writer::div(
    get_string('amount', 'local_lidio') . ': <strong>' . number_format($transaction->amount, 2) . ' ' . $transaction->currency . '</strong>',
    'col-md-6'
);
echo html_writer::div(
    get_string('date', 'local_lidio') . ': <strong>' . userdate($transaction->timecreated, get_string('strftimedatetime', 'langconfig')) . '</strong>',
    'col-md-6'
);
echo html_writer::end_div();

// Payment method and Gateway transaction ID
echo html_writer::start_div('row mb-3');
echo html_writer::div(
    get_string('paymentmethod', 'local_lidio') . ': <strong>' . $transaction->payment_method . '</strong>',
    'col-md-6'
);
echo html_writer::div(
    get_string('gatewaytransactionid', 'local_lidio') . ': <strong>' . 
    ($transaction->gateway_transaction_id ?: get_string('notavailable', 'local_lidio')) . '</strong>',
    'col-md-6'
);
echo html_writer::end_div();

echo html_writer::end_div(); // End card-body
echo html_writer::end_div(); // End card

// Merchant details card
echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('merchantinfo', 'local_lidio'), 'card-header bg-info text-white');
echo html_writer::start_div('card-body');

// Merchant name and link to merchant profile
$merchantname = $transaction->company_name ?: ($transaction->firstname . ' ' . $transaction->lastname);
echo html_writer::start_div('row mb-3');
echo html_writer::div(
    get_string('merchant', 'local_lidio') . ': <strong>' . $merchantname . '</strong>',
    'col-md-6'
);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/lidio/admin/view_merchant.php', ['id' => $transaction->merchant_id]),
        get_string('viewmerchantprofile', 'local_lidio'),
        ['class' => 'btn btn-sm btn-info']
    ),
    'col-md-6'
);
echo html_writer::end_div();

// Payment link details if available
if ($transaction->payment_link_id) {
    echo html_writer::div(
        get_string('paymentlink', 'local_lidio') . ': <strong>' . $transaction->payment_link_title . '</strong>',
        'mb-3'
    );
}

echo html_writer::end_div(); // End card-body
echo html_writer::end_div(); // End card

// Customer details card
echo html_writer::start_div('card mb-4');
echo html_writer::div(get_string('customerinfo', 'local_lidio'), 'card-header bg-success text-white');
echo html_writer::start_div('card-body');

// Customer name
echo html_writer::div(
    get_string('customername', 'local_lidio') . ': <strong>' . $transaction->customer_name . '</strong>',
    'mb-3'
);

// Customer email if available
if (!empty($transaction->customer_email)) {
    echo html_writer::div(
        get_string('customeremail', 'local_lidio') . ': <strong>' . $transaction->customer_email . '</strong>',
        'mb-3'
    );
}

// Customer phone if available
if (!empty($transaction->customer_phone)) {
    echo html_writer::div(
        get_string('customerphone', 'local_lidio') . ': <strong>' . $transaction->customer_phone . '</strong>',
        'mb-3'
    );
}

echo html_writer::end_div(); // End card-body
echo html_writer::end_div(); // End card

// Technical details card - Gateway response
if (!empty($transaction->gateway_response)) {
    echo html_writer::start_div('card mb-4');
    echo html_writer::div(get_string('technicaldetails', 'local_lidio'), 'card-header bg-secondary text-white');
    echo html_writer::start_div('card-body');
    
    echo html_writer::tag('h5', get_string('gatewayresponse', 'local_lidio'), ['class' => 'mb-3']);
    
    // Format JSON if it's valid JSON
    $response_data = $transaction->gateway_response;
    if (json_decode($response_data)) {
        $formatted_json = json_encode(json_decode($response_data), JSON_PRETTY_PRINT);
        echo html_writer::tag('pre', $formatted_json, ['class' => 'bg-light p-3 rounded']);
    } else {
        echo html_writer::tag('pre', $response_data, ['class' => 'bg-light p-3 rounded']);
    }
    
    echo html_writer::end_div(); // End card-body
    echo html_writer::end_div(); // End card
}

// Action buttons
echo html_writer::start_div('mb-4');

// Only show refund button if transaction is completed
if ($transaction->status === 'completed') {
    echo html_writer::link(
        new moodle_url('/local/lidio/admin/refund_transaction.php', ['id' => $transaction->id]),
        get_string('refundtransaction', 'local_lidio'),
        ['class' => 'btn btn-warning mr-2', 'onclick' => 'return confirm("' . get_string('refundconfirm', 'local_lidio') . '")']
    );
}

echo html_writer::end_div();

echo $OUTPUT->footer(); 