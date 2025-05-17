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
 * Merchant manager class for Lidio payment system.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio;

defined('MOODLE_INTERNAL') || die();

/**
 * Merchant manager class - handles merchant operations
 */
class merchant_manager {
    
    /**
     * Create a new merchant application
     *
     * @param int $userid User ID
     * @param string $fullname Full name
     * @param string $address Address
     * @param string $phone Phone number
     * @param string $idnumber ID number
     * @return int Merchant ID
     */
    public static function create_merchant($userid, $fullname, $address, $phone, $idnumber) {
        global $DB;
        
        // Create merchant record
        $record = new \stdClass();
        $record->userid = $userid;
        $record->status = 'pending';
        $record->fullname = $fullname;
        $record->address = $address;
        $record->phone = $phone;
        $record->idnumber = $idnumber;
        $record->kyc_status = 'pending';
        $record->timecreated = time();
        $record->timemodified = time();
        
        // Save to database
        $merchantid = $DB->insert_record('local_lidio_merchants', $record);
        
        return $merchantid;
    }
    
    /**
     * Change merchant status
     *
     * @param int $merchantid Merchant ID
     * @param string $status New status (pending, approved, rejected)
     * @return bool Success
     */
    public static function update_merchant_status($merchantid, $status) {
        global $DB;
        
        // Get merchant record
        $merchant = $DB->get_record('local_lidio_merchants', array('id' => $merchantid));
        if (!$merchant) {
            return false;
        }
        
        // Update status
        $merchant->status = $status;
        $merchant->timemodified = time();
        
        // Save to database
        return $DB->update_record('local_lidio_merchants', $merchant);
    }
    
    /**
     * Change KYC status
     *
     * @param int $merchantid Merchant ID
     * @param string $kyc_status New KYC status (pending, approved, rejected)
     * @return bool Success
     */
    public static function update_kyc_status($merchantid, $kyc_status) {
        global $DB;
        
        // Get merchant record
        $merchant = $DB->get_record('local_lidio_merchants', array('id' => $merchantid));
        if (!$merchant) {
            return false;
        }
        
        // Update KYC status
        $merchant->kyc_status = $kyc_status;
        $merchant->timemodified = time();
        
        // Save to database
        $result = $DB->update_record('local_lidio_merchants', $merchant);
        
        // Update document status as well
        if ($result) {
            $DB->set_field('local_lidio_documents', 'status', $kyc_status, array('merchantid' => $merchantid));
        }
        
        return $result;
    }
    
    /**
     * Get all merchants with user data
     *
     * @return array Merchants
     */
    public static function get_all_merchants() {
        global $DB;
        
        $sql = "SELECT m.*, u.firstname, u.lastname, u.email 
                FROM {local_lidio_merchants} m 
                JOIN {user} u ON u.id = m.userid 
                ORDER BY m.timecreated DESC";
        
        return $DB->get_records_sql($sql);
    }
    
    /**
     * Get merchant by ID with user data
     *
     * @param int $merchantid Merchant ID
     * @return object Merchant object
     */
    public static function get_merchant($merchantid) {
        global $DB;
        
        $sql = "SELECT m.*, u.firstname, u.lastname, u.email 
                FROM {local_lidio_merchants} m 
                JOIN {user} u ON u.id = m.userid 
                WHERE m.id = ?";
        
        return $DB->get_record_sql($sql, array($merchantid));
    }
    
    /**
     * Get merchant by user ID
     *
     * @param int $userid User ID
     * @return object|false Merchant object or false if not found
     */
    public static function get_merchant_by_user($userid) {
        global $DB;
        
        return $DB->get_record('local_lidio_merchants', array('userid' => $userid));
    }
    
    /**
     * Save a KYC document
     *
     * @param int $merchantid Merchant ID
     * @param string $type Document type
     * @param string $filepath Path to stored file
     * @param string $filename Original filename
     * @return int Document ID
     */
    public static function save_document($merchantid, $type, $filepath, $filename) {
        global $DB;
        
        $document = new \stdClass();
        $document->merchantid = $merchantid;
        $document->type = $type;
        $document->filepath = $filepath;
        $document->filename = $filename;
        $document->status = 'pending';
        $document->timecreated = time();
        $document->timemodified = time();
        
        return $DB->insert_record('local_lidio_documents', $document);
    }
    
    /**
     * Get documents for a merchant
     *
     * @param int $merchantid Merchant ID
     * @return array Documents
     */
    public static function get_documents($merchantid) {
        global $DB;
        
        return $DB->get_records('local_lidio_documents', array('merchantid' => $merchantid));
    }
} 