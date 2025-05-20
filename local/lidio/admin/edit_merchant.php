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
 * Edit merchant details page.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

// Import necessary classes
use core\notification;
use moodle_url;
use html_writer;
use html_table;

// Check access
admin_externalpage_setup('local_lidio_merchants');

$id = required_param('id', PARAM_INT);
$merchant = $DB->get_record('local_lidio_merchants', array('id' => $id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $merchant->userid), '*', MUST_EXIST);

// Set up the page
$PAGE->set_url(new moodle_url('/local/lidio/admin/edit_merchant.php', array('id' => $id)));
$PAGE->set_title(get_string('merchantedit', 'local_lidio'));
$PAGE->set_heading(get_string('merchantedit', 'local_lidio'));

// Define the form
class edit_merchant_form extends moodleform {
    protected function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $merchant = $this->_customdata['merchant'];
        
        // Add some hidden fields
        $mform->addElement('hidden', 'id', $merchant->id);
        $mform->setType('id', PARAM_INT);
        
        // Company details
        $mform->addElement('header', 'companydetails', get_string('companydetails', 'local_lidio'));
        
        $mform->addElement('text', 'company_name', get_string('companyname', 'local_lidio'));
        $mform->setType('company_name', PARAM_TEXT);
        $mform->addRule('company_name', get_string('required'), 'required', null, 'client');
        $mform->setDefault('company_name', $merchant->company_name);

        $mform->addElement('text', 'email', get_string('email'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        $mform->setDefault('email', $merchant->email);

        $mform->addElement('text', 'phone', get_string('phone', 'local_lidio'));
        $mform->setType('phone', PARAM_TEXT);
        $mform->addRule('phone', get_string('required'), 'required', null, 'client');
        $mform->setDefault('phone', $merchant->phone);
        
        // Status settings
        $mform->addElement('header', 'statussettings', get_string('statussettings', 'local_lidio'));
        
        $statusoptions = array(
            'pending' => get_string('pending', 'local_lidio'),
            'approved' => get_string('approved', 'local_lidio'),
            'rejected' => get_string('rejected', 'local_lidio')
        );
        $mform->addElement('select', 'status', get_string('status', 'local_lidio'), $statusoptions);
        $mform->setDefault('status', $merchant->status);

        $kycoptions = array(
            'pending' => get_string('pending', 'local_lidio'),
            'approved' => get_string('approved', 'local_lidio'),
            'rejected' => get_string('rejected', 'local_lidio')
        );
        $mform->addElement('select', 'kyc_status', get_string('kycstatus', 'local_lidio'), $kycoptions);
        $mform->setDefault('kyc_status', $merchant->kyc_status);
        
        // Financial settings
        $mform->addElement('header', 'financialsettings', get_string('financialsettings', 'local_lidio'));
        
        $mform->addElement('text', 'commission_rate', get_string('commissionrate', 'local_lidio') . ' (%)');
        $mform->setType('commission_rate', PARAM_FLOAT);
        $mform->addRule('commission_rate', get_string('required'), 'required', null, 'client');
        $mform->setDefault('commission_rate', $merchant->commission_rate);
        $mform->addHelpButton('commission_rate', 'commissionrate', 'local_lidio');

        $mform->addElement('text', 'settlement_period', get_string('settlementperiod', 'local_lidio') . ' (' . get_string('days', 'local_lidio') . ')');
        $mform->setType('settlement_period', PARAM_INT);
        $mform->addRule('settlement_period', get_string('required'), 'required', null, 'client');
        $mform->setDefault('settlement_period', $merchant->settlement_period);
        $mform->addHelpButton('settlement_period', 'settlementperiod', 'local_lidio');
        
        // Admin notes
        $mform->addElement('header', 'adminnotes', get_string('adminnotes', 'local_lidio'));
        
        $mform->addElement('textarea', 'admin_notes', get_string('adminnotes', 'local_lidio'), 
                           array('rows' => 5, 'cols' => 50));
        $mform->setType('admin_notes', PARAM_TEXT);
        $mform->setDefault('admin_notes', $merchant->admin_notes);
        
        // Add action buttons
        $this->add_action_buttons();
    }
    
    /**
     * Validate the form data.
     *
     * @param array $data The form data
     * @param array $files The form files
     * @return array List of errors if any
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate commission rate (must be between 0 and 100)
        if ($data['commission_rate'] < 0 || $data['commission_rate'] > 100) {
            $errors['commission_rate'] = get_string('commissionrateerror', 'local_lidio');
        }

        // Validate settlement period (must be greater than 0)
        if ($data['settlement_period'] <= 0) {
            $errors['settlement_period'] = get_string('settlementperioderror', 'local_lidio');
        }

        return $errors;
    }
}

// Create form instance
$form = new edit_merchant_form($PAGE->url, array('merchant' => $merchant));

// Process form submission
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/lidio/admin/merchants.php'));
} else if ($data = $form->get_data()) {
    // Update merchant data
    $merchant->company_name = $data->company_name;
    $merchant->email = $data->email;
    $merchant->phone = $data->phone;
    $merchant->status = $data->status;
    $merchant->kyc_status = $data->kyc_status;
    $merchant->commission_rate = $data->commission_rate;
    $merchant->settlement_period = $data->settlement_period;
    $merchant->admin_notes = $data->admin_notes;
    $merchant->timemodified = time();
    
    $DB->update_record('local_lidio_merchants', $merchant);
    
    notification::success(get_string('merchantupdated', 'local_lidio'));
    redirect(new moodle_url('/local/lidio/admin/merchants.php'));
}

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('merchantedit', 'local_lidio') . ': ' . fullname($user));

// Show merchant basic info
$userinfo = new html_table();
$userinfo->attributes['class'] = 'admintable generaltable';
$userinfo->data[] = array(get_string('id', 'local_lidio'), $merchant->id);
$userinfo->data[] = array(get_string('fullname', 'local_lidio'), fullname($user));
$userinfo->data[] = array(get_string('email'), $user->email);
$userinfo->data[] = array(get_string('applicationdate', 'local_lidio'), userdate($merchant->timecreated, get_string('strftimedatetime', 'langconfig')));

echo html_writer::table($userinfo);

// Display form
$form->display();

echo $OUTPUT->footer(); 