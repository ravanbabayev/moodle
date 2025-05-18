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
 * Merchant application form class.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Merchant application form definition.
 */
class merchant_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $CFG, $USER;
        
        $mform = $this->_form;
        
        // Add form header
        $mform->addElement('header', 'general_header', get_string('generalinformation', 'local_lidio'));
        
        // Company type - radio buttons
        $companyTypes = [
            'individual' => get_string('individual', 'local_lidio'),
            'company' => get_string('company', 'local_lidio')
        ];
        $mform->addElement('select', 'company_type', get_string('companytype', 'local_lidio'), $companyTypes);
        $mform->setType('company_type', PARAM_ALPHA);
        $mform->addRule('company_type', get_string('required'), 'required', null, 'client');
        
        // Company/Store name
        $mform->addElement('text', 'company_name', get_string('companyname', 'local_lidio'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('company_name', PARAM_TEXT);
        $mform->addRule('company_name', get_string('required'), 'required', null, 'client');
        
        // Email address
        $mform->addElement('text', 'email', get_string('email'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        
        // Phone number
        $mform->addElement('text', 'phone', get_string('phonenumber', 'local_lidio'), ['size' => 20, 'maxlength' => 20]);
        $mform->setType('phone', PARAM_TEXT);
        $mform->addRule('phone', get_string('required'), 'required', null, 'client');
        
        // Business information header
        $mform->addElement('header', 'business_header', get_string('businessinformation', 'local_lidio'));
        
        // Website (optional)
        $mform->addElement('text', 'website', get_string('website', 'local_lidio'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('website', PARAM_URL);
        
        // Social media links
        $mform->addElement('textarea', 'social_media', get_string('socialmedialinks', 'local_lidio'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('social_media', PARAM_TEXT);
        
        // Business area / Products
        $mform->addElement('textarea', 'business_area', get_string('businessarea', 'local_lidio'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('business_area', PARAM_TEXT);
        $mform->addRule('business_area', get_string('required'), 'required', null, 'client');
        
        // Monthly sales volume
        $volumeOptions = [
            '0-5000' => '0 - 5.000 ₺',
            '5000-20000' => '5.000 - 20.000 ₺',
            '20000-50000' => '20.000 - 50.000 ₺',
            '50000-100000' => '50.000 - 100.000 ₺',
            '100000+' => '100.000+ ₺'
        ];
        $mform->addElement('select', 'monthly_volume', get_string('monthlysalesvolume', 'local_lidio'), $volumeOptions);
        $mform->setType('monthly_volume', PARAM_TEXT);
        $mform->addRule('monthly_volume', get_string('required'), 'required', null, 'client');
        
        // Payment methods
        $paymentOptions = [
            'credit_card' => get_string('creditcard', 'local_lidio'),
            'bank_transfer' => get_string('banktransfer', 'local_lidio'),
            'payment_link' => get_string('paymentlink', 'local_lidio'),
            'mobile_payment' => get_string('mobilepayment', 'local_lidio'),
            'other' => get_string('otherpayment', 'local_lidio')
        ];
        $mform->addElement('select', 'payment_methods', get_string('paymentmethods', 'local_lidio'), $paymentOptions, 
            ['multiple' => 'multiple']);
        $mform->setType('payment_methods', PARAM_ALPHA);
        $mform->addRule('payment_methods', get_string('required'), 'required', null, 'client');
        
        // Bank information header
        $mform->addElement('header', 'bank_header', get_string('bankinformation', 'local_lidio'));
        
        // IBAN
        $mform->addElement('text', 'iban', get_string('iban', 'local_lidio'), ['size' => 30, 'maxlength' => 50]);
        $mform->setType('iban', PARAM_TEXT);
        $mform->addRule('iban', get_string('required'), 'required', null, 'client');
        
        // Account holder name
        $mform->addElement('text', 'account_holder', get_string('accountholder', 'local_lidio'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('account_holder', PARAM_TEXT);
        $mform->addRule('account_holder', get_string('required'), 'required', null, 'client');
        
        // Bank name
        $mform->addElement('text', 'bank_name', get_string('bankname', 'local_lidio'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('bank_name', PARAM_TEXT);
        $mform->addRule('bank_name', get_string('required'), 'required', null, 'client');
        
        // Agreement header
        $mform->addElement('header', 'agreement_header', get_string('agreements', 'local_lidio'));
        
        // KVKK approval
        $mform->addElement('advcheckbox', 'kvkk_approval', get_string('kvkkapproval', 'local_lidio'), 
            get_string('kvkkapprovaltext', 'local_lidio'));
        $mform->addRule('kvkk_approval', get_string('required'), 'required', null, 'client');
        
        // Terms approval
        $mform->addElement('advcheckbox', 'terms_approval', get_string('termsapproval', 'local_lidio'), 
            get_string('termsapprovaltext', 'local_lidio'));
        $mform->addRule('terms_approval', get_string('required'), 'required', null, 'client');
        
        // Hidden User ID field
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $USER->id);
        
        // Add the action buttons
        $this->add_action_buttons();
    }
    
    /**
     * Validation rules.
     *
     * @param array $data The form data
     * @param array $files Any uploaded files
     * @return array Any validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate IBAN format (basic check)
        if (!empty($data['iban'])) {
            // Remove spaces and check if IBAN consists of valid characters
            $iban = str_replace(' ', '', $data['iban']);
            if (!preg_match('/^[A-Z0-9]+$/', $iban)) {
                $errors['iban'] = get_string('invalidiban', 'local_lidio');
            }
        }
        
        // Validate email format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = get_string('invalidemail');
        }
        
        // Validate phone number (basic check for numeric and length)
        if (!empty($data['phone']) && !preg_match('/^[0-9]{10,15}$/', preg_replace('/[^0-9]/', '', $data['phone']))) {
            $errors['phone'] = get_string('invalidphone', 'local_lidio');
        }
        
        // Make sure checkboxes are checked
        if (empty($data['kvkk_approval'])) {
            $errors['kvkk_approval'] = get_string('kvkkapprovalrequired', 'local_lidio');
        }
        
        if (empty($data['terms_approval'])) {
            $errors['terms_approval'] = get_string('termsapprovalrequired', 'local_lidio');
        }
        
        return $errors;
    }
} 