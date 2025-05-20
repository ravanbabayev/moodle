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
 * Admin page to manage Lidio merchants.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/weblib.php');

// Import necessary classes
use moodle_url;

// Check access
admin_externalpage_setup('local_lidio_merchants');

// Get action parameters
$action = optional_param('action', '', PARAM_ALPHA);
$mid = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Process actions
if ($action && $mid) {
    $merchant = $DB->get_record('local_lidio_merchants', array('id' => $mid), '*', MUST_EXIST);
    $user = $DB->get_record('user', array('id' => $merchant->userid), '*', MUST_EXIST);
    
    if ($action === 'approve') {
        // Approve merchant
        if ($confirm) {
            $merchant->status = 'approved';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
            
            // Send notification to the user
            $message = get_string('merchantstatus_approved', 'local_lidio');
            $notification = new \core\output\notification($message, \core\output\notification::NOTIFY_SUCCESS);
            redirect($CFG->wwwroot . '/local/lidio/admin/merchants.php', $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'approve', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new moodle_url('/local/lidio/admin/merchants.php');
            
            echo $OUTPUT->confirm(
                get_string('confirmapprovemerchant', 'local_lidio', fullname($user)),
                $confirmurl,
                $cancelurl
            );
            
            echo $OUTPUT->footer();
            exit;
        }
    } else if ($action === 'reject') {
        // Reject merchant
        if ($confirm) {
            $merchant->status = 'rejected';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
            
            // Send notification to the user
            $message = get_string('merchantstatus_rejected', 'local_lidio');
            $notification = new \core\output\notification($message, \core\output\notification::NOTIFY_ERROR);
            redirect($CFG->wwwroot . '/local/lidio/admin/merchants.php', $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'reject', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new moodle_url('/local/lidio/admin/merchants.php');
            
            echo $OUTPUT->confirm(
                get_string('confirmrejectmerchant', 'local_lidio', fullname($user)),
                $confirmurl,
                $cancelurl
            );
            
            echo $OUTPUT->footer();
            exit;
        }
    } else if ($action === 'kyc_approve') {
        // Approve KYC
        if ($confirm) {
            $merchant->kyc_status = 'approved';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
            
            // Update document status as well
            $DB->set_field('local_lidio_documents', 'status', 'approved', array('merchantid' => $mid));
            
            // Send notification to the user
            $message = get_string('kycstatus_approved', 'local_lidio');
            $notification = new \core\output\notification($message, \core\output\notification::NOTIFY_SUCCESS);
            redirect($CFG->wwwroot . '/local/lidio/admin/merchants.php', $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'kyc_approve', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new moodle_url('/local/lidio/admin/merchants.php');
            
            echo $OUTPUT->confirm(
                get_string('confirmkycapprove', 'local_lidio', fullname($user)),
                $confirmurl,
                $cancelurl
            );
            
            echo $OUTPUT->footer();
            exit;
        }
    } else if ($action === 'kyc_reject') {
        // Reject KYC
        if ($confirm) {
            $merchant->kyc_status = 'rejected';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
            
            // Update document status as well
            $DB->set_field('local_lidio_documents', 'status', 'rejected', array('merchantid' => $mid));
            
            // Send notification to the user
            $message = get_string('kycstatus_rejected', 'local_lidio');
            $notification = new \core\output\notification($message, \core\output\notification::NOTIFY_ERROR);
            redirect($CFG->wwwroot . '/local/lidio/admin/merchants.php', $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'kyc_reject', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new moodle_url('/local/lidio/admin/merchants.php');
            
            echo $OUTPUT->confirm(
                get_string('confirmkycreject', 'local_lidio', fullname($user)),
                $confirmurl,
                $cancelurl
            );
            
            echo $OUTPUT->footer();
            exit;
        }
    }
}

// Display merchants list
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));

// Fetch all merchants
$sql = "SELECT m.*, u.firstname, u.lastname, u.email 
        FROM {local_lidio_merchants} m 
        JOIN {user} u ON u.id = m.userid 
        ORDER BY m.timecreated DESC";

$merchants = $DB->get_records_sql($sql);

// Prepare data for the template
$templatecontext = [];
$templatecontext['has_merchants'] = !empty($merchants);

if (!empty($merchants)) {
    $templatecontext['merchants'] = [];
    foreach ($merchants as $merchant) {
        // Get user data
        $user = $DB->get_record('user', ['id' => $merchant->userid]);
        
        // Build merchant data
        $merchantdata = [
            'id' => $merchant->id,
            'fullname' => fullname($user),
            'email' => $merchant->email,
            'company_name' => $merchant->company_name,
            'phone' => $merchant->phone,
            'location' => '', // Not currently stored
            'profile_image_url' => $OUTPUT->user_picture($user, ['size' => 35, 'link' => false, 'class' => '']),
            'profile_url' => new moodle_url('/user/profile.php', ['id' => $merchant->userid]),
            'formatted_date' => userdate($merchant->timecreated, get_string('strftimedatetime', 'langconfig')),
            
            // Status
            'status_text' => get_string($merchant->status, 'local_lidio'),
            'status_pending' => ($merchant->status === 'pending'),
            'status_approved' => ($merchant->status === 'approved'),
            'status_rejected' => ($merchant->status === 'rejected'),
            
            // KYC Status
            'kyc_status_text' => get_string($merchant->kyc_status, 'local_lidio'),
            'kyc_status_pending' => ($merchant->kyc_status === 'pending'),
            'kyc_status_approved' => ($merchant->kyc_status === 'approved'),
            'kyc_status_rejected' => ($merchant->kyc_status === 'rejected'),
            
            // Action URLs
            'show_approve' => ($merchant->status === 'pending'),
            'show_kyc_approve' => ($merchant->kyc_status === 'pending'),
            'approve_url' => new moodle_url('/local/lidio/admin/merchants.php', ['action' => 'approve', 'id' => $merchant->id]),
            'reject_url' => new moodle_url('/local/lidio/admin/merchants.php', ['action' => 'reject', 'id' => $merchant->id]),
            'kyc_approve_url' => new moodle_url('/local/lidio/admin/merchants.php', ['action' => 'kyc_approve', 'id' => $merchant->id]),
            'kyc_reject_url' => new moodle_url('/local/lidio/admin/merchants.php', ['action' => 'kyc_reject', 'id' => $merchant->id]),
            'view_url' => new moodle_url('/local/lidio/admin/view_merchant.php', ['id' => $merchant->id]),
            'view_kyc_url' => new moodle_url('/local/lidio/admin/view_kyc.php', ['id' => $merchant->id])
        ];
        
        $templatecontext['merchants'][] = $merchantdata;
    }
}

// Language strings
$templatecontext['strings'] = [
    'merchantmanagement' => get_string('merchantmanagement', 'local_lidio'),
    'merchantmanagementdesc' => get_string('merchantmanagement', 'local_lidio') . ' - ' . get_string('pluginname', 'local_lidio'),
    'merchant' => get_string('fullname', 'local_lidio'),
    'companyname' => get_string('companyname', 'local_lidio'),
    'contact' => get_string('contact', 'local_lidio'),
    'applicationdate' => get_string('applicationdate', 'local_lidio'),
    'status' => get_string('status', 'local_lidio'),
    'kycstatus' => get_string('kycstatus', 'local_lidio'),
    'actions' => get_string('actions', 'local_lidio'),
    'approve' => get_string('approve', 'local_lidio'),
    'reject' => get_string('reject', 'local_lidio'),
    'kycapprove' => get_string('kycapprove', 'local_lidio'),
    'kycreject' => get_string('kycreject', 'local_lidio'),
    'view' => get_string('view', 'local_lidio'),
    'view_kyc' => get_string('view_kyc', 'local_lidio'),
    'nomerchants' => get_string('nomerchants', 'local_lidio'),
    'nomerchantsdesc' => get_string('nomerchants', 'local_lidio')
];

// Render the template
echo $OUTPUT->render_from_template('local_lidio/admin_merchants_list', $templatecontext);

echo $OUTPUT->footer(); 