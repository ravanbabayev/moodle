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
 * Payment success page for Lidio payment system
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

// Get transaction reference from URL
$transaction_id = required_param('transaction_id', PARAM_ALPHANUM);

// Debug bilgisi ekle
$transactions = $DB->get_records_sql("SELECT id, gateway_transaction_id FROM {local_lidio_transactions} ORDER BY id DESC LIMIT 10");
echo "<div style='background-color: #f0f0f0; padding: 15px; margin: 15px; border-radius: 5px;'>";
echo "<h3>Debug Bilgisi:</h3>";
echo "<p>Aranan transaction_id: " . $transaction_id . "</p>";
echo "<p>Son 10 transaction kaydı:</p><ul>";
foreach ($transactions as $t) {
    echo "<li>ID: " . $t->id . ", gateway_transaction_id: " . $t->gateway_transaction_id . 
         ($t->gateway_transaction_id == $transaction_id ? " <strong>(EŞLEŞME VAR)</strong>" : "") . "</li>";
}
echo "</ul></div>";

// Fetch transaction data
$transaction = $DB->get_record('local_lidio_transactions', ['gateway_transaction_id' => $transaction_id]);

// Eğer transaction bulunamadıysa, hata mesajı göster
if (!$transaction) {
    echo $OUTPUT->header();
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;'>";
    echo "<h3>Hata:</h3>";
    echo "<p>Transaction kaydı bulunamadı. Transaction ID: " . $transaction_id . "</p>";
    echo "</div>";
    echo $OUTPUT->footer();
    die();
}

// Fetch payment link
$paymentlink = $DB->get_record('local_lidio_payment_links', ['id' => $transaction->payment_link_id], '*', MUST_EXIST);

// Fetch merchant
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $transaction->merchant_id], '*', MUST_EXIST);

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/lidio/payment_success.php', ['transaction_id' => $transaction_id]));
$PAGE->set_title(get_string('paymentsuccess', 'local_lidio'));
$PAGE->set_heading(get_string('paymentsuccess', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Format the amount with currency
$amount_formatted = number_format($transaction->amount, 2) . ' ' . $transaction->currency;

// Render the success page
echo $OUTPUT->header();

// Create template context
$templatecontext = [
    'transaction' => $transaction,
    'paymentlink' => $paymentlink,
    'merchant' => $merchant,
    'amount_formatted' => $amount_formatted,
    'wwwroot' => $CFG->wwwroot,
    'is_credit_card' => ($transaction->payment_method === 'credit_card'),
    'is_bank_transfer' => ($transaction->payment_method === 'bank_transfer'),
];

// Get card last 4 digits from gateway response if available
if (!empty($transaction->gateway_response)) {
    $gateway_response = json_decode($transaction->gateway_response, true);
    if (isset($gateway_response['card_last4'])) {
        $templatecontext['card_last4'] = $gateway_response['card_last4'];
    }
}

// Render the template
echo $OUTPUT->render_from_template('local_lidio/payment_success', $templatecontext);

echo $OUTPUT->footer(); 