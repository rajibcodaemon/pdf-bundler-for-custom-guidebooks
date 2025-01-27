jQuery(document).ready(function($) {
	const customerSelect = $('#customer-select');
	const loadingDiv = $('.customer-loading');
	const detailsCard = $('.customer-details-card');
	const messageDiv = $('.upload-message');

	if (customerSelect.length) {
		customerSelect.on('change', function() {
			const customerId = $(this).val();
			console.log('Customer selected:', customerId);
			
			if (!customerId) {
				detailsCard.hide();
				return;
			}

			loadingDiv.show();
			detailsCard.hide();

			$.ajax({
				url: pdfBundlerAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'load_customer_details',
					customer_id: customerId,
					nonce: pdfBundlerAdmin.nonce
				},
				success: function(response) {
					console.log('AJAX response:', response);
					if (response.success) {
						const data = response.data;
						$('#customer-avatar').attr('src', data.profile_picture);
						$('#customer-name').text(data.first_name + ' ' + data.last_name);
						$('#customer-email').text(data.billing_email);
						$('#customer-phone').text(data.billing_phone);
						$('#customer-address').text(data.billing_address_1);
						$('#customer-location').text(
							`${data.billing_city}, ${data.billing_state} ${data.billing_postcode}`
						);
						$('#selected-customer-id').val(customerId);
						detailsCard.fadeIn();
					}
				},
				complete: function() {
					loadingDiv.hide();
				}
			});
		});
	}

	// Handle PDF upload form submission
	$('#bio-pdf-form').on('submit', function(e) {
		e.preventDefault();
		
		const formData = new FormData(this);
		const submitButton = $(this).find('button[type="submit"]');
		const originalText = submitButton.text();
		
		submitButton.prop('disabled', true).text('Uploading...');
		messageDiv.hide();

		$.ajax({
			url: pdfBundlerAdmin.ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				console.log('Upload response:', response);
				if (response.success) {
					messageDiv
						.removeClass('error')
						.addClass('success')
						.html('PDF uploaded successfully! Redirecting...')
						.show();
					
					// Redirect to plugin's customer list page after short delay
					setTimeout(function() {
						window.location.href = pdfBundlerAdmin.adminUrl + 'admin.php?page=pdf-bundler';
					}, 1500);
				} else {
					messageDiv
						.removeClass('success')
						.addClass('error')
						.html(response.data)
						.show();
				}
			},
			error: function() {
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
