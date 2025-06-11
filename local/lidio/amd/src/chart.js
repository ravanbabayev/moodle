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
 * Chart.js module for the Lidio payment system.
 *
 * @module     local_lidio/chart
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'https://cdn.jsdelivr.net/npm/chart.js'], function($, Chart) {
    /**
     * Initialize the chart module
     */
    var init = function() {
        // This function will be called when the module is loaded
        // The actual chart initialization is handled inline in the page
        // This module just ensures Chart.js is loaded
        console.log('Chart.js module loaded');
        
        // Make Chart globally available for inline scripts
        window.Chart = Chart;
    };

    return {
        init: init
    };
}); 