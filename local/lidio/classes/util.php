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
 * Utility functions for the Lidio payment system plugin.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility functions for the Lidio payment system.
 */
class util {
    /**
     * Check if a merchant exists and if their KYC is approved.
     *
     * @param int $userid The user ID to check
     * @return array Array with 'is_merchant' and 'kyc_approved' keys
     */
    public static function check_merchant_status($userid) {
        global $DB;
        
        $result = [
            'is_merchant' => false,
            'kyc_approved' => false
        ];
        
        // Check if the user is a merchant
        $merchant = $DB->get_record('local_lidio_merchants', ['userid' => $userid]);
        if ($merchant) {
            $result['is_merchant'] = true;
            if ($merchant->kyc_status === 'approved' && $merchant->status === 'approved') {
                $result['kyc_approved'] = true;
            }
        }
        
        return $result;
    }
    
    /**
     * Process a file upload for KYC verification documents.
     *
     * @param int $merchantid The merchant ID
     * @param string $filecontent The file content
     * @param string $filename The original filename
     * @param string $documenttype The type of document
     * @return int The document ID if successful
     */
    public static function save_document($merchantid, $filecontent, $filename, $documenttype) {
        global $DB, $CFG;
        
        // Create storage directory if it doesn't exist
        $storage_dir = $CFG->dataroot . '/lidio/documents/' . $merchantid;
        if (!file_exists($storage_dir)) {
            mkdir($storage_dir, 0755, true);
        }
        
        // Generate unique filename
        $filepath = $storage_dir . '/' . $documenttype . '_' . time() . '_' . $filename;
        
        // Save file
        file_put_contents($filepath, $filecontent);
        
        // Create document record
        $doc_record = new \stdClass();
        $doc_record->merchantid = $merchantid;
        $doc_record->type = $documenttype;
        $doc_record->filepath = $filepath;
        $doc_record->filename = $filename;
        $doc_record->status = 'pending';
        $doc_record->timecreated = time();
        $doc_record->timemodified = time();
        
        // Save to database
        $documentid = $DB->insert_record('local_lidio_documents', $doc_record);
        
        return $documentid;
    }
} 