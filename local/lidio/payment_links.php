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
 * Payment links management page.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');
require_once($CFG->libdir . '/formslib.php');

// Check login
require_login();

// Get current user
$userid = $USER->id;

// Set up the page
$title = get_string('paymentlinks', 'local_lidio');
$url = new moodle_url('/local/lidio/payment_links.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add TailwindCSS
$PAGE->requires->css(new moodle_url('/local/lidio/styles.css'));

// Add Bootstrap for modal functionality
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'));

$PAGE->requires->js('/local/lidio/scripts.js');

// Get merchant data
$merchant = $DB->get_record('local_lidio_merchants', array('userid' => $userid));

// Check if the user is a merchant
if (!$merchant) {
    redirect(
        new moodle_url('/local/lidio/merchant_application.php'),
        get_string('notamerchant', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Check if merchant is approved
if ($merchant->status !== 'approved') {
    redirect(
        new moodle_url('/local/lidio/merchant_dashboard.php'),
        get_string('merchantstatusnotapproved', 'local_lidio'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Process actions
$action = optional_param('action', '', PARAM_ALPHA);
$linkid = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($action && $linkid) {
    $link = $DB->get_record('local_lidio_payment_links', array('id' => $linkid, 'merchantid' => $merchant->id), '*', MUST_EXIST);
    
    // Handle activation/deactivation
    if ($action === 'activate' && $link->status !== 'active') {
        $link->status = 'active';
        $link->timemodified = time();
        $DB->update_record('local_lidio_payment_links', $link);
        
        redirect(
            $url,
            get_string('paymentlinkupdated', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else if ($action === 'deactivate' && $link->status === 'active') {
        $link->status = 'inactive';
        $link->timemodified = time();
        $DB->update_record('local_lidio_payment_links', $link);
        
        redirect(
            $url,
            get_string('paymentlinkupdated', 'local_lidio'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else if ($action === 'delete') {
        // Handle deletion
        if ($confirm) {
            $DB->delete_records('local_lidio_payment_links', array('id' => $linkid, 'merchantid' => $merchant->id));
            
            redirect(
                $url,
                get_string('paymentlinkdeleted', 'local_lidio'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            // Display confirmation page
            echo $OUTPUT->header();
            
            $confirmurl = new moodle_url('/local/lidio/payment_links.php', 
                           array('action' => 'delete', 'id' => $linkid, 'confirm' => 1));
            $cancelurl = new moodle_url('/local/lidio/payment_links.php');
            
            echo $OUTPUT->confirm(
                get_string('confirmdeletepaymentlink', 'local_lidio'),
                $confirmurl,
                $cancelurl
            );
            
            echo $OUTPUT->footer();
            exit;
        }
    }
}

// Define the payment link creation form
class create_payment_link_form extends moodleform {
    protected function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $merchant = $this->_customdata['merchant'];
        
        // Basic Information Header
        $mform->addElement('header', 'basicinfo', get_string('generalinformation', 'local_lidio'));
        
        // Title
        $mform->addElement('text', 'title', get_string('paymentlinktitle', 'local_lidio'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        
        // Description
        $mform->addElement('textarea', 'description', get_string('paymentlinkdescription', 'local_lidio'),
                          array('rows' => 3, 'cols' => 50));
        $mform->setType('description', PARAM_TEXT);
        
        // Amount
        $mform->addElement('text', 'amount', get_string('paymentamount', 'local_lidio'));
        $mform->setType('amount', PARAM_FLOAT);
        $mform->addRule('amount', get_string('required'), 'required', null, 'client');
        
        // Currency
        $currencies = array(
            'TRY' => 'TRY - Turkish Lira',
            'USD' => 'USD - US Dollar',
            'EUR' => 'EUR - Euro'
        );
        $mform->addElement('select', 'currency', get_string('currency', 'local_lidio'), $currencies);
        $mform->setDefault('currency', 'TRY');
        
        // Product Information Header
        $mform->addElement('header', 'productinfo', get_string('productinformation', 'local_lidio'));
        
        // Product Name
        $mform->addElement('text', 'product_name', get_string('productname', 'local_lidio'));
        $mform->setType('product_name', PARAM_TEXT);
        $mform->addHelpButton('product_name', 'productname', 'local_lidio');
        
        // Product Image Upload
        $mform->addElement('filemanager', 'product_image', get_string('productimage', 'local_lidio'), null, 
                          array('subdirs' => 0, 'maxbytes' => 5242880, 'maxfiles' => 1, 'accepted_types' => array('.jpg', '.jpeg', '.png')));
        $mform->addHelpButton('product_image', 'productimage', 'local_lidio');
        
        // Set default value for file manager
        if (isset($this->_customdata['product_image'])) {
            $mform->setDefault('product_image', $this->_customdata['product_image']);
        }
        
        // Product Description
        $mform->addElement('textarea', 'product_description', get_string('productdescription', 'local_lidio'),
                          array('rows' => 4, 'cols' => 50));
        $mform->setType('product_description', PARAM_TEXT);
        $mform->addHelpButton('product_description', 'productdescription', 'local_lidio');
        
        // Customer Contact Requirements
        $mform->addElement('header', 'customerinfo', get_string('customercontactrequirement', 'local_lidio'));
        
        $contact_options = array(
            'phone_or_email' => get_string('phone_or_email', 'local_lidio'),
            'phone_required' => get_string('phone_required', 'local_lidio'),
            'email_required' => get_string('email_required', 'local_lidio'),
            'both_required' => get_string('both_required', 'local_lidio')
        );
        $mform->addElement('select', 'customer_contact_requirement', 
                          get_string('customercontactrequirement', 'local_lidio'), $contact_options);
        $mform->setDefault('customer_contact_requirement', 'phone_or_email');
        $mform->addHelpButton('customer_contact_requirement', 'customercontactrequirement', 'local_lidio');
        
        // Advanced Settings Header
        $mform->addElement('header', 'advancedsettings', get_string('advancedsettings', 'core_admin'));
        
        // Expiry date
        $mform->addElement('date_selector', 'expiry_date', get_string('expirydate', 'local_lidio'), array('optional' => true));
        
        // Maximum uses
        $mform->addElement('text', 'max_uses', get_string('maxuses', 'local_lidio'));
        $mform->setType('max_uses', PARAM_INT);
        
        // Success URL
        $mform->addElement('text', 'success_url', get_string('successurl', 'local_lidio'));
        $mform->setType('success_url', PARAM_URL);
        
        // Cancel URL
        $mform->addElement('text', 'cancel_url', get_string('cancelurl', 'local_lidio'));
        $mform->setType('cancel_url', PARAM_URL);
        
        // Hidden merchant ID
        $mform->addElement('hidden', 'merchantid', $merchant->id);
        $mform->setType('merchantid', PARAM_INT);
        
        // Action buttons
        $this->add_action_buttons(true, get_string('createlink', 'local_lidio'));
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate amount (must be greater than 0)
        if ($data['amount'] <= 0) {
            $errors['amount'] = get_string('invalidamount', 'local_lidio');
        }
        
        // Validate max uses if provided (must be a positive integer)
        if (!empty($data['max_uses']) && $data['max_uses'] <= 0) {
            $errors['max_uses'] = get_string('invalidmaxuses', 'local_lidio');
        }
        
        return $errors;
    }
}

// Initialize file manager for product images
$draftitemid = file_get_submitted_draft_itemid('product_image');
file_prepare_draft_area($draftitemid, context_system::instance()->id, 'local_lidio', 'product_image', null,
                       array('subdirs' => 0, 'maxbytes' => 5242880, 'maxfiles' => 1));

// Create instance of the form
$form = new create_payment_link_form($url, array('merchant' => $merchant, 'product_image' => $draftitemid));

// Process form submission
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/lidio/merchant_dashboard.php'));
} else if ($data = $form->get_data()) {
    // Generate unique link code
    $link_code = md5(uniqid(rand(), true));
    
    // Handle file upload for product image
    $product_image_url = '';
    if (!empty($data->product_image)) {
        $context = context_system::instance();
        $fs = get_file_storage();
        
        // Save the file to Moodle's file system
        $files = $fs->get_area_files($context->id, 'local_lidio', 'product_image', $data->product_image, 'filename', false);
        if (!empty($files)) {
            $file = reset($files);
            $product_image_url = moodle_url::make_pluginfile_url(
                $context->id,
                'local_lidio',
                'product_image',
                $data->product_image,
                '/',
                $file->get_filename()
            )->out();
        }
    }
    
    // Prepare record
    $record = new stdClass();
    $record->merchantid = $merchant->id;
    $record->title = $data->title;
    $record->description = $data->description;
    $record->amount = $data->amount;
    $record->currency = $data->currency;
    $record->link_code = $link_code;
    $record->status = 'active';
    $record->expiry_date = !empty($data->expiry_date) ? $data->expiry_date : null;
    $record->max_uses = !empty($data->max_uses) ? $data->max_uses : null;
    $record->current_uses = 0;
    $record->success_url = $data->success_url;
    $record->cancel_url = $data->cancel_url;
    
    // Add product information fields
    $record->product_name = !empty($data->product_name) ? $data->product_name : null;
    $record->product_image = $product_image_url;
    $record->product_description = !empty($data->product_description) ? $data->product_description : null;
    $record->customer_contact_requirement = $data->customer_contact_requirement;
    
    $record->timecreated = time();
    $record->timemodified = time();
    
    // Process file upload after getting the record ID
    $recordid = $DB->insert_record('local_lidio_payment_links', $record);
    
    // Handle file manager upload
    if (!empty($data->product_image)) {
        $draftitemid = $data->product_image;
        $context = context_system::instance();
        
        file_save_draft_area_files($draftitemid, $context->id, 'local_lidio', 'product_image', $recordid,
                                  array('subdirs' => 0, 'maxbytes' => 5242880, 'maxfiles' => 1));
        
        // Update the record with the actual file URL
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_lidio', 'product_image', $recordid, 'filename', false);
        if (!empty($files)) {
            $file = reset($files);
            $fileurl = moodle_url::make_pluginfile_url(
                $context->id,
                'local_lidio',
                'product_image',
                $recordid,
                '/',
                $file->get_filename()
            )->out();
            
            $DB->set_field('local_lidio_payment_links', 'product_image', $fileurl, array('id' => $recordid));
        }
    }
    
    redirect($url, get_string('paymentlinkcreated', 'local_lidio'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Get all payment links for this merchant
$payment_links = $DB->get_records('local_lidio_payment_links', array('merchantid' => $merchant->id), 'timecreated DESC');

// Display the page
echo $OUTPUT->header();

// Display the form in a modal
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?php echo get_string('paymentlinks', 'local_lidio'); ?></h1>
            <p class="mt-1 text-sm text-gray-500">Create and manage payment links for your customers</p>
        </div>
        <a href="<?php echo new moodle_url('/local/lidio/create_payment_link.php'); ?>" class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
            <i class="fas fa-plus-circle mr-3"></i><?php echo get_string('createpaymentlink', 'local_lidio'); ?>
        </a>
    </div>

    <!-- Create Payment Link Form (Hidden by default) -->
    <div id="createFormContainer" class="bg-white rounded-xl shadow-lg mb-8" style="display: none;">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-lg font-medium text-gray-900"><?php echo get_string('createpaymentlink', 'local_lidio'); ?></h2>
        </div>
        <div class="p-6">
            <?php $form->display(); ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg mb-8">
        <div class="border-b border-gray-200 px-6 py-5">
            <h2 class="text-lg font-medium text-gray-900">Your Payment Links</h2>
        </div>
        <?php if (!empty($payment_links)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('paymentlinktitle', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('amount', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('linkstatus', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('currentuses', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('date', 'local_lidio'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php echo get_string('actions', 'local_lidio'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payment_links as $link): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                    <i class="fas fa-link"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $link->title; ?></div>
                                    <?php if (!empty($link->description)): ?>
                                    <div class="text-sm text-gray-500"><?php echo $link->description; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo number_format($link->amount, 2); ?></div>
                            <div class="text-sm text-gray-500"><?php echo $link->currency; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $status_class = '';
                            $status_icon = '';
                            switch ($link->status) {
                                case 'active':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_icon = '<i class="fas fa-check-circle mr-1"></i>';
                                    break;
                                case 'inactive':
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_icon = '<i class="fas fa-pause-circle mr-1"></i>';
                                    break;
                                case 'expired':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_icon = '<i class="fas fa-times-circle mr-1"></i>';
                                    break;
                            }
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                <?php echo $status_icon . get_string($link->status, 'local_lidio'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm text-gray-900">
                                <?php 
                                echo $link->current_uses;
                                if (!empty($link->max_uses)) {
                                    echo ' / ' . $link->max_uses;
                                }
                                ?>
                                </div>
                                <?php if (!empty($link->max_uses) && $link->current_uses > 0): ?>
                                <div class="ml-2 w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo min(100, ($link->current_uses / $link->max_uses) * 100); ?>%"></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo userdate($link->timecreated, get_string('strftimedate', 'langconfig')); ?></div>
                            <div class="text-sm text-gray-500"><?php echo userdate($link->timecreated, get_string('strftimetime', 'langconfig')); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <button class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 copy-link" data-link="<?php echo (new moodle_url('/local/lidio/pay.php', array('code' => $link->link_code)))->out(); ?>" title="<?php echo get_string('copypaymentlink', 'local_lidio'); ?>">
                                    <i class="fas fa-copy"></i>
                                </button>
                                
                                <a href="<?php echo new moodle_url('/local/lidio/edit_payment_link.php', array('id' => $link->id)); ?>" class="inline-flex items-center p-2 border border-blue-300 rounded-md text-sm font-medium bg-white text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200" title="Edit Payment Link">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if (!empty($link->merchant_contact_info)): ?>
                                <button class="inline-flex items-center p-2 border border-green-300 rounded-md text-sm font-medium bg-white text-green-700 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200 share-link" data-link-id="<?php echo $link->id; ?>" data-contact="<?php echo htmlspecialchars($link->merchant_contact_info); ?>" data-method="<?php echo $link->sharing_method; ?>" title="Share Payment Link">
                                    <i class="fas fa-share"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($link->status === 'active'): ?>
                                <a href="<?php echo new moodle_url('/local/lidio/payment_links.php', array('action' => 'deactivate', 'id' => $link->id)); ?>" class="inline-flex items-center p-2 border border-gray-300 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200" title="<?php echo get_string('deactivate', 'local_lidio'); ?>">
                                    <i class="fas fa-pause"></i>
                                </a>
                                <?php else: ?>
                                <a href="<?php echo new moodle_url('/local/lidio/payment_links.php', array('action' => 'activate', 'id' => $link->id)); ?>" class="inline-flex items-center p-2 border border-green-500 rounded-md text-sm font-medium bg-white text-green-700 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200" title="<?php echo get_string('activate', 'local_lidio'); ?>">
                                    <i class="fas fa-play"></i>
                                </a>
                                <?php endif; ?>
                                
                                <a href="<?php echo new moodle_url('/local/lidio/payment_links.php', array('action' => 'delete', 'id' => $link->id)); ?>" class="inline-flex items-center p-2 border border-red-300 rounded-md text-sm font-medium bg-white text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200" title="<?php echo get_string('delete', 'local_lidio'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="py-16 text-center">
            <div class="inline-block p-6 rounded-full bg-indigo-100 text-indigo-600 mb-4">
                <i class="fas fa-link text-5xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No payment links yet</h3>
            <p class="text-gray-500 max-w-md mx-auto mb-6"><?php echo get_string('nopaymentlinks', 'local_lidio'); ?> Create your first payment link to start accepting payments.</p>
            <a href="<?php echo new moodle_url('/local/lidio/create_payment_link.php'); ?>" class="inline-flex items-center px-5 py-3 border border-transparent rounded-lg shadow-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                <i class="fas fa-plus-circle mr-2"></i> <?php echo get_string('createpaymentlink', 'local_lidio'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Tips Section -->
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-lg p-3">
                        <i class="fas fa-lightbulb text-2xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-bold text-white">Tips for Creating Effective Payment Links</h3>
                        <p class="text-indigo-100 mt-1">Optimize your payment process with these best practices</p>
                    </div>
                </div>
                <div>
                    <button class="text-white hover:text-indigo-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white bg-opacity-10 rounded-lg p-4 backdrop-blur-sm">
                    <div class="text-white text-lg font-medium mb-2"><i class="fas fa-clipboard-check mr-2"></i> Clear Description</div>
                    <p class="text-indigo-100 text-sm">Include a clear and concise description to inform customers exactly what they're paying for.</p>
                </div>
                <div class="bg-white bg-opacity-10 rounded-lg p-4 backdrop-blur-sm">
                    <div class="text-white text-lg font-medium mb-2"><i class="fas fa-money-bill-wave mr-2"></i> Fair Pricing</div>
                    <p class="text-indigo-100 text-sm">Set appropriate prices and consider offering special discounts for recurring customers.</p>
                </div>
                <div class="bg-white bg-opacity-10 rounded-lg p-4 backdrop-blur-sm">
                    <div class="text-white text-lg font-medium mb-2"><i class="fas fa-share-alt mr-2"></i> Share Effectively</div>
                    <p class="text-indigo-100 text-sm">Share your payment links across multiple channels to maximize visibility and conversions.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Form styling */
#createFormContainer .form-group {
    margin-bottom: 1.5rem;
}

#createFormContainer .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

#createFormContainer input[type="text"],
#createFormContainer input[type="email"], 
#createFormContainer input[type="url"],
#createFormContainer input[type="number"],
#createFormContainer select,
#createFormContainer textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
    line-height: 1.5;
    box-sizing: border-box;
}

#createFormContainer input:focus,
#createFormContainer select:focus,
#createFormContainer textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

#createFormContainer .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    margin-right: 0.5rem;
    border: none;
    cursor: pointer;
}

#createFormContainer .btn-primary {
    background-color: #3b82f6;
    color: white;
}

#createFormContainer .btn-secondary {
    background-color: #6b7280;
    color: white;
}

#createFormContainer .helptooltip {
    margin-left: 0.25rem;
}

/* Fix for file manager */
#createFormContainer .filemanager {
    min-height: 100px;
    border: 2px dashed #d1d5db;
    border-radius: 0.375rem;
    padding: 1rem;
    text-align: center;
}

/* Collapsible headers */
#createFormContainer .collapsible-actions {
    background-color: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    overflow: hidden;
}

#createFormContainer .collapsible-actions legend {
    background-color: #f1f5f9;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.75rem 1rem;
    margin: 0;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    width: 100%;
}

#createFormContainer .collapsible-actions .fcontainer {
    padding: 1rem;
}

#createFormContainer .fitem {
    margin-bottom: 1rem;
}

#createFormContainer .fitemtitle {
    margin-bottom: 0.5rem;
}

#createFormContainer .felement {
    margin-bottom: 0.25rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy payment link functionality
    document.querySelectorAll('.copy-link').forEach(button => {
        button.addEventListener('click', function() {
            const link = this.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                // Show success feedback
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.classList.remove('text-gray-700');
                this.classList.add('text-green-700', 'bg-green-50');
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('text-green-700', 'bg-green-50');
                    this.classList.add('text-gray-700');
                }, 2000);
            });
        });
    });

    // Share payment link functionality
    document.querySelectorAll('.share-link').forEach(button => {
        button.addEventListener('click', function() {
            const linkId = this.getAttribute('data-link-id');
            const contact = this.getAttribute('data-contact');
            const method = this.getAttribute('data-method');
            
            // Create modal for sharing
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            modal.innerHTML = `
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg font-medium text-gray-900">Share Payment Link</h3>
                        <div class="mt-4 px-7 py-3">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Recipient</label>
                                <input type="text" id="recipient" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter phone number or email" value="${contact}">
                            </div>
                                                         <div class="mb-4">
                                 <label class="block text-sm font-medium text-gray-700 mb-2">Method</label>
                                 <div class="text-sm text-gray-600 px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
                                     Auto-detect based on contact format
                                 </div>
                             </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Message (Optional)</label>
                                <textarea id="shareMessage" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Add a personal message..."></textarea>
                            </div>
                        </div>
                        <div class="items-center px-4 py-3">
                            <button id="confirmShare" class="px-4 py-2 bg-indigo-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-300 mr-2">
                                Share Link
                            </button>
                            <button id="cancelShare" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Handle modal actions
            modal.querySelector('#cancelShare').addEventListener('click', function() {
                document.body.removeChild(modal);
            });
            
                         modal.querySelector('#confirmShare').addEventListener('click', function() {
                 const recipient = modal.querySelector('#recipient').value;
                 const message = modal.querySelector('#shareMessage').value;
                 
                 if (!recipient) {
                     alert('Please enter a recipient');
                     return;
                 }
                 
                 // Show loading state
                 this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sharing...';
                 this.disabled = true;
                 
                 // Send AJAX request to share link (method will be auto-detected)
                 fetch('<?php echo new moodle_url('/local/lidio/share_link.php'); ?>', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                     },
                     body: JSON.stringify({
                         link_id: linkId,
                         recipient: recipient,
                         method: '', // Auto-detect
                         message: message
                     })
                 })
                .then(response => response.json())
                .then(data => {
                    document.body.removeChild(modal);
                    if (data.success) {
                        alert('Payment link shared successfully!');
                    } else {
                        alert('Error sharing link: ' + data.message);
                    }
                })
                .catch(error => {
                    document.body.removeChild(modal);
                    alert('Error sharing link. Please try again.');
                });
            });
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                }
            });
        });
    });
});
</script>

<?php
echo $OUTPUT->footer(); 