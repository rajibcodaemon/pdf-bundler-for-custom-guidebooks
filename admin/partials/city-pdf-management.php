<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Add New City PDF Form -->
    <div class="pdf-bundler-form-container">
        <h2>Add New City PDF</h2>
        <form id="add-city-pdf-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="handle_city_pdf_upload">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('pdf_bundler_nonce'); ?>">
            
            <div class="form-group">
                <label for="city">Select City:</label>
                <select id="city" name="city" class="searchable-dropdown">
                    <option value="">Select a city...</option>
                    <?php
                    global $wpdb;
                    // Table name
                    $table_name = $wpdb->prefix . 'us_cities';
                    // Query to select all cities
                    $query = "SELECT city FROM $table_name";
                    // Execute the query
                    $cities = $wpdb->get_results($query, ARRAY_A);
                    if (!empty($cities)) {
                        foreach ($cities as $city) {
                            echo sprintf(
                                '<option value="%s">%s</option>',
                                esc_attr(esc_html($city['city'])),
                                esc_html(esc_html($city['city']))
                            );
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="city_pdf">City PDF:</label>
                <input type="file" id="city_pdf" name="city_pdf" accept=".pdf" required>
            </div>

            <button type="submit" class="button button-primary">Upload City PDF</button>
        </form>
    </div>

    <!-- City PDFs List -->
    <div class="pdf-bundler-table-container">
        <h2>City PDFs</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>City</th>
                    <th>PDF</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $cities = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pdf_bundler_cities ORDER BY city ASC");
                
                foreach ($cities as $city) {
                    ?>
                    <tr>
                        <td><?php echo esc_html($city->city); ?></td>
                        <td><?php echo esc_html(basename($city->pdf_path)); ?></td>
                        <td><?php echo esc_html(ucfirst($city->status)); ?></td>
                        <td><?php echo esc_html($city->updated_at ?: $city->created_at); ?></td>
                        <td>
                            <div class="actions">
                                <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/pdf-bundler/city-pdfs/' . basename($city->pdf_path)); ?>" 
                                   class="button button-small" target="_blank">
                                   <span class="dashicons dashicons-visibility"></span> View
                                </a>
                                <button type="button" class="button button-small delete-city-pdf" 
                                        data-nonce="<?php echo wp_create_nonce('delete_city_pdf_nonce'); ?>"
                                        data-city-id="<?php echo esc_attr($city->id); ?>">
                                        <span class="dashicons dashicons-trash"></span> Delete
                                </button>
                            </div>
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
    $('#add-city-pdf-form').on('submit', function(e) {
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
                    alert('City PDF uploaded successfully!');
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

    $('.delete-city-pdf').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this city PDF?')) {
            return;
        }
        
        const button = $(this);
        const cityId = button.data('city-id');
        const nonce = button.data('nonce');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_city_pdf',
                city_id: cityId,
                nonce: nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || 'Failed to delete city PDF');
                }
            },
            error: function() {
                alert('Failed to delete city PDF');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script> 