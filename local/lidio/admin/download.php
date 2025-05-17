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
 * Document download script for Lidio KYC documents.
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

// Get document ID
$id = required_param('id', PARAM_INT);

// Get document record
$document = $DB->get_record('local_lidio_documents', array('id' => $id), '*', MUST_EXIST);

// Get merchant info for access control
$merchant = $DB->get_record('local_lidio_merchants', array('id' => $document->merchantid), '*', MUST_EXIST);

// Prepare file for sending
$filepath = $document->filepath;
$filename = $document->filename;

// Check if file exists
if (!file_exists($filepath)) {
    throw new moodle_exception('filenotfound', 'local_lidio', $CFG->wwwroot . '/local/lidio/admin/merchants.php');
}

// Set headers
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// Clean output buffer
ob_clean();
flush();

// Read file and output
readfile($filepath);

exit; 