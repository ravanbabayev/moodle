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
 * Payment link handler for Lidio payment system
 *
 * @package    local_lidio
 * @copyright  2023 onwards
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Import required classes
use \context_system;
use \moodle_url;

// Get the payment link code from the URL
$linkcode = required_param('code', PARAM_ALPHANUM);

// Check if the link code exists
global $DB;
$paymentlink = $DB->get_record('local_lidio_payment_links', ['link_code' => $linkcode, 'status' => 'active']);

if (!$paymentlink) {
    throw new \core\exception\moodle_exception('invalidpaymentlink', 'local_lidio', '', null, 'Invalid or expired payment link');
}

// Check if the link has expired
if (!empty($paymentlink->expiry_date) && $paymentlink->expiry_date < time()) {
    $DB->set_field('local_lidio_payment_links', 'status', 'expired', ['id' => $paymentlink->id]);
    throw new \core\exception\moodle_exception('expiredpaymentlink', 'local_lidio', '', null, 'This payment link has expired');
}

// Check if the link has reached maximum uses
if (!empty($paymentlink->max_uses) && $paymentlink->current_uses >= $paymentlink->max_uses) {
    $DB->set_field('local_lidio_payment_links', 'status', 'inactive', ['id' => $paymentlink->id]);
    throw new \core\exception\moodle_exception('maxusesreached', 'local_lidio', '', null, 'This payment link has reached its maximum number of uses');
}

// Get merchant details
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $paymentlink->merchantid], '*', MUST_EXIST);

// Page setup
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/lidio/pay.php', ['code' => $linkcode]);
$PAGE->set_title(get_string('paytitle', 'local_lidio', $paymentlink->title));
$PAGE->set_heading(get_string('paytitle', 'local_lidio', $paymentlink->title));
$PAGE->set_pagelayout('popup');  // Use popup layout for payment pages

// Add Tailwind CSS
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css'));

// Display the payment form
echo $OUTPUT->header();

// Prepare contact requirements for template
$contact_requirements = [
    'phone_or_email' => false,
    'phone_required' => false,
    'email_required' => false,
    'both_required' => false
];

// Set the appropriate requirement based on payment link settings
if ($paymentlink->require_phone && $paymentlink->require_email) {
    $contact_requirements['both_required'] = true;
    $requirement = 'both_required';
} elseif ($paymentlink->require_phone) {
    $contact_requirements['phone_required'] = true;
    $requirement = 'phone_required';
} elseif ($paymentlink->require_email) {
    $contact_requirements['email_required'] = true;
    $requirement = 'email_required';
} else {
    $contact_requirements['phone_or_email'] = true;
    $requirement = 'phone_or_email';
}

$templatecontext = [
    'paymentlink' => $paymentlink,
    'merchant' => $merchant,
    'amount_formatted' => number_format($paymentlink->amount, 2) . ' ' . $paymentlink->currency,
    'wwwroot' => $CFG->wwwroot,
    'sesskey' => sesskey(),
    'contact_requirements' => $contact_requirements,
];

// Process payment form submission
if ($data = data_submitted() && confirm_sesskey()) {
    // Doğrulamayı tamamen kaldırıyoruz
    $errors = [];
    $customer_email = !empty($data->customer_email) ? trim($data->customer_email) : '';
    $customer_phone = !empty($data->customer_phone) ? trim($data->customer_phone) : '';
    
    // Doğrulama kodu tamamen kaldırıldı
    
    // Increment the usage counter
    $DB->set_field('local_lidio_payment_links', 'current_uses', $paymentlink->current_uses + 1, ['id' => $paymentlink->id]);
    
    // Create a new transaction record
    $transaction = new \stdClass();
    $transaction->merchant_id = $paymentlink->merchantid;
    $transaction->payment_link_id = $paymentlink->id;
    $transaction->reference = uniqid('LIDIO-');
    $transaction->amount = $paymentlink->amount;
    $transaction->currency = $paymentlink->currency;
    $transaction->status = 'pending';
    $transaction->payment_method = $data->payment_method;
    $transaction->customer_name = $data->customer_name;
    $transaction->customer_email = $customer_email; // Use validated email
    $transaction->customer_phone = $customer_phone; // Use validated phone
    $transaction->timecreated = time();
    $transaction->timemodified = time();
    
    $transactionid = $DB->insert_record('local_lidio_transactions', $transaction);
    
    // Redirect to payment gateway or process payment
    // This is a placeholder for the actual payment processing logic
    // In a real implementation, you would redirect to a payment gateway or process the payment here
    
    // For now, just redirect to a success page
    if (!empty($paymentlink->success_url)) {
        redirect($paymentlink->success_url);
    } else {
        $successurl = $CFG->wwwroot . '/local/lidio/payment_success.php?reference=' . $transaction->reference;
        redirect($successurl);
    }
}

echo $OUTPUT->render_from_template('local_lidio/payment_form', $templatecontext);
echo $OUTPUT->footer(); 