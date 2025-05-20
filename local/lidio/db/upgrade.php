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
 * Lidio payment system upgrade tasks.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for the Lidio payment system plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_local_lidio_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025052004) {
        // Define table local_lidio_payment_links to be created.
        $table = new xmldb_table('local_lidio_payment_links');

        // Adding fields to table local_lidio_payment_links.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('merchantid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('amount', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('currency', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, 'TRY');
        $table->add_field('link_code', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'active');
        $table->add_field('expiry_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('max_uses', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('current_uses', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('custom_fields', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('success_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('cancel_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_lidio_payment_links.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('merchantid', XMLDB_KEY_FOREIGN, ['merchantid'], 'local_lidio_merchants', ['id']);

        // Adding indexes to table local_lidio_payment_links.
        $table->add_index('link_code', XMLDB_INDEX_UNIQUE, ['link_code']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        // Conditionally launch create table for local_lidio_payment_links.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_lidio_transactions to be created.
        $table = new xmldb_table('local_lidio_transactions');

        // Adding fields to table local_lidio_transactions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('merchant_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payment_link_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('reference', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('amount', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('currency', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, 'TRY');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('payment_method', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payment_details', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('customer_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('customer_email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('customer_phone', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('customer_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('gateway_response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('refund_reference', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_lidio_transactions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('merchant_id', XMLDB_KEY_FOREIGN, ['merchant_id'], 'local_lidio_merchants', ['id']);
        $table->add_key('payment_link_id', XMLDB_KEY_FOREIGN, ['payment_link_id'], 'local_lidio_payment_links', ['id']);

        // Adding indexes to table local_lidio_transactions.
        $table->add_index('reference', XMLDB_INDEX_UNIQUE, ['reference']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('customer_email', XMLDB_INDEX_NOTUNIQUE, ['customer_email']);

        // Conditionally launch create table for local_lidio_transactions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add username field to merchants table if it doesn't exist.
        $table = new xmldb_table('local_lidio_merchants');
        $field = new xmldb_field('username', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'kyc_status');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            
            // Add index for username field
            $index = new xmldb_index('username', XMLDB_INDEX_UNIQUE, ['username']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        // Lidio savepoint reached.
        upgrade_plugin_savepoint(true, 2025052004, 'local', 'lidio');
    }

    return true;
} 