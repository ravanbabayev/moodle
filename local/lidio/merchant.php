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
 * Merchant application page for Lidio payment system.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    print_error('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Set up the page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/lidio/merchant.php');
$PAGE->set_title(get_string('merchantapplication', 'local_lidio'));
$PAGE->set_heading(get_string('merchantapplication', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/lidio/styles.css');

// Check if user is already a merchant
$merchant = local_lidio_is_merchant();

// Setup appropriate content based on merchant status
if (!$merchant) {
    // User is not a merchant, show application form
    $templatecontext = [
        'error_message' => optional_param('error', '', PARAM_TEXT),
        'sesskey' => sesskey(),
        'returnurl' => $PAGE->url->out(false)
    ];
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('applyasmerchant', 'local_lidio'));
    
    // Render the application template
    echo $OUTPUT->render_from_template('local_lidio/merchant_application', $templatecontext);
    
    echo $OUTPUT->footer();
} else {
    // User is already a merchant, show status or redirect to KYC
    if ($merchant->kyc_status !== 'approved') {
        // KYC is not approved, redirect to KYC page
        redirect(
            $CFG->wwwroot . '/local/lidio/kyc.php?merchantid=' . $merchant->id,
            get_string('completekycverification', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    } else {
        // Set page title for dashboard
        $PAGE->set_title(get_string('merchantdashboard', 'local_lidio'));
        $PAGE->set_heading(get_string('merchantdashboard', 'local_lidio'));
        
        // Format the merchant data for template
        $merchant->timecreated_formatted = userdate($merchant->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
        
        // Generate status message
        $status_message = '';
        if ($merchant->status === 'pending') {
            $status_message = get_string('merchantstatus_pending', 'local_lidio');
        } else if ($merchant->status === 'approved') {
            $status_message = get_string('merchantstatus_approved', 'local_lidio');
        } else if ($merchant->status === 'rejected') {
            $status_message = get_string('merchantstatus_rejected', 'local_lidio');
        }
        
        // In a real implementation, we would fetch transactions from the database
        // For now, we'll use sample data if the merchant is approved
        $has_transactions = ($merchant->status === 'approved');
        $transactions = [];
        
        if ($has_transactions) {
            // Sample transaction data
            $transactions = [
                [
                    'id' => '1001',
                    'date' => userdate(time() - (3 * DAYSECS), get_string('strftimedatetimeshort', 'core_langconfig')),
                    'amount' => '$150.00',
                    'status' => get_string('approved', 'local_lidio')
                ],
                [
                    'id' => '1002',
                    'date' => userdate(time() - (2 * DAYSECS), get_string('strftimedatetimeshort', 'core_langconfig')),
                    'amount' => '$75.50',
                    'status' => get_string('approved', 'local_lidio')
                ],
                [
                    'id' => '1003',
                    'date' => userdate(time() - DAYSECS, get_string('strftimedatetimeshort', 'core_langconfig')),
                    'amount' => '$200.00',
                    'status' => get_string('pending', 'local_lidio')
                ]
            ];
        }
        
        // Prepare template context
        $templatecontext = [
            'status' => $merchant->status,
            'status_message' => $status_message,
            'status_is_pending' => ($merchant->status === 'pending'),
            'status_is_approved' => ($merchant->status === 'approved'),
            'status_is_rejected' => ($merchant->status === 'rejected'),
            'merchant' => $merchant,
            'has_transactions' => $has_transactions,
            'transactions' => $transactions,
            'sesskey' => sesskey()
        ];
        
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('merchantdashboard', 'local_lidio'));
        
        // Render the dashboard template
        echo $OUTPUT->render_from_template('local_lidio/merchant_dashboard', $templatecontext);
        
        echo $OUTPUT->footer();
    }
} 