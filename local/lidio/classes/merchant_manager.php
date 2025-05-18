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
 * Merchant manager class.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio;

defined('MOODLE_INTERNAL') || die();

/**
 * Class merchant_manager
 *
 * This class provides methods for managing merchants in the Lidio payment system.
 */
class merchant_manager {
    
    /**
     * Get merchant by user ID
     *
     * @param int $userid User ID
     * @return object|bool Merchant record or false if not found
     */
    public static function get_merchant_by_userid($userid) {
        global $DB;
        
        return $DB->get_record('local_lidio_merchants', ['userid' => $userid]);
    }
    
    /**
     * Get merchant by merchant ID
     *
     * @param int $id Merchant ID
     * @return object|bool Merchant record or false if not found
     */
    public static function get_merchant_by_id($id) {
        global $DB;
        
        return $DB->get_record('local_lidio_merchants', ['id' => $id]);
    }
    
    /**
     * Update merchant status
     *
     * @param int $merchantid Merchant ID
     * @param string $status New status (pending, approved, rejected)
     * @return bool Success
     */
    public static function update_merchant_status($merchantid, $status) {
        global $DB;
        
        $validstatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $validstatuses)) {
            return false;
        }
        
        $merchant = self::get_merchant_by_id($merchantid);
        if (!$merchant) {
            return false;
        }
        
        $merchant->status = $status;
        $merchant->timemodified = time();
        
        return $DB->update_record('local_lidio_merchants', $merchant);
    }
    
    /**
     * Update merchant KYC status
     *
     * @param int $merchantid Merchant ID
     * @param string $status New KYC status (pending, approved, rejected)
     * @return bool Success
     */
    public static function update_kyc_status($merchantid, $status) {
        global $DB;
        
        $validstatuses = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $validstatuses)) {
            return false;
        }
        
        $merchant = self::get_merchant_by_id($merchantid);
        if (!$merchant) {
            return false;
        }
        
        $merchant->kyc_status = $status;
        $merchant->timemodified = time();
        
        // Update all documents as well
        $DB->set_field('local_lidio_documents', 'status', $status, ['merchantid' => $merchantid]);
        
        return $DB->update_record('local_lidio_merchants', $merchant);
    }
    
    /**
     * Get all merchant documents
     *
     * @param int $merchantid Merchant ID
     * @return array Array of document records
     */
    public static function get_merchant_documents($merchantid) {
        global $DB;
        
        return $DB->get_records('local_lidio_documents', ['merchantid' => $merchantid]);
    }
    
    /**
     * Save a document for a merchant
     *
     * @param object $document Document data
     * @return int|bool New document ID or false on failure
     */
    public static function save_document($document) {
        global $DB;
        
        if (empty($document->merchantid) || empty($document->type) || empty($document->filename)) {
            return false;
        }
        
        // Set default values
        if (!isset($document->status)) {
            $document->status = 'pending';
        }
        
        if (!isset($document->timecreated)) {
            $document->timecreated = time();
        }
        
        if (!isset($document->timemodified)) {
            $document->timemodified = time();
        }
        
        return $DB->insert_record('local_lidio_documents', $document);
    }
    
    /**
     * Update a document
     *
     * @param object $document Document data
     * @return bool Success
     */
    public static function update_document($document) {
        global $DB;
        
        if (empty($document->id)) {
            return false;
        }
        
        $document->timemodified = time();
        
        return $DB->update_record('local_lidio_documents', $document);
    }
    
    /**
     * Delete a document
     *
     * @param int $documentid Document ID
     * @return bool Success
     */
    public static function delete_document($documentid) {
        global $DB;
        
        return $DB->delete_records('local_lidio_documents', ['id' => $documentid]);
    }
    
    /**
     * Get all merchants with status
     *
     * @param string $status Filter by status (optional)
     * @return array Array of merchant records with user data
     */
    public static function get_all_merchants($status = null) {
        global $DB;
        
        $sql = "SELECT m.*, u.firstname, u.lastname, u.email 
                FROM {local_lidio_merchants} m 
                JOIN {user} u ON u.id = m.userid";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE m.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY m.timecreated DESC";
        
        return $DB->get_records_sql($sql, $params);
    }
} 