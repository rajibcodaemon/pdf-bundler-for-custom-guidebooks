<?php
global $wpdb;
$debug_info = array(
    'Tables' => array(
        'Cities' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}pdf_bundler_cities'") === $wpdb->prefix . 'pdf_bundler_cities',
        'Queue' => $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}pdf_bundler_merge_queue'") === $wpdb->prefix . 'pdf_bundler_merge_queue'
    ),
    'Upload Dir' => wp_upload_dir(),
    'Permissions' => array(
        'Base' => file_exists(WP_CONTENT_DIR . '/uploads') ? substr(sprintf('%o', fileperms(WP_CONTENT_DIR . '/uploads')), -4) : 'N/A',
        'Plugin Dir' => file_exists(WP_CONTENT_DIR . '/uploads/pdf-bundler') ? substr(sprintf('%o', fileperms(WP_CONTENT_DIR . '/uploads/pdf-bundler')), -4) : 'N/A',
        'City PDFs' => file_exists(WP_CONTENT_DIR . '/uploads/pdf-bundler/city-pdfs') ? substr(sprintf('%o', fileperms(WP_CONTENT_DIR . '/uploads/pdf-bundler/city-pdfs')), -4) : 'N/A'
    )
);

if (current_user_can('manage_options')) {
    echo '<div class="debug-info" style="display:none;"><pre>' . print_r($debug_info, true) . '</pre></div>';
}
?>

<div class="wrap">
    <h1><span class="dashicons dashicons-location"></span> City PDF Management</h1>
    
    <div class="pdf-bundler-container">
        <!-- Current City PDFs -->
        <div class="current-pdfs">
            <div class="header-actions">
                <h2>City PDFs</h2>
                <button class="button button-primary add-new-pdf">
                    <span class="dashicons dashicons-plus-alt2"></span> Add New PDF
                </button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th>City</th>
                        <th>PDF File</th>
                        <th width="150">Upload Date</th>
                        <th width="100">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $city_pdfs = $wpdb->get_results("
                        SELECT c.*, 
                            (SELECT COUNT(*) FROM {$wpdb->prefix}pdf_bundler_merge_queue q 
                             WHERE q.city = c.city AND q.needs_update = 1) as pending_updates
                        FROM {$wpdb->prefix}pdf_bundler_cities c
                        ORDER BY c.city ASC
                    ");

                    if ($city_pdfs): 
                        $counter = 1;
                        foreach ($city_pdfs as $pdf): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo esc_html($pdf->city); ?></strong>
                                    <?php if ($pdf->pending_updates > 0): ?>
                                        <span class="pending-updates">
                                            <?php echo $pdf->pending_updates; ?> pending updates
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(str_replace(ABSPATH, site_url('/'), $pdf->pdf_path)); ?>" 
                                       target="_blank" 
                                       class="pdf-link">
                                        <span class="dashicons dashicons-pdf"></span>
                                        <?php echo basename($pdf->pdf_path); ?>
                                    </a>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($pdf->created_at)); ?></td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($pdf->status); ?>">
                                        <?php echo ucfirst($pdf->status); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="button button-small update-pdf" 
                                            data-city="<?php echo esc_attr($pdf->city); ?>"
                                            title="Update PDF">
                                        <span class="dashicons dashicons-update"></span>
                                    </button>
                                    <button class="button button-small delete-pdf" 
                                            data-id="<?php echo esc_attr($pdf->id); ?>"
                                            title="Delete PDF">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="6" class="no-records">
                                <span class="dashicons dashicons-media-document"></span>
                                <p>No city PDFs uploaded yet</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Upload Form (Hidden by default) -->
        <div id="upload-form" class="upload-section" style="display: none;">
            <div class="upload-header">
                <h2>Upload City PDF</h2>
                <button class="close-upload">×</button>
            </div>
            <form id="city-pdf-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="handle_city_pdf_upload">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('pdf_bundler_nonce'); ?>">
                
                <div class="form-group">
                    <label for="city">Select City:</label>
                    <select id="city" name="city" required></select>
                        
                    <p class="description">Select the city for which you want to upload a PDF</p>
                </div>

                <div class="form-group">
                    <label>City PDF:</label>
                    <div class="upload-area" id="drop-area">
                        <input type="file" id="city_pdf" name="city_pdf" accept=".pdf" required>
                        <label for="city_pdf">
                            <div class="upload-icon">
                                <span class="dashicons dashicons-upload"></span>
                            </div>
                            <div class="upload-text">
                                <strong>Choose a PDF file</strong>
                                <span>or drag it here</span>
                            </div>
                            <div class="selected-file"></div>
                        </label>
                    </div>
                </div>

                <div class="message" style="display: none;"></div>
                <div class="form-actions">
                    <button type="button" class="button cancel-upload">Cancel</button>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-upload"></span> Upload PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.wrap h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.wrap h1 .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
}

.pdf-bundler-container {
    max-width: 1200px;
    margin: 20px 0;
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.current-pdfs, .upload-section {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.city-pdf-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.city-pdf-card {
    background: #f8f9fa;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    transition: transform 0.2s;
}

.city-pdf-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.city-name {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pdf-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.view-pdf {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #2271b1;
    text-decoration: none;
    padding: 5px 0;
}

.upload-date {
    font-size: 12px;
    color: #666;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
}

.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.description {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

.upload-area {
    border: 2px dashed #2271b1;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    margin-top: 10px;
    background: #f0f6fc;
    transition: all 0.3s ease;
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
    margin: 0;
}

.upload-icon {
    margin-bottom: 15px;
}

.upload-icon .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #2271b1;
}

.upload-text strong {
    display: block;
    margin-bottom: 5px;
    color: #2271b1;
}

.upload-text span {
    color: #666;
    font-size: 13px;
}

.selected-file {
    margin-top: 10px;
    font-size: 13px;
    color: #135e96;
}

.message {
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 4px;
    font-size: 14px;
}

.message.success {
    background: #edfaef;
    color: #0a5132;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #fbeaea;
    color: #8a1f11;
    border: 1px solid #f5c6cb;
}

.button-primary {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.no-pdfs {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
}

.no-pdfs .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
    margin-bottom: 10px;
}

.no-pdfs p {
    margin: 0;
    color: #666;
}

@media (max-width: 1200px) {
    .pdf-bundler-container {
        grid-template-columns: 1fr;
    }
}

.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.upload-section {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    max-width: 90%;
    background: white;
    z-index: 1000;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.upload-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.close-upload {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.pdf-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #2271b1;
    text-decoration: none;
}

.pending-updates {
    display: inline-block;
    background: #fff7e5;
    color: #996300;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
}

/* Add overlay */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: none;
}
</style>

<div class="overlay"></div>

<script>
jQuery(document).ready(function($) {
    // Show upload form
    $('.add-new-pdf').on('click', function() {
        $('.overlay').show();
        $('#upload-form').show();
    });

    // Hide upload form
    $('.close-upload, .cancel-upload, .overlay').on('click', function() {
        $('.overlay').hide();
        $('#upload-form').hide();
    });

    // File input change handler
    $('#city_pdf').on('change', function() {
        const fileName = this.files[0]?.name || '';
        if (fileName) {
            $('.selected-file').text('Selected: ' + fileName);
        } else {
            $('.selected-file').text('');
        }
    });

    // Drag and drop functionality
    const dropArea = $('#drop-area');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.on(eventName, preventDefaults);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.on(eventName, highlight);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.on(eventName, unhighlight);
    });

    function highlight(e) {
        dropArea.addClass('highlight');
    }

    function unhighlight(e) {
        dropArea.removeClass('highlight');
    }

    dropArea.on('drop', handleDrop);

    function handleDrop(e) {
        const dt = e.originalEvent.dataTransfer;
        const files = dt.files;
        $('#city_pdf')[0].files = files;
        const fileName = files[0]?.name || '';
        if (fileName) {
            $('.selected-file').text('Selected: ' + fileName);
        }
    }

    // Form submission
    $('#city-pdf-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = $(this).find('button[type="submit"]');
        const originalText = submitButton.text();
        const messageDiv = $('.message');
        
        submitButton.prop('disabled', true).text('Uploading...');
        messageDiv.hide();

        $.ajax({
            url: pdfBundlerAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    messageDiv
                        .removeClass('error')
                        .addClass('success')
                        .html('City PDF uploaded successfully! Refreshing...')
                        .show();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    messageDiv
                        .removeClass('success')
                        .addClass('error')
                        .html(response.data)
                        .show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', error);
                messageDiv
                    .removeClass('success')
                    .addClass('error')
                    .html('Upload failed. Please try again.')
                    .show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script> 