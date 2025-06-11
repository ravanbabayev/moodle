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
 * Admin page to manage Lidio transactions.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check access
admin_externalpage_setup('local_lidio_transactions');

// Set up the page
$title = get_string('transactionmanagement', 'local_lidio');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Get all transactions
$sql = "SELECT t.*, m.company_name, u.firstname, u.lastname, pl.title as payment_link_title
        FROM {local_lidio_transactions} t
        LEFT JOIN {local_lidio_merchants} m ON t.merchant_id = m.id
        LEFT JOIN {user} u ON m.userid = u.id
        LEFT JOIN {local_lidio_payment_links} pl ON t.payment_link_id = pl.id
        ORDER BY t.timecreated DESC";

$transactions = $DB->get_records_sql($sql);

if (empty($transactions)) {
    echo html_writer::tag('p', get_string('notransactions', 'local_lidio'), ['class' => 'alert alert-info']);
} else {
    // Create table
    $table = new html_table();
    $table->head = [
        get_string('id', 'local_lidio'),
        get_string('merchant', 'local_lidio'),
        get_string('paymentlink', 'local_lidio'),
        get_string('amount', 'local_lidio'),
        get_string('status', 'local_lidio'),
        get_string('date', 'local_lidio'),
        get_string('actions', 'local_lidio')
    ];
    
    foreach ($transactions as $transaction) {
        $merchantname = $transaction->company_name ?: ($transaction->firstname . ' ' . $transaction->lastname);
        $amount = number_format($transaction->amount, 2) . ' TL';
        $date = userdate($transaction->timecreated, get_string('strftimedate'));
        
        $statusclass = '';
        switch ($transaction->status) {
            case 'completed':
                $statusclass = 'badge badge-success';
                break;
            case 'failed':
                $statusclass = 'badge badge-danger';
                break;
            case 'pending':
                $statusclass = 'badge badge-warning';
                break;
            default:
                $statusclass = 'badge badge-secondary';
        }
        
        $status = html_writer::tag('span', ucfirst($transaction->status), ['class' => $statusclass]);
        
        $actions = html_writer::link(
            new moodle_url('/local/lidio/admin/transaction_detail.php', ['id' => $transaction->id]),
            get_string('view', 'local_lidio'),
            ['class' => 'btn btn-sm btn-primary']
        );
        
        $table->data[] = [
            $transaction->id,
            $merchantname,
            $transaction->payment_link_title ?: '-',
            $amount,
            $status,
            $date,
            $actions
        ];
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer(); 