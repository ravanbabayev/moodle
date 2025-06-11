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
 * Payment details page for Lidio payment system
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Import required classes
use \context_system;
use \moodle_url;

// Get transaction ID from URL
$transactionid = required_param('id', PARAM_INT);
$payment_method = required_param('method', PARAM_ALPHA);

// Fetch transaction data
$transaction = $DB->get_record('local_lidio_transactions', ['id' => $transactionid], '*', MUST_EXIST);

// Fetch payment link
$paymentlink = $DB->get_record('local_lidio_payment_links', ['id' => $transaction->payment_link_id], '*', MUST_EXIST);

// Fetch merchant
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $transaction->merchant_id], '*', MUST_EXIST);

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/lidio/payment_details.php', ['id' => $transactionid, 'method' => $payment_method]));
$PAGE->set_title(get_string('paymentdetails', 'local_lidio', ['amount' => number_format($transaction->amount, 2) . ' ' . $transaction->currency]));
$PAGE->set_heading(get_string('paymentdetails', 'local_lidio', ['amount' => number_format($transaction->amount, 2) . ' ' . $transaction->currency]));
$PAGE->set_pagelayout('standard');

// Format the amount with currency
$amount_formatted = number_format($transaction->amount, 2) . ' ' . $transaction->currency;

// Process payment form submission
if ($data = data_submitted() && confirm_sesskey()) {
    try {
        if ($payment_method === 'credit_card') {
            // Simülasyon: Kredi kartı işlemi
            $transaction->status = 'completed';
            $transaction->gateway_response = json_encode(['card_last4' => substr($data->card_number, -4), 'processor' => 'Demo', 'success' => true]);
            $transaction->timemodified = time();
            
            $DB->update_record('local_lidio_transactions', $transaction);
            
            if (!empty($paymentlink->success_url)) {
                redirect($paymentlink->success_url);
            } else {
                redirect(new moodle_url('/local/lidio/payment_success.php', ['transaction_id' => $transaction->gateway_transaction_id]));
            }
        } else if ($payment_method === 'bank_transfer') {
            // Banka havalesi için
            $transaction->status = 'pending';
            $transaction->gateway_response = json_encode(['bank_name' => $data->bank_name, 'time' => time()]);
            $transaction->timemodified = time();
            
            $DB->update_record('local_lidio_transactions', $transaction);
            
            redirect(new moodle_url('/local/lidio/payment_bank_details.php', ['id' => $transaction->id]));
        }
    } catch (Exception $e) {
        echo '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;">';
        echo '<h3>Hata Oluştu:</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
    }
}

// Render the payment details form
echo $OUTPUT->header();

// Create template context
$templatecontext = [
    'transaction' => $transaction,
    'paymentlink' => $paymentlink,
    'merchant' => $merchant,
    'amount_formatted' => $amount_formatted,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
    'is_credit_card' => ($payment_method === 'credit_card'),
    'is_bank_transfer' => ($payment_method === 'bank_transfer'),
    'current_year' => date('Y'),
    'exp_years' => range(date('Y'), date('Y') + 10),
    'exp_months' => range(1, 12),
];

// Render the template
echo $OUTPUT->render_from_template('local_lidio/payment_details', $templatecontext);

echo $OUTPUT->footer(); 