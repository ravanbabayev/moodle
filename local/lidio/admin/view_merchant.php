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
 * Admin page to view Lidio merchant details.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check access
admin_externalpage_setup('local_lidio_merchants');

// Get merchant ID
$id = required_param('id', PARAM_INT);

// Get merchant record
$merchant = $DB->get_record('local_lidio_merchants', array('id' => $id), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $merchant->userid), '*', MUST_EXIST);

// Get documents
$documents = $DB->get_records('local_lidio_documents', array('merchantid' => $id));

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('merchantdetails', 'local_lidio'));

// Display user profile link
$profileurl = $CFG->wwwroot . '/user/profile.php?id=' . $user->id;
echo html_writer::tag('p', 
    get_string('userprofile', 'local_lidio') . ': ' . 
    html_writer::link($profileurl, fullname($user)), 
    array('class' => 'lead')
);

// Display merchant details
$merchanttable = new html_table();
$merchanttable->attributes['class'] = 'table table-striped';
$merchanttable->head = array(get_string('field', 'local_lidio'), get_string('value', 'local_lidio'));
$merchanttable->colclasses = array('field', 'value');

// Add merchant details to table
$merchanttable->data[] = array(get_string('fullname', 'local_lidio'), $merchant->fullname);
$merchanttable->data[] = array(get_string('address', 'local_lidio'), $merchant->address);
$merchanttable->data[] = array(get_string('phone', 'local_lidio'), $merchant->phone);
$merchanttable->data[] = array(get_string('idnumber', 'local_lidio'), $merchant->idnumber);

// Status
if ($merchant->status === 'pending') {
    $statustext = get_string('pending', 'local_lidio');
    $statusclass = 'warning';
} else if ($merchant->status === 'approved') {
    $statustext = get_string('approved', 'local_lidio');
    $statusclass = 'success';
} else {
    $statustext = get_string('rejected', 'local_lidio');
    $statusclass = 'danger';
}
$status = html_writer::tag('span', $statustext, array('class' => 'badge badge-' . $statusclass));
$merchanttable->data[] = array(get_string('merchantstatus', 'local_lidio'), $status);

// KYC status
if ($merchant->kyc_status === 'pending') {
    $kycstatustext = get_string('pending', 'local_lidio');
    $kycstatusclass = 'warning';
} else if ($merchant->kyc_status === 'approved') {
    $kycstatustext = get_string('approved', 'local_lidio');
    $kycstatusclass = 'success';
} else {
    $kycstatustext = get_string('rejected', 'local_lidio');
    $kycstatusclass = 'danger';
}
$kycstatus = html_writer::tag('span', $kycstatustext, array('class' => 'badge badge-' . $kycstatusclass));
$merchanttable->data[] = array(get_string('kycstatus', 'local_lidio'), $kycstatus);

// Registration date
$merchanttable->data[] = array(
    get_string('registrationdate', 'local_lidio'), 
    userdate($merchant->timecreated, get_string('strftimedatetime', 'langconfig'))
);

// Display the merchant details table
echo html_writer::table($merchanttable);

// Display documents section if there are any
if (!empty($documents)) {
    echo $OUTPUT->heading(get_string('documents', 'local_lidio'), 3);
    
    $documentstable = new html_table();
    $documentstable->attributes['class'] = 'table table-striped';
    $documentstable->head = array(
        get_string('documenttype', 'local_lidio'),
        get_string('filename', 'local_lidio'),
        get_string('status', 'local_lidio'),
        get_string('actions', 'local_lidio')
    );
    
    foreach ($documents as $document) {
        // Document status
        if ($document->status === 'pending') {
            $docstatustext = get_string('pending', 'local_lidio');
            $docstatusclass = 'warning';
        } else if ($document->status === 'approved') {
            $docstatustext = get_string('approved', 'local_lidio');
            $docstatusclass = 'success';
        } else {
            $docstatustext = get_string('rejected', 'local_lidio');
            $docstatusclass = 'danger';
        }
        $docstatus = html_writer::tag('span', $docstatustext, 
                        array('class' => 'badge badge-' . $docstatusclass));
        
        // Document download link
        $downloadurl = $CFG->wwwroot . '/local/lidio/admin/download.php?id=' . $document->id;
        $downloadlink = html_writer::link($downloadurl, get_string('download', 'local_lidio'), 
                            array('class' => 'btn btn-sm btn-primary'));
        
        // Document type
        $documentType = get_string($document->type, 'local_lidio');
        
        $documentstable->data[] = array(
            $documentType,
            $document->filename,
            $docstatus,
            $downloadlink
        );
    }
    
    echo html_writer::table($documentstable);
}

// Back button
$backurl = $CFG->wwwroot . '/local/lidio/admin/merchants.php';
echo html_writer::div(
    html_writer::link($backurl, get_string('back', 'local_lidio'), 
        array('class' => 'btn btn-secondary')),
    'mt-4'
);

echo $OUTPUT->footer(); 