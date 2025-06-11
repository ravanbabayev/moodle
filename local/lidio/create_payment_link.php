<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/lidio/lib.php');

// Check login
require_login();

// Get current user
$userid = $USER->id;

// Set up the page
$title = get_string('createpaymentlink', 'local_lidio');
$url = new moodle_url('/local/lidio/create_payment_link.php');
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

// Process form submission
$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $title = trim($_POST['title'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $currency = $_POST['currency'] ?? 'TRY';
    $product_name = trim($_POST['product_name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $customer_contact_requirement = $_POST['customer_contact_requirement'] ?? 'phone_or_email';
    $description = trim($_POST['description'] ?? '');
    $expiry_date = !empty($_POST['expiry_date']) ? strtotime($_POST['expiry_date']) : null;
    $max_uses = !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null;
    $success_url = trim($_POST['success_url'] ?? '');
    $cancel_url = trim($_POST['cancel_url'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Payment link title is required';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than 0';
    }
    if (!empty($max_uses) && $max_uses <= 0) {
        $errors[] = 'Maximum uses must be greater than 0';
    }
    
    // Handle file upload
    $product_image_url = '';
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
            }
        } else {
            $errors[] = 'Invalid file type. Please upload JPG, JPEG or PNG files only.';
        }
    }
    
    // If no errors, create the payment link
    if (empty($errors)) {
        // Generate unique link code
        $link_code = md5(uniqid(rand(), true));
        
        // Prepare record
        $record = new stdClass();
        $record->merchantid = $merchant->id;
        $record->title = $title;
        $record->description = $description;
        $record->amount = $amount;
        $record->currency = $currency;
        $record->link_code = $link_code;
        $record->status = 'active';
        $record->expiry_date = $expiry_date;
        $record->max_uses = $max_uses;
        $record->current_uses = 0;
        $record->success_url = $success_url;
        $record->cancel_url = $cancel_url;
        $record->product_name = $product_name;
        $record->product_image = $product_image_url;
        $record->product_description = $product_description;
        $record->customer_contact_requirement = $customer_contact_requirement;
        $record->timecreated = time();
        $record->timemodified = time();
        
        // Insert record
        $DB->insert_record('local_lidio_payment_links', $record);
        
        redirect(new moodle_url('/local/lidio/payment_links.php'), 
                'Payment link created successfully!', null, 
                \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Display the page
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
        <h1><i class="fas fa-plus-circle"></i> Create Payment Link</h1>
        <p>Create a new payment link for your customers</p>
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
                    <input type="file" id="product_image" name="product_image" class="form-file" 
                           accept=".jpg,.jpeg,.png">
                    <div class="help-text">Upload an image of your product (JPG, JPEG, PNG - Max 5MB)</div>
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
                    <label for="customer_contact_requirement" class="form-label">Contact Information Required</label>
                    <select id="customer_contact_requirement" name="customer_contact_requirement" class="form-select">
                        <option value="phone_or_email" <?php echo ($_POST['customer_contact_requirement'] ?? 'phone_or_email') === 'phone_or_email' ? 'selected' : ''; ?>>Phone or Email (either one required)</option>
                        <option value="phone_required" <?php echo ($_POST['customer_contact_requirement'] ?? '') === 'phone_required' ? 'selected' : ''; ?>>Phone Number Required</option>
                        <option value="email_required" <?php echo ($_POST['customer_contact_requirement'] ?? '') === 'email_required' ? 'selected' : ''; ?>>Email Address Required</option>
                        <option value="both_required" <?php echo ($_POST['customer_contact_requirement'] ?? '') === 'both_required' ? 'selected' : ''; ?>>Both Phone and Email Required</option>
                    </select>
                    <div class="help-text">Choose what contact information is required from customers</div>
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
                    <i class="fas fa-save"></i> Create Payment Link
                </button>
                <a href="<?php echo new moodle_url('/local/lidio/payment_links.php'); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
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
                e.target.parentNode.appendChild(preview);
            }
            
            preview.innerHTML = `
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
});
</script>

<?php
echo $OUTPUT->footer();
?> 