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
 * @copyright  2023 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Get the transaction reference from the URL
$reference = required_param('reference', PARAM_TEXT);

// Check if the transaction exists
global $DB;
$transaction = $DB->get_record('local_lidio_transactions', ['reference' => $reference], '*', MUST_EXIST);

// Get payment link details
$paymentlink = null;
if (!empty($transaction->payment_link_id)) {
    $paymentlink = $DB->get_record('local_lidio_payment_links', ['id' => $transaction->payment_link_id]);
}

// Get merchant details
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $transaction->merchant_id], '*', MUST_EXIST);

// Page setup
$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/local/lidio/payment_success.php', ['reference' => $reference]);
$PAGE->set_title(get_string('paymentsuccess', 'local_lidio'));
$PAGE->set_heading(get_string('paymentsuccess', 'local_lidio'));

// Add Tailwind CSS
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css'));

// Display the success page
echo $OUTPUT->header();

$templatecontext = [
    'transaction' => $transaction,
    'paymentlink' => $paymentlink,
    'merchant' => $merchant,
    'amount_formatted' => number_format($transaction->amount, 2) . ' ' . $transaction->currency,
    'wwwroot' => $CFG->wwwroot,
];

echo $OUTPUT->render_from_template('local_lidio/payment_success', $templatecontext);
echo $OUTPUT->footer(); 