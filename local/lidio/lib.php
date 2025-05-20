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
 * Library functions for the Lidio payment system plugin.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\notification;

// Import required classes to fix linter errors
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/weblib.php');
use context_system;
use moodle_url;

/**
 * Extend navigation to add Lidio links to the user menu.
 *
 * @param global_navigation $navigation The navigation object
 */
function local_lidio_extend_navigation(global_navigation $navigation) {
    global $USER, $CFG, $DB, $PAGE;

    // Only proceed if the plugin is enabled
    if (empty(get_config('local_lidio', 'enabled'))) {
        return;
    }

    if (isloggedin() && !isguestuser()) {
        // Check if the current user has merchant capability
        require_once($CFG->libdir.'/accesslib.php');
        $systemcontext = context_system::instance();
        if (has_capability('local/lidio:bemerchant', $systemcontext)) {
            // Check if user is already a merchant
            $record = $DB->get_record('local_lidio_merchants', ['userid' => $USER->id]);
            
            // Find the correct node to add our links to
            $myprofile = $navigation->find('myprofile', null);
            if ($myprofile) {
                if (!$record) {
                    // User is not a merchant, show apply link
                    $url = $CFG->wwwroot . '/local/lidio/merchant_application.php';
                    $myprofile->add(
                        get_string('applyasmerchant', 'local_lidio'),
                        $url,
                        navigation_node::TYPE_SETTING
                    );
                } else if ($record->status === 'approved') {
                    // User is an approved merchant, show dashboard link
                    $url = $CFG->wwwroot . '/local/lidio/merchant_dashboard.php';
                    $myprofile->add(
                        get_string('merchantdashboard', 'local_lidio'),
                        $url,
                        navigation_node::TYPE_SETTING
                    );
                } else {
                    // User has a pending application
                    $url = $CFG->wwwroot . '/local/lidio/merchant_application.php';
                    $myprofile->add(
                        get_string('merchantapplication', 'local_lidio'),
                        $url,
                        navigation_node::TYPE_SETTING
                    );
                }
            }
        }
    }
}

/**
 * Inject redirect script if needed - runs on each page load through the before_header hook
 *
 * @return void
 */
function local_lidio_before_standard_html_head() {
    global $SESSION, $PAGE;
    
    // If there's a redirect URL set by the login event, output JavaScript to handle the redirect
    if (!empty($SESSION->lidio_redirect_after_login)) {
        $redirecturl = $SESSION->lidio_redirect_after_login;
        
        // Clear the redirect URL so it's only used once
        unset($SESSION->lidio_redirect_after_login);
        
        // Add JavaScript to handle the redirect
        $js = "
            <script type='text/javascript'>
                window.onload = function() {
                    window.location.href = '{$redirecturl}';
                }
            </script>
        ";
        
        return $js;
    }
    
    return '';
}

/**
 * User login event handler
 * 
 * @param \core\event\user_loggedin $event
 * @return bool
 */
function local_lidio_user_loggedin(\core\event\user_loggedin $event) {
    global $USER, $DB, $CFG, $SESSION;
    
    if (empty(get_config('local_lidio', 'enabled'))) {
        return true;
    }

    $userId = $event->objectid;
    
    $isMerchant = $DB->get_record('local_lidio_merchants', ['userid' => $userId]);
    
    if (!$isMerchant || $isMerchant->status === 'pending') {
        $SESSION->lidio_redirect_after_login = $CFG->wwwroot . '/local/lidio/merchant_application.php';
        return true;
    }

    if ($isMerchant && $isMerchant->kyc_status == 'pending') {
        $SESSION->lidio_redirect_after_login = $CFG->wwwroot . '/local/lidio/kyc.php';
        return true;
    }

    $SESSION->lidio_redirect_after_login = $CFG->wwwroot . '/local/lidio/merchant_dashboard.php';

    return true;
}

/**
 * Check if the current user is a merchant
 *
 * @return bool|object Returns false if not a merchant, otherwise returns the merchant record
 */
function local_lidio_is_merchant() {
    global $USER, $DB;
    
    // Check if the user is logged in
    if (!isloggedin() || isguestuser()) {
        return false;
    }
    
    $record = $DB->get_record('local_lidio_merchants', ['userid' => $USER->id]);
    return $record ? $record : false;
}

/**
 * Check if the merchant has completed KYC verification
 *
 * @param object $merchant The merchant record
 * @return bool True if KYC is complete, false otherwise
 */
function local_lidio_kyc_complete($merchant) {
    if (!$merchant) {
        return false;
    }
    
    // Check if KYC is approved
    return ($merchant->kyc_status === 'approved');
}

/**
 * Redirect to merchant application page if user is not a merchant
 * 
 * @return object|bool The merchant record if user is a merchant
 */
function local_lidio_require_merchant() {
    global $CFG;
    
    $merchant = local_lidio_is_merchant();
    if (!$merchant) {
        redirect($CFG->wwwroot . '/local/lidio/merchant_application.php',
            get_string('notamerchant', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
    
    return $merchant;
}

/**
 * Redirect to KYC verification page if merchant has not completed KYC
 * 
 * @param object $merchant The merchant record
 * @return bool True if KYC is complete
 */
function local_lidio_require_kyc($merchant) {
    global $CFG;
    
    if (!local_lidio_kyc_complete($merchant)) {
        redirect($CFG->wwwroot . '/local/lidio/kyc.php',
            get_string('kycstatus_pending', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_WARNING);
    }
    
    return true;
}

/*
function local_lidio_extend_navigation(global_navigation $navigation) {
    global $USER;

    $main_node = $navigation->add(
        get_string('merchantdashboard', 'local_lidio'),
        new moodle_url('/local/lidio/merchant.php'),
        navigation_node::TYPE_SETTING,
        null,
        'merchantdashboard'
    );
    $main_node->nodetype = 1;
    $main_node->collapse = false;
    $main_node->foreceopen = true;
}*/