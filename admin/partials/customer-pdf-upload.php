<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Customer PDF Upload Form -->
    <div class="pdf-bundler-form-container">
        <h2>Upload Agent Bio PDF</h2>
        <form id="customer-pdf-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="handle_customer_pdf_upload">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('pdf_bundler_nonce'); ?>">
            
            <!-- Customer Selection -->
            <div class="form-group">
                <label for="customer">Select Customer:</label>
                <select id="customer" name="customer_id" required>
                    <option value="">Select a customer...</option>
                    <?php
                    $roles = array('customer', 'subscriber', 'agent');
                    $customers = get_users(array(
                        //'role' => 'customer',
                        'role__in' => $roles, // Fetch users with any of these roles
                        'orderby' => 'display_name',
                        'order' => 'ASC'
                    ));

                    foreach ($customers as $customer) {
                        $full_name = trim(get_user_meta($customer->ID, 'first_name', true) . ' ' . 
                                        get_user_meta($customer->ID, 'last_name', true));
                        $display_name = !empty($full_name) ? $full_name : $customer->display_name;
                        echo sprintf(
                            '<option value="%d">%s</option>',
                            esc_attr($customer->ID),
                            esc_html($display_name)
                        );
                    }
                    ?>
                </select>
            </div>

            <!-- City Selection -->
            <div class="form-group">
                <label for="city">Agent City:</label>
                <select id="city" name="city" required>
                    <option value="">Select a city...</option>
                    <?php
                    global $wpdb;
                    // Get cities from your WooCommerce orders
                    $cities = $wpdb->get_col("
                        SELECT DISTINCT meta_value 
                        FROM {$wpdb->prefix}postmeta 
                        WHERE meta_key = '_billing_city' 
                        AND meta_value != ''
                        ORDER BY meta_value ASC
                    ");

                    foreach ($cities as $city) {
                        echo sprintf(
                            '<option value="%s">%s</option>',
                            esc_attr($city),
                            esc_html($city)
                        );
                    }
                    ?>
                </select>
            </div>

            <!-- PDF Upload -->
            <div class="form-group">
                <label for="customer_pdf">Agent Bio PDF:</label>
                <input type="file" id="customer_pdf" name="customer_pdf" accept=".pdf" required>
            </div>

            <button type="submit" class="button button-primary">Upload PDF</button>
        </form>
    </div>

    <!-- Customer PDFs List -->
    <div class="pdf-bundler-table-container">
        <h2>Agent Bio PDFs</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>City</th>
                    <th>Bio PDF</th>
                    <th>Status</th>
                    <th>Uploaded Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $customer_pdfs = $wpdb->get_results("
                    SELECT * FROM {$wpdb->prefix}pdf_bundler_merge_queue 
                    WHERE customer_bio_pdf IS NOT NULL 
                    ORDER BY created_at DESC
                ");
                
                foreach ($customer_pdfs as $pdf) {
                    $customer = get_user_by('id', $pdf->user_id);
                    if (!$customer) continue;

                    $full_name = trim(get_user_meta($pdf->user_id, 'first_name', true) . ' ' . 
                                    get_user_meta($pdf->user_id, 'last_name', true));
                    $display_name = !empty($full_name) ? $full_name : $customer->display_name;
                    ?>
                    <tr>
                        <td><?php echo esc_html($display_name); ?></td>
                        <td><?php echo esc_html($pdf->city); ?></td>
                        <td><?php echo esc_html(basename($pdf->customer_bio_pdf)); ?></td>
                        <td><?php echo esc_html(ucfirst($pdf->status)); ?></td>
                        <td><?php echo esc_html(date('F j, Y g:i a', strtotime($pdf->created_at))); ?></td>
                        <td>
                            <a href="<?php echo esc_url(home_url('view-pdf/' . $pdf->id . '?type=bio')); ?>" 
                               class="button button-small" target="_blank">View PDF</a>
                            <button class="button button-small delete-customer-pdf" 
                                    data-pdf-id="<?php echo esc_attr($pdf->id); ?>">Delete</button>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('#customer-pdf-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var submitButton = $(this).find('button[type="submit"]');
        var originalText = submitButton.text();
        
        submitButton.prop('disabled', true).text('Uploading...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('PDF uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Upload failed. Please try again.');
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalText);
            }
        });
    });

    // Handle delete button
    $('.delete-customer-pdf').on('click', function() {
        if (confirm('Are you sure you want to delete this PDF?')) {
            var button = $(this);
            var pdfId = button.data('pdf-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_customer_pdf',
                    pdf_id: pdfId,
                    nonce: '<?php echo wp_create_nonce("pdf_bundler_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').remove();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
});
</script> 