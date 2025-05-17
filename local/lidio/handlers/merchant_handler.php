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
 * Handler for merchant form submissions.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check if user is logged in
require_login();

// Check if the plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    redirect($CFG->wwwroot, get_string('plugindisabled', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
}

// Setup the page to return to after processing
$returnurl = optional_param('returnurl', $CFG->wwwroot . '/local/lidio/merchant.php', PARAM_URL);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullname = required_param('fullname', PARAM_TEXT);
    $address = required_param('address', PARAM_TEXT);
    $phone = required_param('phone', PARAM_TEXT);
    $idnumber = required_param('idnumber', PARAM_TEXT);
    $sesskey = required_param('sesskey', PARAM_RAW);
    
    // Check session key for security
    if (!confirm_sesskey($sesskey)) {
        redirect($returnurl, get_string('invalidsesskey', 'error'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Validate the phone number
    if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
        redirect($returnurl, get_string('invalidphone', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Create merchant record
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->status = 'pending';
    $record->fullname = $fullname;
    $record->address = $address;
    $record->phone = $phone;
    $record->idnumber = $idnumber;
    $record->kyc_status = 'pending';
    $record->timecreated = time();
    $record->timemodified = time();
    
    // Save to database
    $merchantid = $DB->insert_record('local_lidio_merchants', $record);
    
    if ($merchantid) {
        // Redirect to KYC verification page
        redirect(
            $CFG->wwwroot . '/local/lidio/kyc.php?merchantid=' . $merchantid,
            get_string('applicationsubmitted', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Error saving record
        redirect($returnurl, get_string('errorprocessingform', 'local_lidio'), null, \core\output\notification::NOTIFY_ERROR);
    }
} else {
    // Form was not submitted via POST, redirect back
    redirect($returnurl);
} 