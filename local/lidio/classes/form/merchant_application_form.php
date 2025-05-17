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
 * Merchant application form for Lidio payment system
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Merchant application form
 */
class merchant_application_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;

        // Add a header for merchant information
        $mform->addElement('header', 'merchantinfo', get_string('merchantapplication', 'local_lidio'));
        $mform->setExpanded('merchantinfo', true);

        // Full name
        $mform->addElement('text', 'fullname', get_string('fullname', 'local_lidio'), 'size="50"');
        $mform->addRule('fullname', get_string('requiredfield', 'local_lidio'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addHelpButton('fullname', 'fullname', 'local_lidio');

        // Address
        $mform->addElement('textarea', 'address', get_string('address', 'local_lidio'), 
            array('rows' => 4, 'cols' => 50));
        $mform->addRule('address', get_string('requiredfield', 'local_lidio'), 'required', null, 'client');
        $mform->setType('address', PARAM_TEXT);
        $mform->addHelpButton('address', 'address', 'local_lidio');

        // Phone number
        $mform->addElement('text', 'phone', get_string('phone', 'local_lidio'), 'size="20"');
        $mform->addRule('phone', get_string('requiredfield', 'local_lidio'), 'required', null, 'client');
        $mform->setType('phone', PARAM_TEXT);
        $mform->addHelpButton('phone', 'phone', 'local_lidio');

        // ID number
        $mform->addElement('text', 'idnumber', get_string('idnumber', 'local_lidio'), 'size="20"');
        $mform->addRule('idnumber', get_string('requiredfield', 'local_lidio'), 'required', null, 'client');
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addHelpButton('idnumber', 'idnumber', 'local_lidio');

        // Add standard buttons
        $this->add_action_buttons(true, get_string('applyasmerchant', 'local_lidio'));
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate phone number format (basic validation)
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]{6,20}$/', $data['phone'])) {
            $errors['phone'] = get_string('invalidphone', 'local_lidio');
        }
        
        return $errors;
    }
} 