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
 * Merchant application form.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/accesslib.php');

// Import necessary classes
use core\exception\moodle_exception;
// We need to explicitly import the context_system class to fix the linter error
require_once($CFG->libdir . '/accesslib.php');

// Check plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    throw new moodle_exception('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Page setup
global $PAGE;
$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/lidio/merchant_application.php');
$PAGE->set_title(get_string('merchantapplication', 'local_lidio'));
$PAGE->set_heading(get_string('merchantapplication', 'local_lidio'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/lidio/styles.css');

// Add JavaScript
$PAGE->requires->js('/local/lidio/scripts.js');

// Get the edit parameter if available
$edit = optional_param('edit', false, PARAM_BOOL);

// Check if the user is already a merchant
$merchant = local_lidio_is_merchant();

// Process form submission
if ($data = data_submitted() and confirm_sesskey()) {
    if (isset($data->submitbutton)) {
        $merchantdata = new stdClass();
        $merchantdata->userid = $USER->id;
        $merchantdata->company_type = required_param('company_type', PARAM_ALPHA);
        $merchantdata->company_name = required_param('company_name', PARAM_TEXT);
        $merchantdata->email = $USER->email; // Use user's email directly
        $merchantdata->phone = required_param('phone', PARAM_TEXT);
        $merchantdata->website = optional_param('website', '', PARAM_URL);
        $merchantdata->social_media = optional_param('social_media', '', PARAM_TEXT);
        $merchantdata->business_area = required_param('business_area', PARAM_TEXT);
        $merchantdata->monthly_volume = required_param('monthly_volume', PARAM_TEXT);
        
        // Get payment methods array and implode to string
        $payment_methods = optional_param_array('payment_methods', [], PARAM_ALPHA);
        $merchantdata->payment_methods = implode(',', $payment_methods);
        
        $merchantdata->iban = required_param('iban', PARAM_TEXT);
        $merchantdata->account_holder = required_param('account_holder', PARAM_TEXT);
        $merchantdata->bank_name = required_param('bank_name', PARAM_TEXT);
        $merchantdata->kvkk_approval = required_param('kvkk_approval', PARAM_BOOL);
        $merchantdata->terms_approval = required_param('terms_approval', PARAM_BOOL);
        $merchantdata->timecreated = time();
        $merchantdata->timemodified = time();
        $merchantdata->status = 'pending'; // Default status
        $merchantdata->kyc_status = 'pending'; // Default KYC status
        
        // Validate required fields
        $errors = [];
        
        if (empty($merchantdata->company_type)) {
            $errors['company_type'] = get_string('required');
        }
        
        if (empty($merchantdata->company_name)) {
            $errors['company_name'] = get_string('required');
        }
        
        // Email validation is no longer needed since we use $USER->email directly
        
        if (empty($merchantdata->phone)) {
            $errors['phone'] = get_string('required');
        }
        
        if (empty($merchantdata->business_area)) {
            $errors['business_area'] = get_string('required');
        }
        
        if (empty($merchantdata->monthly_volume)) {
            $errors['monthly_volume'] = get_string('required');
        }
        
        if (empty($payment_methods)) {
            $errors['payment_methods'] = get_string('required');
        }
        
        if (empty($merchantdata->iban)) {
            $errors['iban'] = get_string('required');
        } else {
            // Basic IBAN validation
            $iban = str_replace(' ', '', $merchantdata->iban);
            if (!preg_match('/^[A-Z0-9]+$/', $iban)) {
                $errors['iban'] = get_string('invalidiban', 'local_lidio');
            }
        }
        
        if (empty($merchantdata->account_holder)) {
            $errors['account_holder'] = get_string('required');
        }
        
        if (empty($merchantdata->bank_name)) {
            $errors['bank_name'] = get_string('required');
        }
        
        if (empty($merchantdata->kvkk_approval)) {
            $errors['kvkk_approval'] = get_string('kvkkapprovalrequired', 'local_lidio');
        }
        
        if (empty($merchantdata->terms_approval)) {
            $errors['terms_approval'] = get_string('termsapprovalrequired', 'local_lidio');
        }
        
        // Process if no errors
        if (empty($errors)) {
            if ($merchant) {
                // Update existing merchant
                $merchantdata->id = $merchant->id;
                $merchantdata->status = $merchant->status; // Preserve existing status
                $merchantdata->kyc_status = $merchant->kyc_status; // Preserve existing KYC status
                $DB->update_record('local_lidio_merchants', $merchantdata);
                $message = get_string('merchantupdated', 'local_lidio');
            } else {
                // Insert new merchant
                $DB->insert_record('local_lidio_merchants', $merchantdata);
                $message = get_string('merchantcreated', 'local_lidio');
            }
            
            // Redirect to the merchant dashboard page
            redirect($CFG->wwwroot . '/local/lidio/merchant.php', $message, null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
}

// Prepare template context
$templatecontext = [
    'sesskey' => sesskey(),
    'actionurl' => $CFG->wwwroot . '/local/lidio/merchant_application.php',
    'errors' => isset($errors) ? $errors : [],
    'company_type_options' => [
        ['value' => 'individual', 'text' => get_string('individual', 'local_lidio'), 'selected' => (isset($merchant) && $merchant->company_type === 'individual')],
        ['value' => 'company', 'text' => get_string('company', 'local_lidio'), 'selected' => (isset($merchant) && $merchant->company_type === 'company')]
    ],
    'monthly_volume_options' => [
        ['value' => '0-5000', 'text' => '0 - 5.000 ₺', 'selected' => (isset($merchant) && $merchant->monthly_volume === '0-5000')],
        ['value' => '5000-20000', 'text' => '5.000 - 20.000 ₺', 'selected' => (isset($merchant) && $merchant->monthly_volume === '5000-20000')],
        ['value' => '20000-50000', 'text' => '20.000 - 50.000 ₺', 'selected' => (isset($merchant) && $merchant->monthly_volume === '20000-50000')],
        ['value' => '50000-100000', 'text' => '50.000 - 100.000 ₺', 'selected' => (isset($merchant) && $merchant->monthly_volume === '50000-100000')],
        ['value' => '100000+', 'text' => '100.000+ ₺', 'selected' => (isset($merchant) && $merchant->monthly_volume === '100000+')]
    ],
    'payment_methods_options' => [
        ['value' => 'credit_card', 'text' => get_string('creditcard', 'local_lidio'), 
            'checked' => (isset($merchant) && strpos($merchant->payment_methods, 'credit_card') !== false)],
        ['value' => 'bank_transfer', 'text' => get_string('banktransfer', 'local_lidio'), 
            'checked' => (isset($merchant) && strpos($merchant->payment_methods, 'bank_transfer') !== false)],
        ['value' => 'payment_link', 'text' => get_string('paymentlink', 'local_lidio'), 
            'checked' => (isset($merchant) && strpos($merchant->payment_methods, 'payment_link') !== false)],
    ],
    'merchant' => $merchant,
    'edit' => $edit,
    'dashboard_url' => $CFG->wwwroot . '/local/lidio/merchant_dashboard.php',
    'strings' => [
        // Application form strings
        'generalinformation' => get_string('generalinformation', 'local_lidio'),
        'companytype' => get_string('companytype', 'local_lidio'),
        'companyname' => get_string('companyname', 'local_lidio'),
        'email' => get_string('email'),
        'phonenumber' => get_string('phonenumber', 'local_lidio'),
        'businessinformation' => get_string('businessinformation', 'local_lidio'),
        'website' => get_string('website', 'local_lidio'),
        'socialmedialinks' => get_string('socialmedialinks', 'local_lidio'),
        'businessarea' => get_string('businessarea', 'local_lidio'),
        'monthlysalesvolume' => get_string('monthlysalesvolume', 'local_lidio'),
        'paymentmethods' => get_string('paymentmethods', 'local_lidio'),
        'bankinformation' => get_string('bankinformation', 'local_lidio'),
        'iban' => get_string('iban', 'local_lidio'),
        'accountholder' => get_string('accountholder', 'local_lidio'),
        'bankname' => get_string('bankname', 'local_lidio'),
        'agreements' => get_string('agreements', 'local_lidio'),
        'kvkkapproval' => get_string('kvkkapproval', 'local_lidio'),
        'kvkkapprovaltext' => get_string('kvkkapprovaltext', 'local_lidio'),
        'termsapproval' => get_string('termsapproval', 'local_lidio'),
        'termsapprovaltext' => get_string('termsapprovaltext', 'local_lidio'),
        'submit' => get_string('submit'),
        'cancel' => get_string('cancel'),
        'status' => get_string('status', 'local_lidio'),
        'kycstatus' => get_string('kycstatus', 'local_lidio'),
        
        // Common strings
        'merchantapplication' => get_string('merchantapplication', 'local_lidio'),
        'becomemerchantintro' => get_string('becomemerchantintro', 'local_lidio'),
        'merchantapplicationpending' => get_string('merchantapplicationpending', 'local_lidio'),
        'merchantapplicationpendingmessage' => get_string('merchantapplicationpendingmessage', 'local_lidio'),
        'updateapplication' => get_string('updateapplication', 'local_lidio')
    ]
];

// Set form values from existing merchant data
if ($merchant) {
    $templatecontext['form_data'] = (array)$merchant;
    $templatecontext['form_data']['payment_methods'] = explode(',', $merchant->payment_methods);
} else {
    $templatecontext['form_data'] = [
        'email' => $USER->email  // Always populate with user's email
    ];
}

// Display errors if any
if (isset($errors)) {
    $templatecontext['has_errors'] = true;
    // Ensure email is always set even if there are validation errors
    $templatecontext['form_data']['email'] = $USER->email;
}

// Start output
echo $OUTPUT->header();

// Display appropriate content based on merchant status
if ($merchant && $merchant->status === 'pending' && !$edit) {
    echo $OUTPUT->render_from_template('local_lidio/merchant_pending', $templatecontext);
} else {
    echo $OUTPUT->render_from_template('local_lidio/merchant_application', $templatecontext);
}

// End output
echo $OUTPUT->footer(); 