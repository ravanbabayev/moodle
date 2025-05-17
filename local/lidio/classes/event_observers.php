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
 * Event observers for the Lidio payment system.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class
 */
class event_observers {
    
    /**
     * Observer function for user login event
     *
     * @param \core\event\user_loggedin $event The event
     * @return bool Success
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $CFG, $DB, $USER, $SESSION;
        
        // Check if the plugin is enabled
        if (empty(get_config('local_lidio', 'enabled'))) {
            return true;
        }
        
        // Skip admin users and other special cases
        if (is_siteadmin() || isguestuser() || !isloggedin()) {
            return true;
        }
        
        // Allow users to skip the redirect check for a session (e.g., for admins doing testing)
        if (!empty($SESSION->lidio_skip_redirect)) {
            return true;
        }
        
        // Add a delay param to URL to avoid immediate redirect loop in case there's a problem
        $delay = time();
        
        // Check merchant status
        $merchant = $DB->get_record('local_lidio_merchants', ['userid' => $USER->id]);
        
        // Check if user has capability to be a merchant
        $systemcontext = \context_system::instance();
        if (!has_capability('local/lidio:bemerchant', $systemcontext)) {
            return true;
        }
        
        if (!$merchant) {
            // User is not a merchant, redirect to merchant application
            $redirecturl = $CFG->wwwroot . '/local/lidio/merchant.php?delay=' . $delay;
            
            // Use JavaScript for redirect to prevent login redirect loop issues
            $SESSION->lidio_redirect_after_login = $redirecturl;
            return true;
        } 
        
        // If merchant but KYC is not approved, redirect to KYC page
        if ($merchant && $merchant->kyc_status !== 'approved') {
            $redirecturl = $CFG->wwwroot . '/local/lidio/kyc.php?merchantid=' . $merchant->id . '&delay=' . $delay;
            
            // Use JavaScript for redirect to prevent login redirect loop issues
            $SESSION->lidio_redirect_after_login = $redirecturl;
            return true;
        }
        
        return true;
    }
} 