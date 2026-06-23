jQuery(document).ready(function($) {
  // Intercept contact form submission
  $(document).on('submit', '#redspider-contact-form', function(e) {
    e.preventDefault();
    var form = $(this);
    var statusMsg = form.find('.form-status');
    var btn = form.find('button[type="submit"]');

    if (!statusMsg.length) {
      // Find the row or append to the form
      form.append('<div class="form-status mt-3"></div>');
      statusMsg = form.find('.form-status');
    }

    statusMsg.html('<div style="color: #DE1515; font-weight: 600; margin-top: 15px;">Sending message...</div>');
    btn.prop('disabled', true);

    var formData = form.serialize() + '&action=redspider_contact_submit&security=' + redspider_ajax.nonce;

    $.ajax({
      url: redspider_ajax.ajax_url,
      type: 'POST',
      data: formData,
      success: function(response) {
        btn.prop('disabled', false);
        if (response.success) {
          statusMsg.html('<div style="background: rgba(46, 204, 113, 0.1); border: 1px solid #2ecc71; color: #2ecc71; padding: 15px 20px; border-radius: 8px; margin-top: 15px; text-align: left;">' + response.data.message + '</div>');
          form[0].reset();
        } else {
          statusMsg.html('<div style="background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; padding: 15px 20px; border-radius: 8px; margin-top: 15px; text-align: left;">' + response.data.message + '</div>');
        }
      },
      error: function() {
        btn.prop('disabled', false);
        statusMsg.html('<div style="background: rgba(231, 76, 60, 0.1); border: 1px solid #e74c3c; color: #e74c3c; padding: 15px 20px; border-radius: 8px; margin-top: 15px; text-align: left;">An error occurred. Please try again.</div>');
      }
    });
  });
});
