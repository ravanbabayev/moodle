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
 * Handler for KYC verification form submissions.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/filelib.php');

// Check if user is logged in
require_login();

// Check if the plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    redirect($CFG->wwwroot, get_string('plugindisabled', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
}

// Get merchantid parameter
$merchantid = required_param('merchantid', PARAM_INT);
$returnurl = $CFG->wwwroot . '/local/lidio/kyc.php?merchantid=' . $merchantid;

// Verify user is authorized to submit KYC for this merchant
$merchant = $DB->get_record('local_lidio_merchants', ['id' => $merchantid, 'userid' => $USER->id]);
if (!$merchant) {
    redirect($CFG->wwwroot, get_string('notauthorized', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $sesskey = required_param('sesskey', PARAM_RAW);
    
    // Check session key for security
    if (!confirm_sesskey($sesskey)) {
        redirect($returnurl, get_string('invalidsesskey', 'error'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Process uploaded files
    $context = context_user::instance($USER->id);
    $fs = get_file_storage();
    
    // Flag to track if all documents were uploaded successfully
    $allDocumentsUploaded = true;
    
    // Process ID document upload
    if (!empty($_FILES['iddocument']['name'])) {
        $file_info = array(
            'contextid' => $context->id,
            'component' => 'local_lidio',
            'filearea' => 'kyc_id_document',
            'itemid' => $merchantid,
            'filepath' => '/',
            'filename' => $_FILES['iddocument']['name']
        );
        
        // Save ID document
        if (!$fs->create_file_from_pathname($file_info, $_FILES['iddocument']['tmp_name'])) {
            $allDocumentsUploaded = false;
        }
        
        // Create record in local_lidio_kyc_documents table
        $docRecord = new stdClass();
        $docRecord->merchantid = $merchantid;
        $docRecord->documenttype = 'id_document';
        $docRecord->filename = $_FILES['iddocument']['name'];
        $docRecord->status = 'pending';
        $docRecord->timecreated = time();
        $docRecord->timemodified = time();
        
        $DB->insert_record('local_lidio_kyc_documents', $docRecord);
    } else {
        $allDocumentsUploaded = false;
    }
    
    // Process address proof upload
    if (!empty($_FILES['addressproof']['name'])) {
        $file_info = array(
            'contextid' => $context->id,
            'component' => 'local_lidio',
            'filearea' => 'kyc_address_proof',
            'itemid' => $merchantid,
            'filepath' => '/',
            'filename' => $_FILES['addressproof']['name']
        );
        
        // Save address proof
        if (!$fs->create_file_from_pathname($file_info, $_FILES['addressproof']['tmp_name'])) {
            $allDocumentsUploaded = false;
        }
        
        // Create record in local_lidio_kyc_documents table
        $docRecord = new stdClass();
        $docRecord->merchantid = $merchantid;
        $docRecord->documenttype = 'address_document';
        $docRecord->filename = $_FILES['addressproof']['name'];
        $docRecord->status = 'pending';
        $docRecord->timecreated = time();
        $docRecord->timemodified = time();
        
        $DB->insert_record('local_lidio_kyc_documents', $docRecord);
    } else {
        $allDocumentsUploaded = false;
    }
    
    if ($allDocumentsUploaded) {
        // Update merchant record to reflect KYC submission
        $merchant->kyc_status = 'pending';
        $merchant->timemodified = time();
        $DB->update_record('local_lidio_merchants', $merchant);
        
        // Redirect to merchant dashboard
        redirect(
            $CFG->wwwroot . '/local/lidio/merchant.php',
            get_string('kycsubmitted', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Some documents were not uploaded successfully
        redirect($returnurl, get_string('fileuploaderror', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
    }
} else {
    // Form was not submitted via POST, redirect back
    redirect($returnurl);
} 