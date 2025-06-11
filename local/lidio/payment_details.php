// Process payment form submission
if ($data = data_submitted() && confirm_sesskey()) {
    try {
        if ($payment_method === 'credit_card') {
            // Simülasyon: Kredi kartı işlemi
            $transaction->status = 'completed';
            $transaction->gateway_response = json_encode(['card_last4' => substr($data->card_number, -4), 'processor' => 'Demo', 'success' => true]);
            $transaction->timemodified = time();
            
            $DB->update_record('local_lidio_transactions', $transaction);
            
            if (!empty($paymentlink->success_url)) {
                redirect($paymentlink->success_url);
            } else {
                redirect(new moodle_url('/local/lidio/payment_success.php', ['reference' => $transaction->reference]));
            }
        } else if ($payment_method === 'bank_transfer') {
            // Banka havalesi için
            $transaction->status = 'pending';
            $transaction->gateway_response = json_encode(['bank_name' => $data->bank_name, 'time' => time()]);
            $transaction->timemodified = time();
            
            $DB->update_record('local_lidio_transactions', $transaction);
            
            redirect(new moodle_url('/local/lidio/payment_bank_details.php', ['id' => $transaction->id]));
        }
    } catch (Exception $e) {
        echo '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 15px; border-radius: 5px;">';
        echo '<h3>Hata Oluştu:</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
    }
} 