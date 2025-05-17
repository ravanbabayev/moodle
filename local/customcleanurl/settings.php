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
 * 
 * @package    local_customcleanurl
 * @copyright  2024 https://santoshmagar.com.np/
 * @author     santoshtmp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

if ($hassiteconfig) {

    // Create the parent item (your local plugin)
    $ADMIN->add('localplugins', new admin_category(
        'customcleanurl_settings', // Unique identifier for the category.
        get_string('pluginname', 'local_customcleanurl') // Display name for your plugin.
    ));

    // ------------------
    $settings = new admin_settingpage('local_customcleanurl', 'General Setting');

    /**
     * 
     */
    $check_rewrite_htaccess = '';
    $enable_customcleanurl = get_config('local_customcleanurl', 'enable_customcleanurl');

    /**
     * 
     */
    $name = 'local_customcleanurl/enable_customcleanurl';
    $title = "Enable Customcleanurl";
    $description = '';
    if ($enable_customcleanurl) {
        $check_rewrite_htaccess = \local_customcleanurl\local\htaccess::check_rewrite_htaccess();
        if (!$check_rewrite_htaccess) {
            $description .= '<div class="alert alert-danger alert-block fade in  alert-dismissible"> change the .htaccess accoding to readme file.</div>';
        } else {
            $re_check_rewrite_htaccess = \local_customcleanurl\local\htaccess::check_other_rewrite_rule_htaccess();
            if (!$re_check_rewrite_htaccess) {
                $description .= '<div class="alert alert-danger alert-block fade in  alert-dismissible">Re-change the .htaccess accoding to readme file, as there might be some changes. <br><strong>IGNORE</strong> if you have made the changes.</div>';
            }
        }
    }
    $setting = new admin_setting_configcheckbox($name, $title, $description, '0');
    $settings->add($setting);

    /**
     * 
     */
    if ($enable_customcleanurl) {

        /**
         * 
         */
        if (!$check_rewrite_htaccess) {
            $name = 'local_customcleanurl/set_htaccess';
            $title = "Set htaccess route";
            $description = 'change the .htaccess accoding to readme file. <br> After change conform by chacking .htaccess file, where there is rewrite rule for this plugin, and if there is any other rewrite rule remove other rules except from local_customcleanurl to match readme file rules.';
            $setting = new admin_setting_configcheckbox($name, $title, $description, '0');
            $setting->set_updatedcallback('local_customcleanurl_set_htaccess');
            $settings->add($setting);
        }

        /**
         *
         */
        $checkbox_options  = array(
            'course_url' => 'Course URL',
            'user_url' => 'User URL',
            'define_url' => 'Define Custom URL'
        );
        $default_values = [
            'course_url' => 0,
            'user_url' => 0,
            'define_url' => 1,
        ];
        $name = 'local_customcleanurl/cleanurl_type';
        $title = 'Custom URL Type';
        $description = get_string('cleanurl_options_desc', 'local_customcleanurl');
        $setting = new admin_setting_configmulticheckbox($name, $title, $description, $default_values, $checkbox_options);
        // $setting = new admin_setting_configmultiselect($name, $title, $description, array(), $checkbox_options );
        $settings->add($setting);
    }
    // 
    $ADMIN->add('customcleanurl_settings', $settings);

    // -----------------
    // External link
    $cleanurl_options = get_config('local_customcleanurl', 'cleanurl_type');
    $cleanurl_options = explode(",", $cleanurl_options);
    if (in_array('define_url', $cleanurl_options) && $enable_customcleanurl) {
        $external_link = new moodle_url('/local/customcleanurl/define_custom_url.php');
        $ADMIN->add('customcleanurl_settings', new admin_externalpage(
            'local_define_custom_url', // Unique identifier
            'Define Custom URL', // Link name
            $external_link  // External URL
        ));
    }
}
