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
        $transaction->reference = $reference_code; // Veritabanında reference alanı var
        $transaction->amount = $paymentlink->amount;
        $transaction->currency = $paymentlink->currency;
        $transaction->status = 'pending';
        
        // Kredi kartı bilgilerini al
        $card_number = isset($_POST['card_number']) ? preg_replace('/\D/', '', $_POST['card_number']) : '';
        $card_holder = isset($_POST['card_holder']) ? trim($_POST['card_holder']) : '';
        $exp_month = isset($_POST['exp_month']) ? trim($_POST['exp_month']) : '';
        $exp_year = isset($_POST['exp_year']) ? trim($_POST['exp_year']) : '';
        $cvv = isset($_POST['cvv']) ? trim($_POST['cvv']) : '';
        
        // Kart doğrulaması (bu örnek için basit doğrulama)
        $errors = [];
        
        if (strlen($card_number) < 13 || strlen($card_number) > 19) {
            $errors[] = get_string('invalidcardnumber', 'local_lidio');
        }
        
        if (empty($card_holder)) {
            $errors[] = get_string('invalidcardholder', 'local_lidio');
        }
        
        if (empty($exp_month) || empty($exp_year) || !is_numeric($exp_month) || !is_numeric($exp_year)) {
            $errors[] = get_string('invalidexpiry', 'local_lidio');
        }
        
        if (strlen($cvv) < 3 || strlen($cvv) > 4 || !is_numeric($cvv)) {
            $errors[] = get_string('invalidcvv', 'local_lidio');
        }
        
        // Hata varsa, formu tekrar göster
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            // Hata mesajlarını göster
            echo $OUTPUT->header();
            echo '<div class="alert alert-danger">' . $error_message . '</div>';
            
            // Template context
            $templatecontext = [
                'paymentlink' => $paymentlink,
                'merchant' => $merchant,
                'amount_formatted' => $amount_formatted,
                'contact_requirements' => $contact_requirements,
                'sesskey' => sesskey(),
                'wwwroot' => $CFG->wwwroot,
                'exp_months' => range(1, 12),
                'exp_years' => range(date('Y'), date('Y') + 10),
            ];
            
            echo $OUTPUT->render_from_template('local_lidio/payment_form', $templatecontext);
            echo $OUTPUT->footer();
            exit;
        }
        
        // Müşteri bilgileri
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
        
        $transaction->payment_method = 'credit_card';
        $transaction->timecreated = time();
        $transaction->timemodified = time();
        
        // Insert transaction record
        $transactionid = $DB->insert_record('local_lidio_transactions', $transaction);
        
        // Ödeme işlemi simülasyonu (gerçek bir ödeme entegrasyonu burada olacak)
        // Burada gerçek bir ödeme ağ geçidi entegrasyonu yapılacak (Iyzico, PayTR, vs)
        
        // Simülasyon: İşlem her zaman başarılı
        $transaction->id = $transactionid;
        $transaction->status = 'completed';
        $transaction->timecompleted = time();
        $transaction->timemodified = time();
        
        // Son 4 hanesi ve sahte işlem ID'si
        $last4 = substr($card_number, -4);
        $gateway_txn_id = 'DEMO-' . time() . '-' . rand(1000, 9999);
        
        // Gateway yanıtını json olarak kaydet
        $gateway_response = [
            'status' => 'success',
            'transaction_id' => $gateway_txn_id,
            'card_last4' => $last4,
            'card_brand' => detectCardBrand($card_number),
            'processor' => 'DEMO',
            'time' => time()
        ];
        
        $transaction->gateway_response = json_encode($gateway_response);
        
        // Transaction'ı güncelle
        $DB->update_record('local_lidio_transactions', $transaction);
        
        // Linki kullanım sayacını artır
        $DB->set_field('local_lidio_payment_links', 'current_uses', $paymentlink->current_uses + 1, ['id' => $paymentlink->id]);
        
        // Başarılı ödeme sayfasına yönlendir
        redirect(new moodle_url('/local/lidio/payment_success.php', ['reference' => $transaction->reference]));
        
    } catch (Exception $e) {
        echo '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;">';
        echo '<h3>Hata Oluştu:</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
    }
}

/**
 * Kart markasını tespit eden yardımcı fonksiyon
 *
 * @param string $number Kart numarası
 * @return string Kart markası
 */
function detectCardBrand($number) {
    $number = preg_replace('/\D/', '', $number);
    
    // Kart numarası desenleri
    $patterns = [
        'visa' => '/^4\d{12}(\d{3})?$/',
        'mastercard' => '/^(5[1-5]\d{4}|222[1-9]\d{3}|22[3-9]\d{4}|2[3-6]\d{5}|27[01]\d{4}|2720\d{3})\d{10}$/',
        'amex' => '/^3[47]\d{13}$/',
        'discover' => '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/',
    ];
    
    foreach ($patterns as $brand => $pattern) {
        if (preg_match($pattern, $number)) {
            return ucfirst($brand);
        }
    }
    
    return 'Unknown';
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
    'exp_months' => range(1, 12),
    'exp_years' => range(date('Y'), date('Y') + 10),
];

// Render the template
echo $OUTPUT->render_from_template('local_lidio/payment_form', $templatecontext);

echo $OUTPUT->footer(); 