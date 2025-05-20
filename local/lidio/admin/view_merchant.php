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
 * Admin page to view Lidio merchant details.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

global $PAGE, $DB, $USER, $OUTPUT;

// Import necessary classes
use core\output\notification;

// Check access
admin_externalpage_setup('local_lidio_merchants');

// Get merchant ID
$id = required_param('id', PARAM_INT);

// Get action parameters
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Get merchant record
$merchant = $DB->get_record('local_lidio_merchants', array('id' => $id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $merchant->userid), '*', MUST_EXIST);

// Process actions
if ($action) {
    if ($action === 'approve') {
        // Approve merchant
        if ($confirm) {
            $merchant->status = 'approved';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
            
            // Send notification to the user about KYC verification
            $message = get_string('merchantstatus_approved', 'local_lidio') . ' ' . get_string('completekycverification', 'local_lidio');
            \core\notification::success($message);
            redirect(new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id)));
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantdetails', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/view_merchant.php', 
                            array('action' => 'approve', 'id' => $id, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id));
            
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
            \core\notification::error($message);
            redirect(new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id)));
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantdetails', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/view_merchant.php', 
                            array('action' => 'reject', 'id' => $id, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id));
            
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
            $DB->set_field('local_lidio_documents', 'status', 'approved', array('merchantid' => $id));
            
            // Send notification to the user
            $message = get_string('kycstatus_approved', 'local_lidio');
            \core\notification::success($message);
            redirect(new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id)));
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantdetails', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/view_merchant.php', 
                            array('action' => 'kyc_approve', 'id' => $id, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id));
            
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
            $DB->set_field('local_lidio_documents', 'status', 'rejected', array('merchantid' => $id));
            
            // Send notification to the user
            $message = get_string('kycstatus_rejected', 'local_lidio');
            \core\notification::error($message);
            redirect(new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id)));
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('merchantdetails', 'local_lidio'));
            
            $confirmurl = new \moodle_url('/local/lidio/admin/view_merchant.php', 
                            array('action' => 'kyc_reject', 'id' => $id, 'confirm' => 1));
            $cancelurl = new \moodle_url('/local/lidio/admin/view_merchant.php', array('id' => $id));
            
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

// Get documents
$documents = $DB->get_records('local_lidio_documents', array('merchantid' => $id));

// Start page output
echo $OUTPUT->header();

// Prepare template context
$templatecontext = [];

// Merchant data with formatted values
$templatecontext['merchant'] = [
    'id' => $merchant->id,
    'userid' => $merchant->userid,
    'company_type' => $merchant->company_type,
    'company_type_text' => get_string($merchant->company_type, 'local_lidio'),
    'company_name' => $merchant->company_name,
    'email' => $merchant->email,
    'phone' => $merchant->phone,
    'website' => $merchant->website,
    'social_media' => $merchant->social_media,
    'business_area' => $merchant->business_area,
    'monthly_volume' => $merchant->monthly_volume,
    'monthly_volume_text' => format_monthly_volume($merchant->monthly_volume),
    'payment_methods' => $merchant->payment_methods,
    'payment_methods_array' => explode(',', $merchant->payment_methods),
    'iban' => $merchant->iban,
    'account_holder' => $merchant->account_holder,
    'bank_name' => $merchant->bank_name,
    'formatted_date' => userdate($merchant->timecreated, get_string('strftimedatetime', 'langconfig')),
    
    // Status flags
    'status' => $merchant->status,
    'kyc_status' => $merchant->kyc_status,
    'status_text' => get_string($merchant->status, 'local_lidio'),
    'kyc_status_text' => get_string($merchant->kyc_status, 'local_lidio'),
    'status_pending' => ($merchant->status === 'pending'),
    'status_approved' => ($merchant->status === 'approved'),
    'status_rejected' => ($merchant->status === 'rejected'),
    'kyc_status_pending' => ($merchant->kyc_status === 'pending'),
    'kyc_status_approved' => ($merchant->kyc_status === 'approved'),
    'kyc_status_rejected' => ($merchant->kyc_status === 'rejected')
];

// User data
$templatecontext['user'] = [
    'id' => $user->id,
    'fullname' => fullname($user),
    'email' => $user->email,
    'picture' => $OUTPUT->user_picture($user, ['size' => 100, 'link' => false, 'class' => 'profile-image'])
];

// Prepare documents
$templatecontext['has_documents'] = !empty($documents);
if (!empty($documents)) {
    $templatecontext['documents'] = [];
    foreach ($documents as $document) {
        $document_data = [
            'id' => $document->id,
            'type_text' => get_string($document->type, 'local_lidio'),
            'filename' => $document->filename,
            'status' => $document->status,
            'status_text' => get_string($document->status, 'local_lidio'),
            'status_pending' => ($document->status === 'pending'),
            'status_approved' => ($document->status === 'approved'),
            'status_rejected' => ($document->status === 'rejected'),
            'formatted_date' => userdate($document->timecreated, get_string('strftimedatetime', 'langconfig')),
            'download_url' => new \moodle_url('/local/lidio/admin/download.php', ['id' => $document->id])
        ];
        $templatecontext['documents'][] = $document_data;
    }
}

// URLs
$templatecontext['back_url'] = new \moodle_url('/local/lidio/admin/merchants.php');
$templatecontext['profile_url'] = new \moodle_url('/user/profile.php', ['id' => $user->id]);
$templatecontext['show_approve'] = ($merchant->status === 'pending');
$templatecontext['show_kyc_approve'] = ($merchant->kyc_status === 'pending');
$templatecontext['approve_url'] = new \moodle_url('/local/lidio/admin/view_merchant.php', ['action' => 'approve', 'id' => $merchant->id]);
$templatecontext['reject_url'] = new \moodle_url('/local/lidio/admin/view_merchant.php', ['action' => 'reject', 'id' => $merchant->id]);
$templatecontext['kyc_approve_url'] = new \moodle_url('/local/lidio/admin/view_merchant.php', ['action' => 'kyc_approve', 'id' => $merchant->id]);
$templatecontext['kyc_reject_url'] = new \moodle_url('/local/lidio/admin/view_merchant.php', ['action' => 'kyc_reject', 'id' => $merchant->id]);

// Language strings
$templatecontext['strings'] = [
    'back' => get_string('back', 'local_lidio'),
    'merchantdetails' => get_string('merchantdetails', 'local_lidio'),
    'merchantdetailsdesc' => get_string('merchantdetails', 'local_lidio') . ' - ' . $merchant->company_name,
    'userprofile' => get_string('userprofile', 'local_lidio'),
    'email' => get_string('email', 'local_lidio'),
    'companytype' => get_string('companytype', 'local_lidio'),
    'companyname' => get_string('companyname', 'local_lidio'),
    'phone' => get_string('phone', 'local_lidio'),
    'status' => get_string('status', 'local_lidio'),
    'kycstatus' => get_string('kycstatus', 'local_lidio'),
    'applicationdate' => get_string('applicationdate', 'local_lidio'),
    'businessinformation' => get_string('businessinformation', 'local_lidio'),
    'website' => get_string('website', 'local_lidio'),
    'socialmedialinks' => get_string('socialmedialinks', 'local_lidio'),
    'businessarea' => get_string('businessarea', 'local_lidio'),
    'monthlysalesvolume' => get_string('monthlysalesvolume', 'local_lidio'),
    'paymentmethods' => get_string('paymentmethods', 'local_lidio'),
    'bankinformation' => get_string('bankinformation', 'local_lidio'),
    'accountholder' => get_string('accountholder', 'local_lidio'),
    'bankname' => get_string('bankname', 'local_lidio'),
    'iban' => get_string('iban', 'local_lidio'),
    'documents' => get_string('documents', 'local_lidio'),
    'documenttype' => get_string('documenttype', 'local_lidio'),
    'filename' => get_string('filename', 'local_lidio'),
    'uploaddate' => get_string('uploaddate', 'local_lidio'), 
    'approve' => get_string('approve', 'local_lidio'),
    'reject' => get_string('reject', 'local_lidio'),
    'kycapprove' => get_string('kycapprove', 'local_lidio'),
    'kycreject' => get_string('kycreject', 'local_lidio'),
    'download' => get_string('download', 'local_lidio'),
    'actions' => get_string('actions', 'local_lidio')
];

// Render template
echo $OUTPUT->render_from_template('local_lidio/admin_merchant_view', $templatecontext);

echo $OUTPUT->footer();

/**
 * Format monthly volume to human-readable text
 *
 * @param string $volume Volume code
 * @return string Formatted text
 */
function format_monthly_volume($volume) {
    switch ($volume) {
        case '0-5000':
            return '0 - 5.000 ₺';
        case '5000-20000':
            return '5.000 - 20.000 ₺';
        case '20000-50000':
            return '20.000 - 50.000 ₺';
        case '50000-100000':
            return '50.000 - 100.000 ₺';
        case '100000+':
            return '100.000+ ₺';
        default:
            return $volume;
    }
} 