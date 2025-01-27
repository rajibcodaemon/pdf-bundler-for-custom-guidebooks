<?php
// Get all merged PDFs from the database
global $wpdb;
$table_name = $wpdb->prefix . 'pdf_bundler_merge_queue';
$merged_pdfs = $wpdb->get_results("
    SELECT * FROM {$table_name} 
    ORDER BY created_at DESC
");

if (current_user_can('manage_options')) { // Only show to admins
    echo '<!-- Debug Info -->';
    foreach ($merged_pdfs as $pdf) {
        echo "<!-- \n";
        echo "PDF Path: " . $pdf->custom_pdf_path . "\n";
        echo "File exists: " . (file_exists($pdf->custom_pdf_path) ? 'Yes' : 'No') . "\n";
        $upload_dir = wp_upload_dir();
        echo "Upload Base Dir: " . $upload_dir['basedir'] . "\n";
        echo "Upload Base URL: " . $upload_dir['baseurl'] . "\n";
        echo "-->\n";
    }
}
?>

<div class="wrap pdf-bundler-container">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="pdf-bundler-management-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Agent Name</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Generated Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($merged_pdfs)) {
                    foreach ($merged_pdfs as $pdf) {
                        $customer = get_user_by('id', $pdf->user_id);
                        if (!$customer) continue;

                        $full_name = trim(get_user_meta($pdf->user_id, 'first_name', true) . ' ' . 
                                        get_user_meta($pdf->user_id, 'last_name', true));
                        $display_name = !empty($full_name) ? $full_name : $customer->display_name;
                        
                        // Update the PDF URL generation part
                        $pdf_view_url = home_url('view-pdf/' . $pdf->id);
                        
                        // Get flipbook URL from user meta
                        $flipbook_url = get_user_meta($pdf->user_id, '_flipbook_url', true);
                        ?>
                        <tr>
                            <td><?php echo esc_html($display_name); ?></td>
                            <td><?php echo esc_html($customer->user_email); ?></td>
                            <td><?php echo esc_html(get_user_meta($pdf->user_id, 'billing_city', true)); ?></td>
                            <td><?php echo esc_html(date('F j, Y g:i a', strtotime($pdf->created_at))); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($pdf->status); ?>">
                                    <?php echo esc_html(ucfirst($pdf->status)); ?>
                                </span>
                            </td>
                            <td class="pdf-actions">
                                <?php if ($pdf->status === 'completed' && $pdf_view_url) : ?>
                                    <a href="<?php echo esc_url($pdf_view_url); ?>" 
                                       class="button button-small view-pdf"
                                       target="_blank">
                                        <span class="dashicons dashicons-visibility"></span> View PDF
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($flipbook_url) : ?>
                                    <a href="<?php echo esc_url($flipbook_url); ?>" 
                                       class="button button-small add-to-flipbook"
                                       target="_blank">
                                        <span class="dashicons dashicons-book"></span> View Flipbook
                                    </a>
                                <?php else: ?>
                                    <a href="#" 
                                       class="button button-small add-to-flipbook"
                                       data-user-id="<?php echo esc_attr($pdf->user_id); ?>"
                                       data-pdf-path="<?php echo esc_attr($pdf->custom_pdf_path); ?>">
                                        <span class="dashicons dashicons-book"></span> Add to Flipbook
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6">No PDF records found.</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.pdf-actions {
    display: flex;
    gap: 5px;
}

.pdf-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.pdf-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-top: 3px;
}

.view-pdf { 
    background: #2271b1 !important; 
    color: white !important; 
    border-color: #2271b1 !important;
}

.add-to-flipbook { 
    background: #2ea2cc !important; 
    color: white !important; 
    border-color: #2ea2cc !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.add-to-flipbook').on('click', function(e) {
        if (!$(this).data('user-id')) return; // Skip if already a flipbook URL
        
        e.preventDefault();
        var button = $(this);
        var userId = button.data('user-id');
        var pdfPath = button.data('pdf-path');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'add_to_user_flipbook',
                user_id: userId,
                pdf_path: pdfPath,
                nonce: '<?php echo wp_create_nonce("pdf_bundler_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Replace button with flipbook link
                    button.replaceWith(
                        '<a href="' + response.data.flipbook_url + '" ' +
                        'class="button button-small add-to-flipbook" target="_blank">' +
                        '<span class="dashicons dashicons-book"></span> View Flipbook</a>'
                    );
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
});
</script> 