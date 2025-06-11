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

if ($hassiteconfig) {
    // Create the main Lidio settings category
    $ADMIN->add('localplugins', new admin_category('local_lidio_settings', 
        get_string('pluginname', 'local_lidio')));

    // Create the settings page
    $settings = new admin_settingpage('local_lidio', get_string('settings', 'local_lidio'));
    
    // General settings
    $settings->add(new admin_setting_heading('local_lidio/general',
        get_string('pluginname', 'local_lidio'), 
        get_string('plugindesc', 'local_lidio')));
    
    $settings->add(new admin_setting_configcheckbox('local_lidio/enabled',
        get_string('enabled', 'local_lidio'),
        get_string('enabled_desc', 'local_lidio'), 1));
    
    // Add commission rate setting
    $settings->add(new admin_setting_configtext('local_lidio/commission_rate',
        get_string('commissionrate', 'local_lidio'),
        get_string('commissionrate_desc', 'local_lidio'), '2.5', PARAM_FLOAT));
    
    // Add settings to the category
    $ADMIN->add('local_lidio_settings', $settings);
    
    // Add merchant management page
    $ADMIN->add('local_lidio_settings', new admin_externalpage('local_lidio_merchants',
        get_string('merchantmanagement', 'local_lidio'),
        new \moodle_url('/local/lidio/admin/merchants.php'),
        'local/lidio:managemerchants'));
    
    // Add transactions page
    $ADMIN->add('local_lidio_settings', new admin_externalpage('local_lidio_transactions',
        get_string('transactionmanagement', 'local_lidio'),
        new \moodle_url('/local/lidio/admin/transactions.php'),
        'local/lidio:viewtransactions'));
    
    // Add reports page
    $ADMIN->add('local_lidio_settings', new admin_externalpage('local_lidio_reports',
        get_string('reports', 'local_lidio'),
        new \moodle_url('/local/lidio/admin/reports.php'),
        'local/lidio:viewreports'));
} 