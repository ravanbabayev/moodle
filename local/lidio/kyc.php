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

// Check if the plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    print_error('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Get merchant ID from the request
$merchantid = optional_param('merchantid', 0, PARAM_INT);

// Set up the page
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($CFG->wwwroot . '/local/lidio/kyc.php', array('merchantid' => $merchantid));
$PAGE->set_title(get_string('kycverification', 'local_lidio'));
$PAGE->set_heading(get_string('kycverification', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/lidio/styles.css');

// Add JavaScript
$PAGE->requires->js('/local/lidio/scripts.js');

// Check if user is a merchant
$merchant = local_lidio_is_merchant();
if (!$merchant) {
    // User is not a merchant, redirect to merchant application
    redirect(
        $CFG->wwwroot . '/local/lidio/merchant.php',
        get_string('notamerchant', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// If merchant has completed KYC and it's approved, redirect to dashboard
if ($merchant->kyc_status === 'approved') {
    redirect(
        $CFG->wwwroot . '/local/lidio/merchant.php',
        get_string('kycstatus_approved', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Generate error message if there is one from the redirect
$error_message = optional_param('error', '', PARAM_TEXT);

// Generate status message based on merchant KYC status
$status_message = '';
if ($merchant->kyc_status === 'pending') {
    $status_message = get_string('kycstatus_pending', 'local_lidio');
} else if ($merchant->kyc_status === 'rejected') {
    $status_message = get_string('kycstatus_rejected', 'local_lidio');
}

// Prepare the template context
$templatecontext = [
    'error_message' => $error_message,
    'status_message' => $status_message,
    'merchant' => $merchant,
    'sesskey' => sesskey()
];

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('kycverification', 'local_lidio'));

// Show KYC status message if any
if (!empty($status_message)) {
    $notification_type = ($merchant->kyc_status === 'pending') ? 
        \core\output\notification::NOTIFY_WARNING : 
        \core\output\notification::NOTIFY_ERROR;
    
    echo $OUTPUT->notification($status_message, $notification_type);
}

// Render the KYC verification template
echo $OUTPUT->render_from_template('local_lidio/kyc_verification', $templatecontext);

echo $OUTPUT->footer(); 