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
 * Para çekme işlemi için sayfa
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_login();

// Import required classes
use \context_system;
use \moodle_url;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/lidio/withdraw.php'));
$PAGE->set_title(get_string('withdraw', 'local_lidio', []));
$PAGE->set_heading(get_string('withdraw', 'local_lidio', []));
$PAGE->set_pagelayout('standard');

// Satıcı bilgilerini al
$merchant = $DB->get_record('local_lidio_merchants', ['userid' => $USER->id]);
if (!$merchant) {
    redirect(new moodle_url('/local/lidio/become_merchant.php'), get_string('notamerchant', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
}

// Para çekme işlemi için form gönderildi mi?
$withdraw_submitted = false;
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $amount = required_param('amount', PARAM_FLOAT);
    $bank_name = required_param('bank_name', PARAM_TEXT);
    $account_holder = required_param('account_holder', PARAM_TEXT);
    $iban = required_param('iban', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_TEXT);
    
    // Validasyon
    if ($amount <= 0) {
        $error_message = get_string('invalidamount', 'local_lidio');
    } else if ($amount > $merchant->balance) {
        $error_message = get_string('insufficientbalance', 'local_lidio');
    } else if (empty($bank_name)) {
        $error_message = get_string('banknamereq', 'local_lidio');
    } else if (empty($account_holder)) {
        $error_message = get_string('accountholderreq', 'local_lidio');
    } else if (empty($iban)) {
        $error_message = get_string('ibanreq', 'local_lidio');
    } else {
        // Para çekme işlemini kaydet
        $withdraw = new \stdClass();
        $withdraw->merchant_id = $merchant->id;
        $withdraw->amount = $amount;
        $withdraw->currency = 'TRY'; // Varsayılan para birimi
        $withdraw->status = 'pending';
        $withdraw->bank_name = $bank_name;
        $withdraw->account_holder = $account_holder;
        $withdraw->iban = $iban;
        $withdraw->description = $description;
        $withdraw->timecreated = time();
        $withdraw->timemodified = time();
        
        // Veritabanına kaydet
        $withdraw_id = $DB->insert_record('local_lidio_withdrawals', $withdraw);
        
        if ($withdraw_id) {
            // Bakiyeyi güncelle
            $merchant->balance -= $amount;
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
            
            $withdraw_submitted = true;
            $success_message = get_string('withdrawrequested', 'local_lidio');
        } else {
            $error_message = get_string('withdrawfailed', 'local_lidio');
        }
    }
}

// Önceki para çekme işlemleri
$withdrawals = $DB->get_records('local_lidio_withdrawals', ['merchant_id' => $merchant->id], 'timecreated DESC');

// Sayfayı göster
echo $OUTPUT->header();

// Template context
$templatecontext = [
    'merchant' => $merchant,
    'withdrawals' => array_values($withdrawals),
    'withdraw_submitted' => $withdraw_submitted,
    'error_message' => $error_message,
    'success_message' => $success_message,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
    'formatted_balance' => number_format($merchant->balance, 2) . ' TRY',
    'has_withdrawals' => !empty($withdrawals),
];

// Tarihleri formatla
foreach ($templatecontext['withdrawals'] as &$withdrawal) {
    $withdrawal->formatted_date = userdate($withdrawal->timecreated, get_string('strftimedatetimeshort', 'core_langconfig'));
    $withdrawal->formatted_amount = number_format($withdrawal->amount, 2) . ' ' . $withdrawal->currency;
    $withdrawal->is_pending = ($withdrawal->status === 'pending');
    $withdrawal->is_completed = ($withdrawal->status === 'completed');
    $withdrawal->is_rejected = ($withdrawal->status === 'rejected');
}

// Render the template
echo $OUTPUT->render_from_template('local_lidio/withdraw', $templatecontext);

echo $OUTPUT->footer(); 