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
 * KYC verification form for Lidio payment system
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * KYC verification form
 */
class kyc_verification_form extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;
        
        // Add a header for KYC verification
        $mform->addElement('header', 'kycverification', get_string('kycverification', 'local_lidio'));
        $mform->setExpanded('kycverification', true);
        
        // Add hidden merchant ID
        if (isset($this->_customdata['merchantid'])) {
            $mform->addElement('hidden', 'merchantid', $this->_customdata['merchantid']);
            $mform->setType('merchantid', PARAM_INT);
        }
        
        // Upload ID document
        $filepickeroptions = array(
            'maxbytes' => 5242880, // 5MB
            'accepted_types' => array('.jpg', '.png', '.pdf'),
            'maxfiles' => 1
        );
        
        $mform->addElement('filepicker', 'id_document', 
            get_string('uploadid', 'local_lidio'), null, $filepickeroptions);
        $mform->addRule('id_document', get_string('requiredfield', 'local_lidio'), 'required', null, 'client');
        $mform->addHelpButton('id_document', 'uploadid', 'local_lidio');
        
        // Upload address proof document
        $mform->addElement('filepicker', 'address_document', 
            get_string('uploadaddressproof', 'local_lidio'), null, $filepickeroptions);
        $mform->addRule('address_document', get_string('requiredfield', 'local_lidio'), 'required', null, 'client');
        $mform->addHelpButton('address_document', 'uploadaddressproof', 'local_lidio');
        
        // Add standard buttons
        $this->add_action_buttons(true, get_string('submitmerchantrequirement', 'local_lidio'));
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
        
        // We can add document type validation here if needed
        
        return $errors;
    }
} 