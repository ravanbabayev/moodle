<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check login
require_login();

// Get parameters
$id = required_param('id', PARAM_INT);
$userid = $USER->id;

// Set up the page
$title = get_string('editpaymentlink', 'local_lidio');
$url = new moodle_url('/local/lidio/edit_payment_link.php', array('id' => $id));
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

// Add TailwindCSS
$PAGE->requires->css(new moodle_url('/local/lidio/styles.css'));
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

// Get the payment link
$paymentlink = $DB->get_record('local_lidio_payment_links', array('id' => $id, 'merchantid' => $merchant->id));

if (!$paymentlink) {
    redirect(
        new moodle_url('/local/lidio/payment_links.php'),
        'Payment link not found',
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$errors = array();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $title_val = trim($_POST['title'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $currency = $_POST['currency'] ?? 'TRY';
    $product_name = trim($_POST['product_name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $contact_method = trim($_POST['contact_method'] ?? '');
    $require_phone = ($contact_method === 'phone') ? 1 : 0;
    $require_email = ($contact_method === 'email') ? 1 : 0;
    $merchant_contact_info = trim($_POST['merchant_contact_info'] ?? '');
    
    // Auto-detect sharing method based on merchant contact info
    $sharing_method = '';
    if (!empty($merchant_contact_info)) {
        if (filter_var($merchant_contact_info, FILTER_VALIDATE_EMAIL)) {
            $sharing_method = 'email';
        } elseif (preg_match('/^[+]?[0-9\s\-\(\)]+$/', $merchant_contact_info)) {
            $sharing_method = 'sms';
        }
    }
    $description = trim($_POST['description'] ?? '');
    $expiry_date = !empty($_POST['expiry_date']) ? strtotime($_POST['expiry_date']) : null;
    $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
    $success_url = trim($_POST['success_url'] ?? '');
    $cancel_url = trim($_POST['cancel_url'] ?? '');
    
    // Validation
    if (empty($title_val)) {
        $errors[] = 'Payment link title is required';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0';
    }
    if (!empty($max_uses) && $max_uses <= 0) {
        $errors[] = 'Maximum uses must be greater than 0';
    }
    if (empty($contact_method)) {
        $errors[] = 'Please select a contact method';
    }
    
    // Handle file upload
    $product_image_url = $paymentlink->product_image; // Keep existing if no new upload
    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = $CFG->dataroot . '/local_lidio/product_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $product_image_url = $CFG->wwwroot . '/local/lidio/product_images/' . $filename;
                
                // Delete old image if exists
                if (!empty($paymentlink->product_image)) {
                    $old_filename = basename($paymentlink->product_image);
                    $old_path = $upload_dir . $old_filename;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
            }
        } else {
            $errors[] = 'Invalid file type. Please upload JPG, JPEG or PNG files only.';
        }
    }
    
    // If no errors, update the payment link
    if (empty($errors)) {
        // Prepare record
        $record = new stdClass();
        $record->id = $id;
        $record->title = $title_val;
        $record->description = $description;
        $record->amount = $amount;
        $record->currency = $currency;
        $record->expiry_date = $expiry_date;
        $record->max_uses = $max_uses;
        $record->success_url = $success_url;
        $record->cancel_url = $cancel_url;
        $record->product_name = $product_name;
        $record->product_image = $product_image_url;
        $record->product_description = $product_description;
        $record->require_phone = $require_phone ? 1 : 0;
        $record->require_email = $require_email ? 1 : 0;
        $record->merchant_contact_info = $merchant_contact_info;
        $record->sharing_method = $sharing_method;
        $record->timemodified = time();
        
        // Update record
        $DB->update_record('local_lidio_payment_links', $record);
        
        redirect(new moodle_url('/local/lidio/payment_links.php'), 
                'Payment link updated successfully!', null, 
                \core\output\notification::NOTIFY_SUCCESS);
    }
} else {
    // Pre-populate form with existing data
    $_POST['title'] = $paymentlink->title;
    $_POST['description'] = $paymentlink->description;
    $_POST['amount'] = $paymentlink->amount;
    $_POST['currency'] = $paymentlink->currency;
    $_POST['product_name'] = $paymentlink->product_name;
    $_POST['product_description'] = $paymentlink->product_description;
    
    // Set contact method based on existing requirements
    if ($paymentlink->require_phone) {
        $_POST['contact_method'] = 'phone';
    } elseif ($paymentlink->require_email) {
        $_POST['contact_method'] = 'email';
    } else {
        $_POST['contact_method'] = '';
    }
    
    $_POST['merchant_contact_info'] = $paymentlink->merchant_contact_info ?? '';
    $_POST['expiry_date'] = $paymentlink->expiry_date ? date('Y-m-d', $paymentlink->expiry_date) : '';
    $_POST['max_uses'] = $paymentlink->max_uses;
    $_POST['success_url'] = $paymentlink->success_url;
    $_POST['cancel_url'] = $paymentlink->cancel_url;
}

echo $OUTPUT->header();
?>

<style>
.form-container {
    max-width: 800px;
    margin: 2rem auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.form-body {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
}

.form-section h3 {
    margin: 0 0 1rem 0;
    color: #374151;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.form-section h3 i {
    margin-right: 0.5rem;
    color: #667eea;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.form-label.required::after {
    content: " *";
    color: #ef4444;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s;
    background: white;
    box-sizing: border-box;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-file {
    width: 100%;
    padding: 0.75rem;
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    background: white;
    text-align: center;
    transition: all 0.2s;
    cursor: pointer;
}

.form-file:hover {
    border-color: #667eea;
    background: #f0f4ff;
}

.btn {
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    margin-right: 1rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.error-messages {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.error-messages ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.error-messages li {
    color: #dc2626;
    margin-bottom: 0.5rem;
}

.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.current-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-container {
        margin: 1rem;
    }
    
    .form-header, .form-body {
        padding: 1rem;
    }
}
</style>

<div class="form-container">
    <div class="form-header">
        <h1><i class="fas fa-edit"></i> Edit Payment Link</h1>
        <p>Update your payment link details</p>
    </div>
    
    <div class="form-body">
        <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            
            <!-- Basic Information -->
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="title" class="form-label required">Payment Link Title</label>
                    <input type="text" id="title" name="title" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                           placeholder="e.g., Premium Course Access" required>
                    <div class="help-text">Choose a clear, descriptive title for your payment link</div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-textarea" rows="3"
                              placeholder="Additional details about this payment link..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount" class="form-label required">Amount</label>
                        <input type="number" id="amount" name="amount" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" 
                               step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="currency" class="form-label">Currency</label>
                        <select id="currency" name="currency" class="form-select">
                            <option value="TRY" <?php echo ($_POST['currency'] ?? 'TRY') === 'TRY' ? 'selected' : ''; ?>>TRY - Turkish Lira</option>
                            <option value="USD" <?php echo ($_POST['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo ($_POST['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Product Information -->
            <div class="form-section">
                <h3><i class="fas fa-box"></i> Product Information</h3>
                
                <div class="form-group">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" id="product_name" name="product_name" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" 
                           placeholder="Name of your product or service">
                    <div class="help-text">Enter the name of the product or service you are selling</div>
                </div>
                
                <div class="form-group">
                    <label for="product_image" class="form-label">Product Image</label>
                    <?php if (!empty($paymentlink->product_image)): ?>
                    <div style="margin-bottom: 1rem;">
                        <p style="margin-bottom: 0.5rem; font-size: 0.875rem; color: #374151;">Current Image:</p>
                        <img src="<?php echo $paymentlink->product_image; ?>" alt="Current product image" class="current-image">
                    </div>
                    <?php endif; ?>
                    <input type="file" id="product_image" name="product_image" class="form-file" 
                           accept=".jpg,.jpeg,.png">
                    <div class="help-text">Upload a new image to replace the current one (JPG, JPEG, PNG - Max 5MB)</div>
                </div>
                
                <div class="form-group">
                    <label for="product_description" class="form-label">Product Description</label>
                    <textarea id="product_description" name="product_description" class="form-textarea" rows="4"
                              placeholder="Detailed description of your product or service..."><?php echo htmlspecialchars($_POST['product_description'] ?? ''); ?></textarea>
                    <div class="help-text">Provide a detailed description of your product or service</div>
                </div>
            </div>
            
            <!-- Customer Contact Requirements -->
            <div class="form-section">
                <h3><i class="fas fa-users"></i> Customer Contact Requirements</h3>
                
                <div class="form-group">
                    <label class="form-label">Customer Contact Method</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="contact-option" for="require_phone">
                            <input type="radio" id="require_phone" name="contact_method" value="phone" style="margin-right: 0.5rem;" <?php echo (($_POST['contact_method'] ?? '') === 'phone') ? 'checked' : ''; ?>>
                            <span>
                                <strong>Phone Number</strong><br>
                                <small style="color: #6b7280;">Collect customer phone</small>
                            </span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;" class="contact-option" for="require_email">
                            <input type="radio" id="require_email" name="contact_method" value="email" style="margin-right: 0.5rem;" <?php echo (($_POST['contact_method'] ?? '') === 'email') ? 'checked' : ''; ?>>
                            <span>
                                <strong>Email Address</strong><br>
                                <small style="color: #6b7280;">Collect customer email</small>
                            </span>
                        </label>
                    </div>
                    <div class="help-text" style="color: #dc2626; font-weight: 500;">Please select one contact method</div>
                </div>
                
                <!-- Merchant Contact Info for Link Sharing -->
                <div class="form-group">
                    <label for="merchant_contact_info" class="form-label">Your Contact Info for Sharing</label>
                    <input type="text" id="merchant_contact_info" name="merchant_contact_info" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['merchant_contact_info'] ?? ''); ?>" 
                           placeholder="Enter your phone number or email to share this link with customers">
                    <div class="help-text">Enter your phone number or email address. System will auto-detect the sharing method.</div>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <div class="form-section">
                <h3><i class="fas fa-cog"></i> Advanced Settings</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date" class="form-label">Expiry Date (Optional)</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="form-input" 
                               value="<?php echo $_POST['expiry_date'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_uses" class="form-label">Maximum Uses (Optional)</label>
                        <input type="number" id="max_uses" name="max_uses" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['max_uses'] ?? ''); ?>" 
                               min="1" placeholder="Unlimited">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="success_url" class="form-label">Success URL (Optional)</label>
                    <input type="url" id="success_url" name="success_url" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['success_url'] ?? ''); ?>" 
                           placeholder="https://yoursite.com/thank-you">
                    <div class="help-text">URL to redirect customers after successful payment</div>
                </div>
                
                <div class="form-group">
                    <label for="cancel_url" class="form-label">Cancel URL (Optional)</label>
                    <input type="url" id="cancel_url" name="cancel_url" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['cancel_url'] ?? ''); ?>" 
                           placeholder="https://yoursite.com/cancelled">
                    <div class="help-text">URL to redirect customers if they cancel the payment</div>
                </div>
            </div>
            
            <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Payment Link
                </button>
                <a href="<?php echo new moodle_url('/local/lidio/payment_links.php'); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Contact option styling
document.addEventListener('DOMContentLoaded', function() {
    const contactOptions = document.querySelectorAll('.contact-option');
    
    contactOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        
        // Initial styling
        updateContactOptionStyle(option, radio.checked);
        
        // Add change listener
        radio.addEventListener('change', function() {
            // Update all options
            contactOptions.forEach(opt => {
                const r = opt.querySelector('input[type="radio"]');
                updateContactOptionStyle(opt, r.checked);
            });
        });
    });
    
    function updateContactOptionStyle(option, isChecked) {
        if (isChecked) {
            option.style.borderColor = '#667eea';
            option.style.backgroundColor = '#f0f4ff';
        } else {
            option.style.borderColor = '#e5e7eb';
            option.style.backgroundColor = 'white';
        }
    }
});

// File upload preview
document.getElementById('product_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Create preview if it doesn't exist
            let preview = document.getElementById('image-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'image-preview';
                preview.style.marginTop = '1rem';
                document.getElementById('product_image').parentNode.appendChild(preview);
            }
            
            preview.innerHTML = `
                <p style="margin-bottom: 0.5rem; font-size: 0.875rem; color: #374151;">New Image Preview:</p>
                <img src="${e.target.result}" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Product Preview">
                <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280;">Preview: ${file.name}</p>
            `;
        };
        reader.readAsDataURL(file);
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const amount = parseFloat(document.getElementById('amount').value);
    const contactMethod = document.querySelector('input[name="contact_method"]:checked');
    
    if (!title) {
        e.preventDefault();
        alert('Please enter a payment link title');
        document.getElementById('title').focus();
        return;
    }
    
    if (!amount || amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid amount greater than 0');
        document.getElementById('amount').focus();
        return;
    }
    
    if (!contactMethod) {
        e.preventDefault();
        alert('Please select a contact method');
        return;
    }
});
</script>

<?php echo $OUTPUT->footer(); ?> 