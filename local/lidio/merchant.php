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
 * Merchant router - redirects to the appropriate page.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check plugin is enabled
if (empty(get_config('local_lidio', 'enabled'))) {
    throw new \core\exception\moodle_exception('plugindisabled', 'local_lidio');
}

// Require login
require_login();

// Get the ismerchant parameter if available
$ismerchant = optional_param('ismerchant', null, PARAM_BOOL);
$edit = optional_param('edit', false, PARAM_BOOL);

// Check if the user is already a merchant
$merchant = local_lidio_is_merchant();

// Router logic
if ($merchant && $merchant->status === 'approved') {
    // User is an approved merchant, redirect to dashboard
    redirect($CFG->wwwroot . '/local/lidio/merchant_dashboard.php');
} else if ($merchant && $edit) {
    // User is a merchant and wants to edit application
    redirect($CFG->wwwroot . '/local/lidio/merchant_application.php?edit=1');
} else {
    // User needs to apply or view pending application
    redirect($CFG->wwwroot . '/local/lidio/merchant_application.php');
} 