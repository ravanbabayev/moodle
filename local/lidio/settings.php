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
 * Settings for the Lidio payment system plugin.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Import necessary classes
use moodle_url;

if ($hassiteconfig) {
    // Create the settings page
    $settings = new admin_settingpage('local_lidio', get_string('settings', 'local_lidio'));
    $ADMIN->add('localplugins', $settings);

    // General settings
    $settings->add(new admin_setting_heading('local_lidio/general',
        get_string('pluginname', 'local_lidio'), ''));
    
    $settings->add(new admin_setting_configcheckbox('local_lidio/enabled',
        get_string('enabled', 'local_lidio'),
        get_string('enabled_desc', 'local_lidio'), 1));
    
    // Add link to manage merchants
    $ADMIN->add('localplugins', new admin_externalpage('local_lidio_merchants',
        get_string('merchantmanagement', 'local_lidio'),
        new moodle_url('/local/lidio/admin/merchants.php')));
} 