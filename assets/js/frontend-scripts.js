jQuery(document).ready(function($) {
    var strings = pcvs_vars.strings;

    function messageCard(message, genuine) {
        return $('<div>', { 'class': 'pcvs-verification-card ' + (genuine ? 'genuine' : 'invalid') })
            .append($('<h3>', { 'class': 'pcvs-result-message', text: message }));
    }

    function safeUrl(value) {
        try {
            var url = new URL(value, window.location.href);
            return /^(https?:)$/.test(url.protocol) ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    $('#pcvs-verify-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var resultDiv = $('#pcvs-result');
        var submitButton = form.find('.pcvs-verify-btn');
        var buttonLabel = submitButton.text();
        var code = form.find('[name="code"]').val().trim();
        var nonce = form.find('[name="pcvs_nonce"]').val();

        if (submitButton.prop('disabled')) return;
        if (!code) {
            resultDiv.empty().append(messageCard(strings.emptyCode, false));
            return;
        }

        submitButton.prop('disabled', true).text(strings.verifying);
        resultDiv.attr('aria-busy', 'true').empty();

        $.ajax({
            url: pcvs_vars.ajax_url,
            type: 'POST',
            timeout: 30000,
            data: { action: 'pcvs_verify_code', code: code, pcvs_nonce: nonce },
            success: function(response) {
                if (!response || !response.data) {
                    resultDiv.empty().append(messageCard(strings.error, false));
                    return;
                }
                var data = response.data;
                var card = messageCard(data.message || strings.error, response.success);
                if (response.success) {
                    var wrapper = $('<div>', { 'class': 'pcvs-details-wrapper' });
                    var details = $('<dl>', { 'class': 'pcvs-details-list' });
                    if (Array.isArray(data.details)) {
                        data.details.forEach(function(item) {
                            var value = $('<dd>', { 'class': 'pcvs-detail-value', text: item.value });
                            if (item.key === 'warranty_status') {
                                value.addClass('pcvs-status');
                                // Compare translated display values rather than forming CSS classes from them.
                                if (item.value === strings.active) value.addClass('status-active');
                                if (item.value === strings.expired) value.addClass('status-expired');
                            }
                            details.append($('<div>', { 'class': 'pcvs-detail-item' })
                                .append($('<dt>', { 'class': 'pcvs-detail-label', text: item.label }), value));
                        });
                    }
                    wrapper.append(details);

                    var media = $('<div>', { 'class': 'pcvs-product-image' });
                    var imageUrl = data.image_url ? safeUrl(data.image_url) : '';
                    if (imageUrl) media.append($('<img>', { src: imageUrl, alt: strings.imageAlt }));
                    [
                        { url: data.certificate_url, label: strings.certificate },
                        { url: data.bill_url, label: strings.bill }
                    ].forEach(function(file) {
                        var url = file.url ? safeUrl(file.url) : '';
                        if (url) media.append($('<a>', {
                            href: url, 'class': 'pcvs-cert-button', download: '',
                            target: '_blank', rel: 'noopener noreferrer', text: file.label
                        }));
                    });
                    if (media.children().length) wrapper.append(media);
                    card.append(wrapper);
                }
                resultDiv.empty().append(card);
            },
            error: function() {
                resultDiv.empty().append(messageCard(strings.error, false));
            },
            complete: function() {
                resultDiv.attr('aria-busy', 'false');
                submitButton.prop('disabled', false).text(buttonLabel);
            }
        });
    });
});
