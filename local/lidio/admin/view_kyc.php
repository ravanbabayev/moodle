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
 * Admin page to view and manage Lidio merchant KYC documents.
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
use moodle_url;

global $PAGE, $DB, $USER, $OUTPUT;

// Check access
admin_externalpage_setup('local_lidio_merchants');

// Get merchant ID
$id = required_param('id', PARAM_INT);

// Get action parameters
$action = optional_param('action', '', PARAM_ALPHA);
$docid = optional_param('docid', 0, PARAM_INT);

// Get merchant record
$merchant = $DB->get_record('local_lidio_merchants', array('id' => $id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $merchant->userid), '*', MUST_EXIST);

// Process document actions
if ($action && $docid) {
    $document = $DB->get_record('local_lidio_documents', array('id' => $docid, 'merchantid' => $id), '*', MUST_EXIST);

    if ($action === 'approve') {
        $document->status = 'approved';
        $document->timemodified = time();
        $DB->update_record('local_lidio_documents', $document);

        // Check if all documents are approved
        $all_approved = true;
        $documents = $DB->get_records('local_lidio_documents', array('merchantid' => $id));
        if (count($documents) >= 3) {
            foreach ($documents as $doc) {
                if ($doc->status !== 'approved') {
                    $all_approved = false;
                    break;
                }
            }
        }

        // If all documents are approved, update merchant KYC status
        if ($all_approved) {
            $merchant->kyc_status = 'approved';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);

            \core\notification::success(get_string('document_approved_all', 'local_lidio'));
            redirect(new \moodle_url('/local/lidio/admin/view_kyc.php', array('id' => $id)));
        } else {
            \core\notification::success(get_string('document_approved', 'local_lidio'));
            redirect(new \moodle_url('/local/lidio/admin/view_kyc.php', array('id' => $id)));
        }
    } else if ($action === 'reject') {
        $document->status = 'rejected';
        $document->timemodified = time();
        $DB->update_record('local_lidio_documents', $document);

        // Update merchant KYC status to rejected
        $merchant->kyc_status = 'rejected';
        $merchant->timemodified = time();
        $DB->update_record('local_lidio_merchants', $merchant);

        \core\notification::error(get_string('document_rejected', 'local_lidio'));
        redirect(new \moodle_url('/local/lidio/admin/view_kyc.php', array('id' => $id)));
    }
}

// Get documents
$documents = $DB->get_records('local_lidio_documents', array('merchantid' => $id));

// Start page output
echo $OUTPUT->header();

// Prepare template context
$templatecontext = [];

// Merchant data
$templatecontext['merchant'] = [
    'id' => $merchant->id,
    'company_name' => $merchant->company_name,
    'fullname' => fullname($user),
    'picture' => $OUTPUT->user_picture($user, ['size' => 50, 'link' => false, 'class' => 'profile-image']),

    // Status flags
    'kyc_status' => $merchant->kyc_status,
    'kyc_status_text' => get_string($merchant->kyc_status, 'local_lidio'),
    'kyc_status_pending' => ($merchant->kyc_status === 'pending'),
    'kyc_status_approved' => ($merchant->kyc_status === 'approved'),
    'kyc_status_rejected' => ($merchant->kyc_status === 'rejected')
];

// Prepare documents
$templatecontext['has_documents'] = !empty($documents);
if (!empty($documents)) {
    $templatecontext['documents'] = [];
    foreach ($documents as $document) {
        $document_data = [
            'id' => $document->id,
            'type' => $document->type,
            'type_text' => get_string($document->type, 'local_lidio'),
            'filename' => $document->filename,
            'status' => $document->status,
            'status_text' => get_string($document->status, 'local_lidio'),
            'status_pending' => ($document->status === 'pending'),
            'status_approved' => ($document->status === 'approved'),
            'status_rejected' => ($document->status === 'rejected'),
            'can_approve' => ($document->status !== 'approved'),
            'can_reject' => ($document->status !== 'rejected'),
            'formatted_date' => userdate($document->timecreated, get_string('strftimedatetime', 'langconfig')),
            'download_url' => new \moodle_url('/local/lidio/admin/download.php', ['id' => $document->id]),
            'approve_url' => new \moodle_url('/local/lidio/admin/view_kyc.php', ['action' => 'approve', 'id' => $id, 'docid' => $document->id]),
            'reject_url' => new \moodle_url('/local/lidio/admin/view_kyc.php', ['action' => 'reject', 'id' => $id, 'docid' => $document->id])
        ];

        // For document display
        $ext = pathinfo($document->filename, PATHINFO_EXTENSION);
        $document_data['is_image'] = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif']);
        $document_data['is_pdf'] = strtolower($ext) === 'pdf';

        if ($document_data['is_image']) {
            $document_data['preview_url'] = new \moodle_url('/local/lidio/admin/download.php', ['id' => $document->id]);
        }

        $templatecontext['documents'][] = $document_data;
    }
}

// URLs
$templatecontext['back_url'] = new \moodle_url('/local/lidio/admin/merchants.php');
$templatecontext['merchant_url'] = new \moodle_url('/local/lidio/admin/view_merchant.php', ['id' => $id]);

// Language strings
$templatecontext['strings'] = [
    'kyc_documents' => get_string('kyc_documents', 'local_lidio'),
    'merchant_kyc' => get_string('merchant_kyc', 'local_lidio'),
    'back' => get_string('back', 'local_lidio'),
    'merchant_details' => get_string('merchantdetails', 'local_lidio'),
    'documents' => get_string('documents', 'local_lidio'),
    'documenttype' => get_string('documenttype', 'local_lidio'),
    'filename' => get_string('filename', 'local_lidio'),
    'preview' => get_string('preview', 'local_lidio'),
    'uploaddate' => get_string('uploaddate', 'local_lidio'),
    'status' => get_string('status', 'local_lidio'),
    'kycstatus' => get_string('kycstatus', 'local_lidio'),
    'actions' => get_string('actions', 'local_lidio'),
    'approve' => get_string('approve', 'local_lidio'),
    'reject' => get_string('reject', 'local_lidio'),
    'download' => get_string('download', 'local_lidio'),
    'view' => get_string('view', 'local_lidio'),
    'no_documents' => get_string('no_documents', 'local_lidio')
];

// Render template
echo $OUTPUT->render_from_template('local_lidio/admin_kyc_view', $templatecontext);

echo $OUTPUT->footer();
