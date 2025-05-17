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

if (empty($merchants)) {
    echo $OUTPUT->notification(get_string('nomerchants', 'local_lidio'), \core\output\notification::NOTIFY_INFO);
} else {
    // Display merchants table
    $table = new html_table();
    $table->head = array(
        get_string('fullname', 'local_lidio'),
        get_string('email', 'local_lidio'),
        get_string('phone', 'local_lidio'),
        get_string('merchantstatus', 'local_lidio'),
        get_string('kycstatus', 'local_lidio'),
        get_string('actions', 'local_lidio')
    );
    $table->colclasses = array(
        'fullname', 'email', 'phone', 'status', 'kycstatus', 'actions'
    );
    
    foreach ($merchants as $merchant) {
        $userurl = new moodle_url('/user/profile.php', array('id' => $merchant->userid));
        $namelink = html_writer::link($userurl, fullname($merchant));
        
        // Status column
        if ($merchant->status === 'pending') {
            $statustext = get_string('pending', 'local_lidio');
            $statusclass = 'warning';
        } else if ($merchant->status === 'approved') {
            $statustext = get_string('approved', 'local_lidio');
            $statusclass = 'success';
        } else {
            $statustext = get_string('rejected', 'local_lidio');
            $statusclass = 'danger';
        }
        $status = html_writer::tag('span', $statustext, array('class' => 'badge badge-' . $statusclass));
        
        // KYC status column
        if ($merchant->kyc_status === 'pending') {
            $kycstatustext = get_string('pending', 'local_lidio');
            $kycstatusclass = 'warning';
        } else if ($merchant->kyc_status === 'approved') {
            $kycstatustext = get_string('approved', 'local_lidio');
            $kycstatusclass = 'success';
        } else {
            $kycstatustext = get_string('rejected', 'local_lidio');
            $kycstatusclass = 'danger';
        }
        $kycstatus = html_writer::tag('span', $kycstatustext, array('class' => 'badge badge-' . $kycstatusclass));
        
        // Actions
        $actions = array();
        
        // Merchant status actions
        if ($merchant->status === 'pending') {
            $approveurl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'approve', 'id' => $merchant->id));
            $actions[] = html_writer::link($approveurl, get_string('approve', 'local_lidio'), 
                            array('class' => 'btn btn-sm btn-success'));
            
            $rejecturl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'reject', 'id' => $merchant->id));
            $actions[] = html_writer::link($rejecturl, get_string('reject', 'local_lidio'), 
                            array('class' => 'btn btn-sm btn-danger'));
        }
        
        // KYC status actions
        if ($merchant->kyc_status === 'pending') {
            $kycapproveurl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'kyc_approve', 'id' => $merchant->id));
            $actions[] = html_writer::link($kycapproveurl, get_string('kycapprove', 'local_lidio'), 
                            array('class' => 'btn btn-sm btn-outline-success'));
            
            $kycrejecturl = new moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'kyc_reject', 'id' => $merchant->id));
            $actions[] = html_writer::link($kycrejecturl, get_string('kycreject', 'local_lidio'), 
                            array('class' => 'btn btn-sm btn-outline-danger'));
        }
        
        $viewurl = new moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $merchant->id));
        $actions[] = html_writer::link($viewurl, get_string('view', 'local_lidio'), 
                        array('class' => 'btn btn-sm btn-info'));
        
        $actionshtml = implode(' ', $actions);
        
        $row = new html_table_row(array(
            $namelink,
            $merchant->email,
            $merchant->phone,
            $status,
            $kycstatus,
            $actionshtml
        ));
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer(); 