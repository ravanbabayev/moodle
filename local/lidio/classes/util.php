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
 * Utility class for Lidio payment system.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lidio;

defined('MOODLE_INTERNAL') || die();

/**
 * Class util
 *
 * This class provides utility methods for the Lidio payment system.
 */
class util {
    
    /**
     * Format a currency amount
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code (default: TRY)
     * @return string Formatted amount
     */
    public static function format_amount($amount, $currency = 'TRY') {
        if ($currency === 'TRY') {
            return number_format($amount, 2, ',', '.') . ' ₺';
        } else {
            return number_format($amount, 2) . ' ' . $currency;
        }
    }
    
    /**
     * Format a date for display
     *
     * @param int $timestamp Unix timestamp
     * @param string $format Date format (default: 'd.m.Y H:i')
     * @return string Formatted date
     */
    public static function format_date($timestamp, $format = 'd.m.Y H:i') {
        if (!$timestamp) {
            return '-';
        }
        
        return date($format, $timestamp);
    }
    
    /**
     * Get status badge HTML
     *
     * @param string $status Status (pending, approved, rejected)
     * @return string HTML for badge
     */
    public static function get_status_badge($status) {
        global $OUTPUT;
        $class = '';
        
        switch ($status) {
            case 'pending':
                $class = 'warning';
                $text = get_string('pending', 'local_lidio');
                break;
            case 'approved':
                $class = 'success';
                $text = get_string('approved', 'local_lidio');
                break;
            case 'rejected':
                $class = 'danger';
                $text = get_string('rejected', 'local_lidio');
                break;
            default:
                $class = 'info';
                $text = $status;
        }
        
        return '<span class="badge badge-' . $class . '">' . $text . '</span>';
    }
    
    /**
     * Validate an IBAN
     *
     * @param string $iban IBAN to validate
     * @return bool Is valid
     */
    public static function validate_iban($iban) {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));
        
        // Basic format check
        if (!preg_match('/^[A-Z0-9]+$/', $iban)) {
            return false;
        }
        
        // Length check (simple check for now, can be expanded to country-specific rules)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate a phone number
     *
     * @param string $phone Phone number to validate
     * @return bool Is valid
     */
    public static function validate_phone($phone) {
        // Allow digits, +, -, spaces, and parentheses
        // Should be at least 6 digits
        return preg_match('/^[0-9+\-\s()]{6,20}$/', $phone);
    }
    
    /**
     * Generate a unique reference ID
     *
     * @param string $prefix Prefix for the reference (default: LID)
     * @return string Unique reference ID
     */
    public static function generate_reference($prefix = 'LID') {
        // Generate a reference ID in format: LID-YYYYMMDD-XXXXX
        $date = date('Ymd');
        $random = substr(md5(uniqid(mt_rand(), true)), 0, 5);
        
        return $prefix . '-' . $date . '-' . strtoupper($random);
    }
} 