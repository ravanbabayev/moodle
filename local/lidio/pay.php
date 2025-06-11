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
 * Payment page for Lidio payment links
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

// Get the payment link code from the URL
$code = required_param('code', PARAM_ALPHANUM);

// Fetch the payment link from the database
$paymentlink = $DB->get_record('local_lidio_payment_links', ['link_code' => $code, 'status' => 'active']);
if (!$paymentlink) {
    throw new \moodle_exception('invalidpaymentlink', 'local_lidio');
}

// Check if the link has expired
if (!empty($paymentlink->expires_at) && $paymentlink->expires_at < time()) {
    throw new \moodle_exception('paymentlinkexpired', 'local_lidio');
}

// Check if the link has reached its maximum uses
if (!empty($paymentlink->max_uses) && $paymentlink->current_uses >= $paymentlink->max_uses) {
    throw new \moodle_exception('paymentlinkmaxuses', 'local_lidio');
}

// Get the merchant details
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $paymentlink->merchantid]);
if (!$merchant) {
    throw new \moodle_exception('invalidmerchant', 'local_lidio');
}

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/lidio/pay.php', ['code' => $code]));
$PAGE->set_title(get_string('paynow', 'local_lidio') . ': ' . $paymentlink->title);
$PAGE->set_heading($paymentlink->title);
$PAGE->set_pagelayout('standard');

// Format the amount with currency
$amount_formatted = number_format($paymentlink->amount, 2) . ' ' . $paymentlink->currency;

// Determine contact requirements
$contact_requirements = [
    'phone_required' => ($paymentlink->require_phone == 1 && $paymentlink->require_email == 0),
    'email_required' => ($paymentlink->require_phone == 0 && $paymentlink->require_email == 1),
    'both_required' => ($paymentlink->require_phone == 1 && $paymentlink->require_email == 1),
    'phone_or_email' => ($paymentlink->require_phone == 0 && $paymentlink->require_email == 0),
];

// Process payment form submission
if ($data = data_submitted() && confirm_sesskey()) {
    // Doğrulamayı tamamen kaldırıyoruz
    $errors = [];
    $customer_email = !empty($data->customer_email) ? trim($data->customer_email) : '';
    $customer_phone = !empty($data->customer_phone) ? trim($data->customer_phone) : '';
    
    // Doğrulama kodu tamamen kaldırıldı
    
    // Hata ayıklama için
    try {
        // Form verilerini yazdır
        echo "<div style='background-color: #e2f0ff; border: 1px solid #b8daff; padding: 15px; margin: 15px; border-radius: 5px;'>";
        echo "<h3>Form Verileri:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        echo "</div>";
        
        // Increment the usage counter
        $DB->set_field('local_lidio_payment_links', 'current_uses', $paymentlink->current_uses + 1, ['id' => $paymentlink->id]);
        
        // Create a new transaction record
        $transaction = new \stdClass();
        $transaction->merchant_id = $paymentlink->merchantid;
        $transaction->payment_link_id = $paymentlink->id;
        $transaction->gateway_transaction_id = uniqid('LIDIO-');
        $transaction->amount = $paymentlink->amount;
        $transaction->currency = $paymentlink->currency;
        $transaction->status = 'pending';
        
        // Ödeme yöntemi için varsayılan değer
        $transaction->payment_method = 'credit_card'; // Varsayılan değer
        if (isset($data->payment_method) && !empty($data->payment_method)) {
            $transaction->payment_method = $data->payment_method;
        }
        
        // Müşteri bilgileri
        $transaction->customer_name = isset($data->customer_name) ? $data->customer_name : '';
        $transaction->customer_email = $customer_email;
        $transaction->customer_phone = $customer_phone;
        $transaction->timecreated = time();
        $transaction->timemodified = time();
        
        // Debug bilgisi
        echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px; border-radius: 5px;'>";
        echo "<h3>Transaction Verileri:</h3>";
        echo "<pre>" . print_r($transaction, true) . "</pre>";
        echo "</div>";
        
        // Veritabanı şemasını kontrol et
        $table_info = $DB->get_columns('local_lidio_transactions');
        echo "<div style='background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin: 15px; border-radius: 5px;'>";
        echo "<h3>Veritabanı Şeması:</h3>";
        echo "<pre>" . print_r($table_info, true) . "</pre>";
        echo "</div>";
        
        $transactionid = $DB->insert_record('local_lidio_transactions', $transaction);
        
        echo "<p style='background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px;'>İşlem ID: " . $transactionid . "</p>";
        
        // Redirect to payment gateway or process payment
        // This is a placeholder for the actual payment processing logic
        // In a real implementation, you would redirect to a payment gateway or process the payment here
        
        // For now, just redirect to a success page
        if (!empty($paymentlink->success_url)) {
            redirect($paymentlink->success_url);
        } else {
            $successurl = $CFG->wwwroot . '/local/lidio/payment_success.php?reference=' . $transaction->gateway_transaction_id;
            redirect($successurl);
        }
    } catch (Exception $e) {
        echo '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;">';
        echo '<h3>Hata Oluştu:</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<p>Dosya: ' . $e->getFile() . ' (Satır: ' . $e->getLine() . ')</p>';
        echo '<p>Hata Kodu: ' . $e->getCode() . '</p>';
        echo '<p>Hata İzleme:</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
        echo '</div>';
    }
}

// Render the payment form
echo $OUTPUT->header();

// Create template context
$templatecontext = [
    'paymentlink' => $paymentlink,
    'merchant' => $merchant,
    'amount_formatted' => $amount_formatted,
    'contact_requirements' => $contact_requirements,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
];

// Render the template
echo $OUTPUT->render_from_template('local_lidio/payment_form', $templatecontext);

echo $OUTPUT->footer(); 