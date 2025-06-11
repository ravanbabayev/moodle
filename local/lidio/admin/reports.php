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
 * Admin page for Lidio reports and analytics.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check access
admin_externalpage_setup('local_lidio_reports');

// Set up the page
$title = get_string('reports', 'local_lidio');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Get statistics
$total_merchants = $DB->count_records('local_lidio_merchants');
$active_merchants = $DB->count_records('local_lidio_merchants', ['status' => 'approved']);
$total_transactions = $DB->count_records('local_lidio_transactions');
$successful_transactions = $DB->count_records('local_lidio_transactions', ['status' => 'completed']);

// Get total revenue
$total_revenue = $DB->get_field_sql("SELECT SUM(amount) FROM {local_lidio_transactions} WHERE status = 'completed'") ?: 0;

echo '<div class="row">';
echo '<div class="col-md-3">';
echo '<div class="card text-white bg-primary">';
echo '<div class="card-header">Total Merchants</div>';
echo '<div class="card-body"><h4>' . $total_merchants . '</h4></div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card text-white bg-success">';
echo '<div class="card-header">Active Merchants</div>';
echo '<div class="card-body"><h4>' . $active_merchants . '</h4></div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card text-white bg-info">';
echo '<div class="card-header">Total Transactions</div>';
echo '<div class="card-body"><h4>' . $total_transactions . '</h4></div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card text-white bg-warning">';
echo '<div class="card-header">Total Revenue</div>';
echo '<div class="card-body"><h4>' . number_format($total_revenue, 2) . ' TL</h4></div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<br>';
echo '<p>Detailed analytics and charts will be implemented here.</p>';

echo $OUTPUT->footer(); 