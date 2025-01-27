<div class="wrap">
    <h1>Agent Bio PDFs</h1>
    
    <div class="pdf-bundler-container">
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="<?php echo admin_url('admin.php?page=pdf-bundler-upload-bio'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span> Upload New Bio PDF
                </a>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th width="50">Avatar</th>
                    <th>Agent Name</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Bio PDF</th>
                    <th>Merged PDF</th>
                    <th width="100">Status</th>
                    <th width="150">Upload Date</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $results = $wpdb->get_results("
                    SELECT q.*, u.display_name, u.user_email
                    FROM {$wpdb->prefix}pdf_bundler_merge_queue q
                    LEFT JOIN {$wpdb->users} u ON q.user_id = u.ID
                    WHERE q.customer_bio_pdf IS NOT NULL
                    ORDER BY q.created_at DESC
                ");

                if ($results): 
                    $counter = 1;
                    foreach ($results as $row): 
                        $billing_city = get_user_meta($row->user_id, 'billing_city', true);
                        $merged_pdf_url = !empty($row->merged_pdf_path) ? 
                            wp_upload_dir()['baseurl'] . '/pdf-bundler/merged-pdfs/' . basename($row->merged_pdf_path) : '';
                        $flipbook_url = !empty($row->flipbook_url) ? $row->flipbook_url : '';
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <?php echo get_avatar($row->user_id, 40, '', '', array('class' => 'customer-avatar')); ?>
                            </td>
                            <td><?php echo esc_html($row->display_name); ?></td>
                            <td><?php echo esc_html($row->user_email); ?></td>
                            <td><?php echo esc_html($billing_city); ?></td>
                            <td>
                                <?php if (!empty($row->customer_bio_pdf)): ?>
                                    <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/pdf-bundler/customer-pdfs/' . basename($row->customer_bio_pdf)); ?>" 
                                       class="button button-small" target="_blank">
                                       <span class="dashicons dashicons-visibility"></span> View Bio
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($merged_pdf_url)): ?>
                                    <div class="pdf-links">
                                        <a href="<?php echo esc_url($merged_pdf_url); ?>" 
                                           class="button button-small" target="_blank">
                                           <span class="dashicons dashicons-visibility"></span> View PDF
                                        </a>
                                        <?php if (!empty($flipbook_url)): ?>
                                            <a href="<?php echo esc_url($flipbook_url); ?>" 
                                               class="button button-small" target="_blank">
                                               <span class="dashicons dashicons-book"></span> View Flipbook
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-pdf">Not merged yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo esc_attr(strtolower($row->status)); ?>">
                                    <?php echo esc_html($row->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($row->created_at))); ?></td>
                            <td class="actions">
                                <?php if (!empty($row->customer_bio_pdf)): ?>
                                    <button type="button" class="button button-small delete-pdf" 
                                            data-nonce="<?php echo wp_create_nonce('delete_pdf_nonce'); ?>"
                                            data-id="<?php echo esc_attr($row->id); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="10" class="no-records">
                            <span class="dashicons dashicons-media-document"></span>
                            <p>No Bio PDFs Found. Start by uploading a agent bio PDF.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.pdf-bundler-container {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.pdf-links {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.pdf-links .button {
    margin: 2px;
}

.customer-avatar {
    border-radius: 50%;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    text-align: center;
}

.status-badge.pending {
    background: #fff7e5;
    color: #996300;
}

.status-badge.completed {
    background: #edfaef;
    color: #0a5132;
}

.actions {
    display: flex;
    gap: 5px;
}

.actions .button {
    padding: 0 5px;
}

.actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    line-height: 1.3;
}

.delete-pdf {
    color: #dc3232;
    border-color: #dc3232;
}

.delete-pdf:hover {
    background: #dc3232;
    color: white;
}

.no-records {
    text-align: center;
    padding: 40px !important;
}

.no-records .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
    margin-bottom: 10px;
}

.no-records p {
    margin: 0;
    color: #666;
}

.button .dashicons {
    line-height: 1.4;
}

.no-pdf {
    color: #666;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.delete-pdf').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this PDF?')) {
            return;
        }

        const button = $(this);
        const id = button.data('id');
        const nonce = button.data('nonce');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_customer_pdf',
                pdf_id: id,
                nonce: nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                        // If no more records, show the "No PDFs" message
                        if ($('tbody tr').length === 0) {
                            $('tbody').html(`
                                <tr>
                                    <td colspan="9" class="no-records">
                                        <span class="dashicons dashicons-media-document"></span>
                                        <p>No Bio PDFs Found. Start by uploading a agent bio PDF.</p>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                } else {
                    alert(response.data || 'Failed to delete PDF');
                }
            },
            error: function() {
                alert('Failed to delete PDF');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script> 