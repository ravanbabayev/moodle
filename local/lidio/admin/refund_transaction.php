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
 * Admin page to refund a transaction.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Global imports to fix linter errors
use core\output\html_writer;

// Check access
admin_externalpage_setup('local_lidio_transactions');

// Get transaction ID
$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Get transaction details
$sql = "SELECT t.*, m.company_name, m.userid, u.firstname, u.lastname
        FROM {local_lidio_transactions} t
        LEFT JOIN {local_lidio_merchants} m ON t.merchant_id = m.id
        LEFT JOIN {user} u ON m.userid = u.id
        WHERE t.id = :id";

$transaction = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

// Verify transaction is in 'completed' status
if ($transaction->status !== 'completed') {
    redirect(
        new \moodle_url('/local/lidio/admin/transaction_detail.php', ['id' => $id]),
        get_string('refunderror_wrongstatus', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Set up the page
$title = get_string('refundtransaction', 'local_lidio');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Process the refund if confirmed
if ($confirm) {
    // Here we would integrate with payment gateway to process the actual refund
    // For now, we'll just update the transaction status
    
    // Create a gateway response for the refund
    $gateway_response = json_encode([
        'refund_id' => 'manual_refund_' . time(),
        'refund_status' => 'success',
        'refund_date' => date('Y-m-d H:i:s'),
        'refund_amount' => $transaction->amount,
        'refund_currency' => $transaction->currency,
        'refund_reason' => 'Manual refund by admin',
        'refund_by' => fullname($USER)
    ]);
    
    // Process the refund
    if (local_lidio_process_transaction_status($id, 'refunded', $gateway_response)) {
        redirect(
            new \moodle_url('/local/lidio/admin/transaction_detail.php', ['id' => $id]),
            get_string('refundsuccess', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            new \moodle_url('/local/lidio/admin/transaction_detail.php', ['id' => $id]),
            get_string('refunderror', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Transaction details
$merchantname = $transaction->company_name ?: ($transaction->firstname . ' ' . $transaction->lastname);
$amount = number_format($transaction->amount, 2) . ' ' . $transaction->currency;
$date = userdate($transaction->timecreated, get_string('strftimedatetime', 'langconfig'));

echo $OUTPUT->box_start('generalbox');

echo '<div class="alert alert-warning">';
echo '<h4>' . get_string('confirmrefund', 'local_lidio') . '</h4>';
echo '<p>' . get_string('refundwarning', 'local_lidio') . '</p>';
echo '</div>';

echo '<div class="mb-3">';
echo '<strong>' . get_string('transactionid', 'local_lidio') . ':</strong> ' . $transaction->id . '<br>';
echo '<strong>' . get_string('merchant', 'local_lidio') . ':</strong> ' . $merchantname . '<br>';
echo '<strong>' . get_string('amount', 'local_lidio') . ':</strong> ' . $amount . '<br>';
echo '<strong>' . get_string('date', 'local_lidio') . ':</strong> ' . $date . '<br>';
echo '</div>';

// Confirmation buttons
echo '<div class="mb-3">';
echo \html_writer::link(
    new \moodle_url('/local/lidio/admin/refund_transaction.php', ['id' => $id, 'confirm' => 1]),
    get_string('confirmrefundbutton', 'local_lidio'),
    ['class' => 'btn btn-danger mr-2']
);
echo \html_writer::link(
    new \moodle_url('/local/lidio/admin/transaction_detail.php', ['id' => $id]),
    get_string('cancel', 'local_lidio'),
    ['class' => 'btn btn-secondary']
);
echo '</div>';

echo $OUTPUT->box_end();

echo $OUTPUT->footer(); 