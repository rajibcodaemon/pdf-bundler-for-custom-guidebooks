<div class="wrap">
    <h1>Upload Agent Bio PDF</h1>
    
    <div class="pdf-bundler-container">
        <!-- Customer Selection -->
        <div class="customer-select-section">
            <label for="customer-select">Select Agent:</label>
            <select id="customer-select" name="customer_id">
                <option value="">Choose a customer...</option>
                <?php
                $roles = array('customer', 'subscriber', 'agent');
                $customers = get_users(array(
                    //'role' => 'customer',
                    'role__in' => $roles, // Fetch users with any of these roles
                    'orderby' => 'display_name',
                    'order' => 'ASC'
                ));

                foreach ($customers as $customer) {
                    $first_name = get_user_meta($customer->ID, 'first_name', true);
                    $last_name = get_user_meta($customer->ID, 'last_name', true);
                    $display_name = $first_name && $last_name 
                        ? "$first_name $last_name" 
                        : $customer->display_name;
                    
                    printf(
                        '<option value="%d">%s</option>',
                        esc_attr($customer->ID),
                        esc_html($display_name)
                    );
                }
                ?>
            </select>
        </div>

        <!-- Loading Spinner -->
        <div class="customer-loading" style="display: none;">
            <span class="spinner is-active"></span>
        </div>

        <!-- Customer Details Card -->
        <div class="customer-details-card" style="display: none;">
            <div class="customer-header">
                <img id="customer-avatar" src="" alt="Customer Avatar">
                <h2 id="customer-name"></h2>
            </div>
            <div class="customer-details">
                <div class="detail-item">
                    <span class="dashicons dashicons-email"></span>
                    <div class="detail-content">
                        <label>Email</label>
                        <span id="customer-email"></span>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="dashicons dashicons-phone"></span>
                    <div class="detail-content">
                        <label>Phone</label>
                        <span id="customer-phone"></span>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="dashicons dashicons-location"></span>
                    <div class="detail-content">
                        <label>Address</label>
                        <span id="customer-address"></span>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="dashicons dashicons-admin-site"></span>
                    <div class="detail-content">
                        <label>Location</label>
                        <span id="customer-location"></span>
                    </div>
                </div>
            </div>

            <!-- PDF Upload Section -->
            <div class="pdf-upload-section">
                <h3>Upload Agent Bio PDF</h3>
                <form id="bio-pdf-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="handle_customer_pdf_upload">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('pdf_bundler_nonce'); ?>">
                    <input type="hidden" id="selected-customer-id" name="customer_id" value="">
                    
                    <div class="upload-area">
                        <input type="file" id="customer_pdf" name="customer_pdf" accept=".pdf" required>
                        <label for="customer_pdf">
                            <span class="dashicons dashicons-upload"></span>
                            <span>Choose PDF or drag it here</span>
                        </label>
                    </div>

                    <div class="upload-message" style="display: none;"></div>
                    <button type="submit" class="button button-primary">Upload Bio PDF</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.pdf-bundler-container {
    max-width: 800px;
    margin: 20px auto;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.customer-select-section {
    margin-bottom: 30px;
}

.customer-select-section label {
    display: block;
    margin-bottom: 10px;
    font-size: 16px;
    font-weight: 500;
}

#customer-select {
    width: 100%;
    max-width: 400px;
    padding: 10px;
    font-size: 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
}

.customer-details-card {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 30px;
}

.customer-header {
    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    color: white;
}

#customer-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid white;
}

#customer-name {
    margin: 0;
    font-size: 24px;
    color: white;
}

.customer-details {
    padding: 30px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.detail-item .dashicons {
    color: #2271b1;
    font-size: 20px;
    width: 20px;
}

.detail-content label {
    display: block;
    color: #666;
    font-size: 12px;
    margin-bottom: 5px;
}

.detail-content span {
    color: #1d2327;
    font-size: 14px;
}

.pdf-upload-section {
    padding: 30px;
    border-top: 1px solid #e2e8f0;
}

.pdf-upload-section h3 {
    margin: 0 0 20px;
    color: #1d2327;
}

.upload-area {
    border: 2px dashed #2271b1;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    margin: 20px 0;
    transition: all 0.3s ease;
    background: #f0f6fc;
}

.upload-area:hover {
    background: #f6f7f7;
    border-color: #135e96;
}

.upload-area input[type="file"] {
    display: none;
}

.upload-area label {
    cursor: pointer;
    display: block;
}

.upload-area .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    margin: 0 auto 15px;
    display: block;
    color: #2271b1;
}

.upload-message {
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 6px;
    font-size: 14px;
}

.upload-message.success {
    background: #edfaef;
    color: #0a5132;
    border: 1px solid #c3e6cb;
}

.upload-message.error {
    background: #fbeaea;
    color: #8a1f11;
    border: 1px solid #f5c6cb;
}

.customer-loading {
    text-align: center;
    padding: 20px;
}

.customer-loading .spinner {
    float: none;
    margin: 0 auto;
}
</style> 