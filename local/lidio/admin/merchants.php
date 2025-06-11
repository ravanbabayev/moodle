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
use core\notification;
use core_table\output\html_table;
use core\output\html_writer;

// Check access
require_login();
require_capability('local/lidio:managemerchants', \context_system::instance());

// Set up the page
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/lidio/admin/merchants.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('merchantmanagement', 'local_lidio'));
$PAGE->set_heading(get_string('merchantmanagement', 'local_lidio'));

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
            notification::success($message);
            redirect(new \moodle_url('/local/lidio/admin/merchants.php'), $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'approve', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/merchants.php');
            
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
            notification::error($message);
            redirect(new \moodle_url('/local/lidio/admin/merchants.php'), $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'reject', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/merchants.php');
            
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
            notification::success($message);
            redirect(new \moodle_url('/local/lidio/admin/merchants.php'), $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'kyc_approve', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/merchants.php');
            
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
            notification::error($message);
            redirect(new \moodle_url('/local/lidio/admin/merchants.php'), $message);
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/merchants.php', 
                            array('action' => 'kyc_reject', 'id' => $mid, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/merchants.php');
            
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

// Display the page header
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('merchantmanagement', 'local_lidio'));

// Get all merchants with user information
$sql = "SELECT m.*, u.firstname, u.lastname, u.email
          FROM {local_lidio_merchants} m
          JOIN {user} u ON m.userid = u.id
      ORDER BY m.timecreated DESC";

$merchants = $DB->get_records_sql($sql);

// Display merchants table
if ($merchants) {
    $table = new html_table();
    $table->head = array(
        get_string('merchantname', 'local_lidio'),
        get_string('email', 'local_lidio'),
        get_string('businessname', 'local_lidio'),
        get_string('status', 'local_lidio'),
        get_string('kycstatus', 'local_lidio'),
        get_string('timecreated', 'local_lidio'),
        get_string('actions', 'local_lidio')
    );
    $table->attributes['class'] = 'admintable generaltable';
    $table->data = array();

    foreach ($merchants as $merchant) {
        $row = array();
        
        // Merchant name (user's full name)
        $row[] = fullname($merchant);
        
        // Email
        $row[] = $merchant->email;
        
        // Business name
        $row[] = $merchant->company_name;
        
        // Status
        $statusstring = get_string('status_' . $merchant->status, 'local_lidio');
        $statusclass = 'badge badge-';
        switch ($merchant->status) {
            case 'pending':
                $statusclass .= 'warning';
                break;
            case 'approved':
                $statusclass .= 'success';
                break;
            case 'rejected':
                $statusclass .= 'danger';
                break;
            default:
                $statusclass .= 'secondary';
        }
        $row[] = html_writer::tag('span', $statusstring, array('class' => $statusclass));
        
        // KYC Status
        $kycstatusstring = get_string('status_' . $merchant->kyc_status, 'local_lidio');
        $kycstatusclass = 'badge badge-';
        switch ($merchant->kyc_status) {
            case 'pending':
                $kycstatusclass .= 'warning';
                break;
            case 'approved':
                $kycstatusclass .= 'success';
                break;
            case 'rejected':
                $kycstatusclass .= 'danger';
                break;
            default:
                $kycstatusclass .= 'secondary';
        }
        $row[] = html_writer::tag('span', $kycstatusstring, array('class' => $kycstatusclass));
        
        // Time created
        $row[] = userdate($merchant->timecreated);
        
        // Actions
        $actions = array();
        
        // Edit link
        $editurl = new \moodle_url('/local/lidio/admin/edit_merchant.php', array('id' => $merchant->id));
        $actions[] = html_writer::link($editurl, get_string('edit'), array('class' => 'btn btn-sm btn-secondary'));
        
        // Status actions
        if ($merchant->status === 'pending') {
            $approveurl = new \moodle_url('/local/lidio/admin/merchants.php', array('action' => 'approve', 'id' => $merchant->id));
            $actions[] = html_writer::link($approveurl, get_string('approve', 'local_lidio'), array('class' => 'btn btn-sm btn-success'));
            
            $rejecturl = new \moodle_url('/local/lidio/admin/merchants.php', array('action' => 'reject', 'id' => $merchant->id));
            $actions[] = html_writer::link($rejecturl, get_string('reject', 'local_lidio'), array('class' => 'btn btn-sm btn-danger'));
        }
        
        // KYC actions
        if ($merchant->kyc_status === 'pending') {
            $kycapproveurl = new \moodle_url('/local/lidio/admin/merchants.php', array('action' => 'kyc_approve', 'id' => $merchant->id));
            $actions[] = html_writer::link($kycapproveurl, get_string('kyc_approve', 'local_lidio'), array('class' => 'btn btn-sm btn-success'));
            
            $kycrejecturl = new \moodle_url('/local/lidio/admin/merchants.php', array('action' => 'kyc_reject', 'id' => $merchant->id));
            $actions[] = html_writer::link($kycrejecturl, get_string('kyc_reject', 'local_lidio'), array('class' => 'btn btn-sm btn-danger'));
        }
        
        $row[] = implode(' ', $actions);
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nomerchants', 'local_lidio'), 'info');
}

// Display the page footer
echo $OUTPUT->footer(); 