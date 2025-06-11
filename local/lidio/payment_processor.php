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
 * Payment processor for Lidio payment system
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
$reference = required_param('reference', PARAM_ALPHANUM);

// Fetch transaction data
$transaction = $DB->get_record('local_lidio_transactions', ['id' => $transactionid, 'reference' => $reference], '*', MUST_EXIST);

// Fetch payment link
$paymentlink = $DB->get_record('local_lidio_payment_links', ['id' => $transaction->payment_link_id], '*', MUST_EXIST);

// Fetch merchant
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $transaction->merchant_id], '*', MUST_EXIST);

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/lidio/payment_processor.php', ['id' => $transactionid, 'reference' => $reference]));
$PAGE->set_title(get_string('paymentprocessing', 'local_lidio'));
$PAGE->set_heading(get_string('paymentprocessing', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Format the amount with currency
$amount_formatted = number_format($transaction->amount, 2) . ' ' . $transaction->currency;

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    try {
        // Kart bilgilerini al
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
        
        // Hata varsa göster
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            echo $OUTPUT->header();
            echo '<div class="alert alert-danger">' . $error_message . '</div>';
            echo '<div class="mt-3"><a href="' . $PAGE->url->out() . '" class="btn btn-primary">' . get_string('tryagain', 'local_lidio') . '</a></div>';
            echo $OUTPUT->footer();
            exit;
        }
        
        // Ödeme işlemi simülasyonu (gerçek bir ödeme entegrasyonu burada olacak)
        // Burada gerçek bir ödeme ağ geçidi entegrasyonu yapılacak (Iyzico, PayTR, vs)
        
        // Simülasyon: İşlem her zaman başarılı
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
        
        // Başarılı ödeme sayfasına yönlendir
        redirect(new moodle_url('/local/lidio/payment_success.php', ['reference' => $transaction->reference]));
        
    } catch (Exception $e) {
        echo $OUTPUT->header();
        echo '<div class="alert alert-danger">Ödeme işlemi sırasında bir hata oluştu: ' . $e->getMessage() . '</div>';
        echo '<div class="mt-3"><a href="' . $PAGE->url->out() . '" class="btn btn-primary">' . get_string('tryagain', 'local_lidio') . '</a></div>';
        echo $OUTPUT->footer();
        exit;
    }
}

// Render the payment form
echo $OUTPUT->header();

// Get any session messages
if (isset($_SESSION['payment_errors'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['payment_errors'] . '</div>';
    unset($_SESSION['payment_errors']);
}

// Create template context
$templatecontext = [
    'transaction' => $transaction,
    'paymentlink' => $paymentlink,
    'merchant' => $merchant,
    'amount_formatted' => $amount_formatted,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
    'processor_url' => $PAGE->url->out(),
    'current_year' => date('Y'),
    'exp_years' => range(date('Y'), date('Y') + 10),
    'exp_months' => range(1, 12),
];

// Render the template
echo $OUTPUT->render_from_template('local_lidio/payment_processor', $templatecontext);

echo $OUTPUT->footer();

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