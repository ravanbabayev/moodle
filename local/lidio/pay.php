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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    try {
        // Benzersiz bir referans kodu oluştur
        $reference_code = uniqid('LIDIO-');
        
        // Create a new transaction record
        $transaction = new \stdClass();
        $transaction->merchant_id = $paymentlink->merchantid;
        $transaction->payment_link_id = $paymentlink->id;
        $transaction->gateway_transaction_id = $reference_code;
        $transaction->amount = $paymentlink->amount;
        $transaction->currency = $paymentlink->currency;
        $transaction->status = 'pending';
        
        // Ödeme yöntemi için varsayılan değer
        $transaction->payment_method = 'credit_card'; // Varsayılan değer
        if (isset($_POST['payment_method']) && !empty($_POST['payment_method'])) {
            $transaction->payment_method = $_POST['payment_method'];
        }
        
        // Müşteri bilgileri - direct $_POST erişimi
        $transaction->customer_name = '';
        if (isset($_POST['customer_name'])) {
            $transaction->customer_name = trim($_POST['customer_name']);
        }
        
        $transaction->customer_email = '';
        if (isset($_POST['customer_email'])) {
            $transaction->customer_email = trim($_POST['customer_email']);
        }
        
        $transaction->customer_phone = '';
        if (isset($_POST['customer_phone'])) {
            $transaction->customer_phone = trim($_POST['customer_phone']);
        }
        
        $transaction->timecreated = time();
        $transaction->timemodified = time();
        
        // Insert transaction record
        $transactionid = $DB->insert_record('local_lidio_transactions', $transaction);
        
        // Ödeme yöntemine göre yönlendirme yap
        if ($transaction->payment_method === 'credit_card') {
            // Kredi kartı ödemesi için doğrudan ödeme işlemcisine yönlendir
            // Bu örnekte, payment_processor.php adında bir dosya kullandığımızı varsayıyoruz
            $payment_url = new moodle_url('/local/lidio/payment_processor.php', [
                'id' => $transactionid, 
                'reference' => $transaction->gateway_transaction_id
            ]);
            
            // Linki kullanım sayacını artır
            $DB->set_field('local_lidio_payment_links', 'current_uses', $paymentlink->current_uses + 1, ['id' => $paymentlink->id]);
            
            // Yönlendirme öncesi çıktı verilmemeli
            redirect($payment_url);
        } else if ($transaction->payment_method === 'bank_transfer') {
            // Banka havalesi için banka bilgileri sayfasına yönlendir
            $bank_details_url = new moodle_url('/local/lidio/payment_bank_details.php', [
                'id' => $transactionid
            ]);
            
            // Linki kullanım sayacını artır
            $DB->set_field('local_lidio_payment_links', 'current_uses', $paymentlink->current_uses + 1, ['id' => $paymentlink->id]);
            
            // Yönlendirme öncesi çıktı verilmemeli
            redirect($bank_details_url);
        }
        
    } catch (Exception $e) {
        echo '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;">';
        echo '<h3>Hata Oluştu:</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
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