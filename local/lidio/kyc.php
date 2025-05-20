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
 * KYC verification page for Lidio payment system.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/filelib.php');

// Check if the plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    throw new \core\exception\moodle_exception('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Process file actions
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'delete') {
    $documentid = required_param('id', PARAM_INT);
    $document = $DB->get_record('local_lidio_documents', ['id' => $documentid], '*', MUST_EXIST);
    
    // Check that this document belongs to the current user
    $merchant = $DB->get_record('local_lidio_merchants', ['id' => $document->merchantid], '*', MUST_EXIST);
    if ($merchant->userid != $USER->id) {
        throw new \core\exception\moodle_exception('notauthorized', 'local_lidio');
    }
    
    // Delete the document file
    if (file_exists($document->filepath)) {
        unlink($document->filepath);
    }
    
    // Delete the document record
    $DB->delete_records('local_lidio_documents', ['id' => $documentid]);
    
    // Redirect to the KYC page
    redirect($CFG->wwwroot . '/local/lidio/kyc.php', get_string('documentdeleted', 'local_lidio'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Set up the page
$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/lidio/kyc.php');
$PAGE->set_title(get_string('kycverification', 'local_lidio'));
$PAGE->set_heading(get_string('kycverification', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/lidio/styles.css');

// Add JavaScript
$PAGE->requires->js('/local/lidio/scripts.js');

// Add Tailwind CSS CDN
$tailwind_url = new moodle_url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
$PAGE->requires->css($tailwind_url);

// Check if user is a merchant
$merchant = local_lidio_is_merchant();
if (!$merchant) {
    // User is not a merchant, redirect to merchant application
    redirect(
        $CFG->wwwroot . '/local/lidio/merchant_application.php',
        get_string('notamerchant', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// If merchant has completed KYC and it's approved, redirect to dashboard
if ($merchant->kyc_status === 'approved') {
    redirect(
        $CFG->wwwroot . '/local/lidio/merchant_dashboard.php',
        get_string('kycstatus_approved', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Define document types for KYC verification
$document_types = [
    [
        'type' => 'passport',
        'name' => get_string('passport', 'local_lidio'),
        'description' => get_string('passport_desc', 'local_lidio')
    ],
    [
        'type' => 'id_card',
        'name' => get_string('id_card', 'local_lidio'),
        'description' => get_string('id_card_desc', 'local_lidio')
    ],
    [
        'type' => 'driving_license',
        'name' => get_string('driving_license', 'local_lidio'),
        'description' => get_string('driving_license_desc', 'local_lidio')
    ],
    [
        'type' => 'address_proof',
        'name' => get_string('address_proof', 'local_lidio'),
        'description' => get_string('address_proof_desc', 'local_lidio')
    ],
    [
        'type' => 'company_registration',
        'name' => get_string('company_registration', 'local_lidio'),
        'description' => get_string('company_registration_desc', 'local_lidio')
    ]
];

// Get the list of document types
$document_type_keys = array_column($document_types, 'type');

// Process file uploads
$errors = [];
if ($data = data_submitted() && confirm_sesskey()) {
    $files_uploaded = false;
    
    // Process each document type
    foreach ($document_type_keys as $type) {
        $file_key = 'document_' . $type;
        
        // Check if a file was uploaded for this document type
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] != UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$file_key];
            
            // Check for errors
            if ($file['error'] != UPLOAD_ERR_OK) {
                $errors[$file_key] = get_string('fileuploaderror', 'local_lidio');
                continue;
            }
            
            // Validate file size
            if ($file['size'] > 5242880) { // 5MB
                $errors[$file_key] = get_string('filetoolarge', 'local_lidio');
                continue;
            }
            
            // Validate file type
            $mime_type = mime_content_type($file['tmp_name']);
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!in_array($mime_type, $allowed_types)) {
                $errors[$file_key] = get_string('invalidfiletype', 'local_lidio');
                continue;
            }
            
            // Create directory if it doesn't exist
            $upload_dir = $CFG->dataroot . '/lidio_documents/' . $merchant->id;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate a unique filename
            $filename = $file['name'];
            $unique_filename = uniqid() . '_' . $filename;
            $filepath = $upload_dir . '/' . $unique_filename;
            
            // Move the uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Check if document already exists
                $existing = $DB->get_record('local_lidio_documents', ['merchantid' => $merchant->id, 'type' => $type]);
                
                // Prepare document data
                $document = new stdClass();
                $document->merchantid = $merchant->id;
                $document->type = $type;
                $document->filepath = $filepath;
                $document->filename = $filename;
                $document->status = 'pending';
                $document->timecreated = time();
                $document->timemodified = time();
                
                if ($existing) {
                    // Update existing document
                    $document->id = $existing->id;
                    $DB->update_record('local_lidio_documents', $document);
                    
                    // Delete the old file
                    if (file_exists($existing->filepath)) {
                        unlink($existing->filepath);
                    }
                } else {
                    // Insert new document
                    $DB->insert_record('local_lidio_documents', $document);
                }
                
                $files_uploaded = true;
            } else {
                $errors[$file_key] = get_string('fileuploaderror', 'local_lidio');
            }
        }
    }
    
    // Update merchant KYC status if files were uploaded
    if ($files_uploaded) {
        // Update merchant KYC status to pending if it was rejected
        if ($merchant->kyc_status === 'rejected') {
            $merchant->kyc_status = 'pending';
            $merchant->timemodified = time();
            $DB->update_record('local_lidio_merchants', $merchant);
        }
        
        // Redirect with success message
        redirect($CFG->wwwroot . '/local/lidio/kyc.php', get_string('kycsubmitted', 'local_lidio'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Generate status message based on merchant KYC status
$status_message = '';
if ($merchant->kyc_status === 'pending') {
    $status_message = get_string('kycstatus_pending', 'local_lidio');
} else if ($merchant->kyc_status === 'rejected') {
    $status_message = get_string('kycstatus_rejected', 'local_lidio');
}

// Get existing documents
$existing_documents = [];
$documents = $DB->get_records('local_lidio_documents', ['merchantid' => $merchant->id]);
foreach ($documents as $document) {
    $existing_documents[$document->type] = [
        'id' => $document->id,
        'type' => $document->type,
        'filename' => $document->filename,
        'status' => $document->status,
        'status_text' => get_string($document->status, 'local_lidio'),
        'timeformatted' => userdate($document->timecreated, get_string('strftimedatetime', 'langconfig')),
        'deleteurl' => $CFG->wwwroot . '/local/lidio/kyc.php?action=delete&id=' . $document->id
    ];
}

// Prepare document types with additional info for template
$template_document_types = [];
$has_primary_id = false;
$primary_id_types = ['passport', 'id_card', 'driving_license'];

// Check if user already has one of the primary ID documents
foreach ($primary_id_types as $primary_type) {
    if (isset($existing_documents[$primary_type])) {
        $has_primary_id = true;
        break;
    }
}

foreach ($document_types as $document) {
    $type = $document['type'];
    $doc_data = $document;
    
    // Identify if this is a primary ID document
    $is_primary_id = in_array($type, $primary_id_types);
    $doc_data['is_primary_id'] = $is_primary_id;
    
    // Check if document exists
    $has_document = isset($existing_documents[$type]);
    $doc_data['has_document'] = $has_document;
    
    // If primary ID is already uploaded, hide the other primary IDs
    if ($is_primary_id && $has_primary_id && !$has_document) {
        $doc_data['hide_document'] = true;
    } else {
        $doc_data['hide_document'] = false;
    }
    
    // Mark document as optional if it's company registration
    $doc_data['is_optional'] = ($type === 'company_registration');
    
    if ($has_document) {
        $doc_data['filename'] = $existing_documents[$type]['filename'];
        $doc_data['status'] = $existing_documents[$type]['status'];
        $doc_data['deleteurl'] = $existing_documents[$type]['deleteurl'];
        $doc_data['is_pending'] = ($existing_documents[$type]['status'] === 'pending');
        $doc_data['is_approved'] = ($existing_documents[$type]['status'] === 'approved');
        $doc_data['is_rejected'] = ($existing_documents[$type]['status'] === 'rejected');
        
        // Only show delete button if the document is rejected
        // For primary IDs, don't allow delete after upload
        $doc_data['can_delete'] = !$is_primary_id && $existing_documents[$type]['status'] === 'rejected';
    }
    
    // Can upload document if it doesn't exist or status is rejected
    // For primary IDs, if one is already uploaded, others can't be uploaded
    if ($is_primary_id && $has_primary_id && !$has_document) {
        $doc_data['can_upload'] = false;
    } else {
        $doc_data['can_upload'] = !$has_document || $existing_documents[$type]['status'] === 'rejected';
    }
    
    // Check for errors
    $doc_data['has_error'] = isset($errors['document_' . $type]);
    if ($doc_data['has_error']) {
        $doc_data['error_message'] = $errors['document_' . $type];
    }
    
    $template_document_types[] = $doc_data;
}

// Determine if all required documents are uploaded
$all_documents_submitted = false;
$has_address_proof = isset($existing_documents['address_proof']);
if ($has_primary_id && $has_address_proof) {
    $all_documents_submitted = true;
}

// Prepare the template context
$templatecontext = [
    'error_message' => optional_param('error', '', PARAM_TEXT),
    'status_message' => $status_message,
    'merchant' => [
        'kyc_status_pending' => ($merchant->kyc_status === 'pending'),
        'kyc_status_rejected' => ($merchant->kyc_status === 'rejected')
    ],
    'document_types' => $template_document_types,
    'has_documents' => !empty($existing_documents),
    'has_errors' => !empty($errors),
    'all_documents_submitted' => $all_documents_submitted,  // New flag for showing animation
    'sesskey' => sesskey(),
    'actionurl' => $CFG->wwwroot . '/local/lidio/kyc.php',
    'strings' => [
        'kycverification' => get_string('kycverification', 'local_lidio'),
        'kycverificationintro' => get_string('kycverification_desc', 'local_lidio'),
        'kycuploaddocuments' => get_string('kycuploaddocuments', 'local_lidio'),
        'kycstatus' => get_string('kycstatus', 'local_lidio'),
        'kycstatus_pending' => get_string('kycstatus_pending', 'local_lidio'),
        'kycstatus_rejected' => get_string('kycstatus_rejected', 'local_lidio'),
        'upload' => get_string('upload', 'local_lidio'),
        'delete' => get_string('delete', 'local_lidio'),
        'uploaded' => get_string('uploaded', 'local_lidio'),
        'acceptedformats' => get_string('acceptedformats', 'local_lidio'),
        'maxfilesize' => get_string('maxfilesize', 'local_lidio'),
        'submit' => get_string('submit', 'local_lidio'),
        'approved' => get_string('approved', 'local_lidio'),
        'pending' => get_string('pending', 'local_lidio'),
        'rejected' => get_string('rejected', 'local_lidio'),
        'confirmdeletedocument' => get_string('confirmdeletedocument', 'local_lidio'),
        'optional' => get_string('optional', 'local_lidio'),
        'processing' => get_string('processing', 'local_lidio'),
        'documents_under_review' => get_string('documents_under_review', 'local_lidio')
    ]
];

// Display the page
echo $OUTPUT->header();

// Skip the normal Moodle heading, we'll use our custom one
// Show KYC status message if any
if (!empty($status_message)) {
    $notification_type = ($merchant->kyc_status === 'pending') ? 
        \core\output\notification::NOTIFY_WARNING : 
        \core\output\notification::NOTIFY_ERROR;
    
    echo $OUTPUT->notification($status_message, $notification_type);
}

// Render the new KYC verification template with Tailwind
echo $OUTPUT->render_from_template('local_lidio/kyc_verification_tailwind', $templatecontext);

echo $OUTPUT->footer(); 