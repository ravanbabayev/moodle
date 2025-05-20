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
 * Language strings for the Lidio payment system plugin.
 *
 * @package    local_lidio
 * @copyright  2023 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lidio Payment System';
$string['plugindisabled'] = 'The Lidio payment system plugin is currently disabled.';

// Capabilities
$string['lidio:manageplugin'] = 'Manage Lidio plugin settings';
$string['lidio:bemerchant'] = 'Apply to be a Lidio merchant';
$string['lidio:managemerchants'] = 'Manage Lidio merchants';
$string['lidio:viewmerchants'] = 'View Lidio merchants';

// Settings
$string['settings'] = 'Lidio settings';
$string['enabled'] = 'Enable Lidio';
$string['enabled_desc'] = 'Enable the Lidio payment system';

// Merchant application
$string['merchantapplication'] = 'Merchant Application';
$string['merchantapplication_desc'] = 'Apply to become a Lidio merchant';
$string['merchantapplicationdescription'] = 'Complete the form below to apply as a Lidio merchant. Once approved, you can start accepting payments through the Lidio payment system.';
$string['merchantstatus'] = 'Merchant Status';
$string['merchantstatus_pending'] = 'Your merchant application is pending review.';
$string['merchantstatus_approved'] = 'Your merchant application has been approved.';
$string['merchantstatus_rejected'] = 'Your merchant application has been rejected.';
$string['applyasmerchant'] = 'Apply as Merchant';
$string['notamerchant'] = 'You are not registered as a merchant. Please apply first.';

// KYC Verification
$string['kycverification'] = 'KYC Verification';
$string['kycverification_desc'] = 'Complete KYC verification to become a merchant';
$string['kycverificationintro'] = 'To activate your merchant account, we need to verify your identity. Please upload the required documents below.';
$string['kycstatus'] = 'KYC Status';
$string['kycstatus_pending'] = 'Your KYC verification is pending.';
$string['kycstatus_approved'] = 'Your KYC verification has been approved.';
$string['kycstatus_rejected'] = 'Your KYC verification has been rejected.';
$string['completekycverification'] = 'Complete KYC Verification';
$string['kycwarning'] = 'Please ensure all uploaded documents are clear and legible. Blurry or incomplete documents will be rejected. Documents must be less than 5MB in size.';
$string['kycuploaddocuments'] = 'Upload Verification Documents';
$string['acceptedformats'] = 'Accepted Formats';
$string['maxfilesize'] = 'Maximum File Size';
$string['uploaded'] = 'Uploaded';
$string['upload'] = 'Upload';
$string['delete'] = 'Delete';
$string['submit'] = 'Submit Documents';
$string['documentdeleted'] = 'Document has been deleted successfully.';

// KYC Document Types
$string['passport'] = 'Passport';
$string['passport_desc'] = 'Upload a clear scan or photo of your passport';
$string['id_card'] = 'ID Card';
$string['id_card_desc'] = 'Upload a clear scan or photo of your ID card (front and back)';
$string['driving_license'] = 'Driving License';
$string['driving_license_desc'] = 'Upload a clear scan or photo of your driving license';
$string['address_proof'] = 'Proof of Address';
$string['address_proof_desc'] = 'Upload a utility bill, bank statement or other official document showing your address (issued within the last 3 months)';
$string['company_registration'] = 'Company Registration Certificate';
$string['company_registration_desc'] = 'Upload your company registration certificate or business license (optional)';

// Additional KYC strings
$string['optional'] = 'Optional';
$string['processing'] = 'Processing...';
$string['documents_under_review'] = 'Your documents are under review. We will notify you once they are verified.';
$string['confirmdeletedocument'] = 'Are you sure you want to delete this document?';
$string['filetoolarge'] = 'The file size exceeds the maximum allowed (5MB).';
$string['invalidfiletype'] = 'Invalid file type. Please upload JPG, JPEG, PNG, or PDF files only.';
$string['merchantstatusnotapproved'] = 'Your merchant status is not yet approved.';

// Form fields
$string['fullname'] = 'Full Name';
$string['fullname_help'] = 'Enter your full legal name';
$string['address'] = 'Address';
$string['address_help'] = 'Enter your full address';
$string['phone'] = 'Phone Number';
$string['phone_help'] = 'Enter your contact phone number';
$string['idnumber'] = 'ID Number';
$string['idnumber_help'] = 'Enter your ID/Passport number';
$string['uploadid'] = 'Upload ID Document';
$string['uploadid_help'] = 'Upload a scan or photo of your ID/Passport';
$string['uploadaddressproof'] = 'Upload Address Proof';
$string['uploadaddressproof_help'] = 'Upload a scan or photo of a document showing your address';
$string['submitmerchantrequirement'] = 'Submit for Verification';
$string['invalidphone'] = 'Invalid phone number format. Please enter a valid phone number.';
$string['termsandconditions'] = 'I agree to the terms and conditions of the Lidio payment system';

// Admin interface
$string['merchantmanagement'] = 'Merchant Management';
$string['merchantdetails'] = 'Merchant Details';
$string['nomerchants'] = 'No merchants found.';
$string['pending'] = 'Pending';
$string['approved'] = 'Approved';
$string['rejected'] = 'Rejected';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['kycapprove'] = 'Approve KYC';
$string['kycreject'] = 'Reject KYC';
$string['view'] = 'View';
$string['back'] = 'Back';
$string['actions'] = 'Actions';
$string['confirmapprovemerchant'] = 'Are you sure you want to approve the merchant application for {$a}?';
$string['confirmrejectmerchant'] = 'Are you sure you want to reject the merchant application for {$a}?';
$string['confirmkycapprove'] = 'Are you sure you want to approve the KYC verification for {$a}?';
$string['confirmkycreject'] = 'Are you sure you want to reject the KYC verification for {$a}?';

// Document management
$string['documents'] = 'Documents';
$string['documenttype'] = 'Document Type';
$string['id_document'] = 'ID Document';
$string['address_document'] = 'Address Proof';
$string['filename'] = 'Filename';
$string['status'] = 'Status';
$string['download'] = 'Download';
$string['field'] = 'Field';
$string['value'] = 'Value';
$string['userprofile'] = 'User Profile';
$string['registrationdate'] = 'Registration Date';
$string['applicationdate'] = 'Application Date';
$string['email'] = 'Email';

// Messages
$string['applicationsubmitted'] = 'Your merchant application has been submitted successfully.';
$string['kycsubmitted'] = 'Your KYC verification documents have been submitted successfully.';
$string['requiredfield'] = 'This field is required.';
$string['fileuploaderror'] = 'Error uploading file.';
$string['errorprocessingform'] = 'There was an error processing your form submission. Please try again.';
$string['notauthorized'] = 'You are not authorized to perform this action.';

// Navigation
$string['merchantdashboard'] = 'Merchant Dashboard';
$string['merchantsettings'] = 'Merchant Settings';
$string['navigation'] = 'Navigation';
$string['dashboard'] = 'Dashboard';
$string['transactions'] = 'Transactions';
$string['help'] = 'Help';

// Dashboard
$string['transactionhistory'] = 'Transaction History';
$string['norecords'] = 'No records found.';
$string['viewdetails'] = 'View Details';
$string['merchantaccountstatus'] = 'Account Status';
$string['personalinformation'] = 'Personal Information';
$string['accountinformation'] = 'Account Information';
$string['lastlogin'] = 'Last Login';
$string['totaltransactions'] = 'Total Transactions';
$string['totalearnings'] = 'Total Earnings';
$string['pendingpayments'] = 'Pending Payments';
$string['amount'] = 'Amount';
$string['date'] = 'Date';
$string['id'] = 'ID';
$string['welcome'] = 'Welcome';
$string['overview'] = 'Overview';
$string['statistics'] = 'Statistics';
$string['activity'] = 'Recent Activity';
$string['balance'] = 'Balance';
$string['withdraw'] = 'Withdraw';
$string['viewall'] = 'View All';

// Merchant application form
$string['merchantapplicationpending'] = 'Your Merchant Application is Pending';
$string['merchantapplicationpendingmessage'] = 'Your merchant application is currently under review. We will notify you once it is approved.';
$string['updateapplication'] = 'Update Application';
$string['generalinformation'] = 'General Information';
$string['companytype'] = 'Company Type';
$string['individual'] = 'Individual';
$string['company'] = 'Company';
$string['companyname'] = 'Company/Store Name';
$string['phonenumber'] = 'Phone Number';
$string['businessinformation'] = 'Business Information';
$string['website'] = 'Website (optional)';
$string['socialmedialinks'] = 'Social Media Links (optional)';
$string['businessarea'] = 'Business Area / Products';
$string['monthlysalesvolume'] = 'Monthly Sales Volume';
$string['paymentmethods'] = 'Preferred Payment Methods';
$string['creditcard'] = 'Credit Card';
$string['banktransfer'] = 'Bank Transfer';
$string['paymentlink'] = 'Payment Link';
$string['mobilepayment'] = 'Mobile Payment';
$string['otherpayment'] = 'Other';
$string['bankinformation'] = 'Bank Information';
$string['iban'] = 'IBAN';
$string['accountholder'] = 'Account Holder Name';
$string['bankname'] = 'Bank Name';
$string['agreements'] = 'Agreements';
$string['kvkkapproval'] = 'KVKK Approval';
$string['kvkkapprovaltext'] = 'I consent to the processing of my personal data in accordance with KVKK.';
$string['termsapproval'] = 'Terms and Conditions';
$string['termsapprovaltext'] = 'I agree to the terms and conditions of the Lidio merchant service.';
$string['merchantcreated'] = 'Merchant application has been submitted successfully!';
$string['merchantupdated'] = 'Merchant application has been updated successfully!';
$string['becomemerchantintro'] = 'Thank you for your interest in becoming a merchant! Please fill in the form below.';

// Validation errors
$string['invalidiban'] = 'Invalid IBAN format.';
$string['kvkkapprovalrequired'] = 'You must agree to the KVKK terms.';
$string['termsapprovalrequired'] = 'You must agree to the terms and conditions.';

// Merchant dashboard
$string['merchantinfo'] = 'Merchant Information';
$string['dashboardstats'] = 'Dashboard Statistics';
$string['nodatayet'] = 'No data available yet.';

// KYC
$string['completekycverification'] = 'Please complete your KYC verification';
$string['kycstatus_pending'] = 'Your KYC verification is pending.';
$string['invalidphone'] = 'Invalid phone number format.';

// Merchant status
$string['notamerchant'] = 'You are not registered as a merchant.';

// Transaction page strings
$string['transactionsintro'] = 'View and manage all your payment transactions.';
$string['filtertransactions'] = 'Filter Transactions';
$string['transactionid'] = 'Transaction ID';
$string['paymentmethod'] = 'Payment Method';
$string['completed'] = 'Completed';
$string['failed'] = 'Failed';
$string['refunded'] = 'Refunded';
$string['details'] = 'Details';
$string['refund'] = 'Refund';
$string['notransactions'] = 'No transactions found';
$string['notransactionsdesc'] = 'You don\'t have any transactions yet. They will appear here once you start receiving payments.';
$string['refresh'] = 'Refresh';
$string['datefrom'] = 'Date From';
$string['dateto'] = 'Date To';
$string['allstatuses'] = 'All Statuses';
$string['filter'] = 'Filter';
$string['showing'] = 'Showing';
$string['to'] = 'to';
$string['of'] = 'of';
$string['results'] = 'results';
$string['previous'] = 'Previous';
$string['next'] = 'Next';
$string['merchantnotapproved'] = 'Your merchant account is not yet approved.';

// Admin interface
$string['merchantmanagementdesc'] = 'Manage Lidio payment system merchants';
$string['merchant'] = 'Merchant';
$string['contact'] = 'Contact Information';
$string['uploaddate'] = 'Upload Date';
$string['nomerchantsdesc'] = 'No merchant applications have been submitted yet.';
$string['merchantdetailsdesc'] = 'View detailed information about this merchant';
$string['individual'] = 'Individual';
$string['company'] = 'Company'; 