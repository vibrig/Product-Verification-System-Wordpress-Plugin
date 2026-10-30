jQuery(document).ready(function($) {
    // Remember the explicitly selected client while switching between projects.
    var projectSelect = $('#pcvs-project_id');
    var clientSelect = $('#pcvs-client_id');
    var manualClient = clientSelect.prop('disabled') ? '' : clientSelect.val();
    clientSelect.on('change', function() {
        if (!clientSelect.prop('disabled')) manualClient = clientSelect.val();
    });
    projectSelect.on('change', function() {
        var inheritedClient = projectSelect.find('option:selected').attr('data-client-id');
        var hasInheritedClient = !!inheritedClient && inheritedClient !== '0';
        clientSelect.val(hasInheritedClient ? inheritedClient : manualClient).prop('disabled', hasInheritedClient);
        $('#pcvs-client-inherited').prop('hidden', !hasInheritedClient);
        $('#pcvs-client-manual').prop('hidden', hasInheritedClient);
    });
    
    if (typeof $.fn.datepicker === 'function') {
        $('.pcvs-date-picker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: 'c-20:c+20'
        });
    }

    $('#pcvs-generate-code').on('click', function() {
        var randomPart = Math.floor(10000000 + Math.random() * 90000000).toString();
        var code = 'PC-' + Date.now().toString().slice(-4) + '-' + randomPart;
        var newField = '<li class="pcvs-code-item"><input type="text" name="product_codes[]" value="' + code + '" class="regular-text pcvs-input" readonly><button type="button" class="button pcvs-remove-code">Remove</button></li>';
        $('#pcvs-code-list').append(newField);
    });
    
    $(document).on('click', '.pcvs-remove-code', function() {
        $(this).closest('.pcvs-code-item').remove();
    });
    
    function pcvsMediaUploader(e, options) {
        e.preventDefault();
        
        var frame = wp.media({
            title: options.title,
            library: { type: options.libraryType },
            button: { text: options.buttonText },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $(options.idField).val(attachment.id);
            
            var previewHtml = '';
            if (options.libraryType === 'image') {
                var imageUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
                previewHtml = '<img src="' + imageUrl + '" style="max-width:200px; height:auto; display:block; margin-bottom:10px;">';
            } else {
                 previewHtml = '<p><a href="' + attachment.url + '" target="_blank">' + attachment.title + '</a> ';
            }
            previewHtml += '<a href="#" class="' + options.removeClass + '" style="color: #a00; text-decoration: none;">(Remove)</a></p>';

            $(options.previewDiv).html(previewHtml);
        });
        
        frame.open();
    }
    
    $(document).on('click', '.pcvs-upload-image-button', function(e) {
        pcvsMediaUploader(e, { title: 'Select Product Image', libraryType: 'image', buttonText: 'Use this image', idField: '#product_image', previewDiv: '.pcvs-image-preview', removeClass: 'pcvs-remove-image-button'});
    });
    
    $(document).on('click', '.pcvs-upload-cert-button', function(e) {
        pcvsMediaUploader(e, { title: 'Select or Upload Certificate', libraryType: '', buttonText: 'Use this file', idField: '#product_certificate', previewDiv: '.pcvs-cert-preview', removeClass: 'pcvs-remove-cert-button' });
    });

    $(document).on('click', '.pcvs-upload-bill-button', function(e) {
        pcvsMediaUploader(e, { title: 'Select or Upload Bill/Invoice', libraryType: '', buttonText: 'Use this file', idField: '#product_bill', previewDiv: '.pcvs-bill-preview', removeClass: 'pcvs-remove-bill-button' });
    });
    
    $(document).on('click', '.pcvs-remove-image-button', function(e) { e.preventDefault(); $('#product_image').val(''); $('.pcvs-image-preview').empty(); });
    $(document).on('click', '.pcvs-remove-cert-button', function(e) { e.preventDefault(); $('#product_certificate').val(''); $('.pcvs-cert-preview').empty(); });
    $(document).on('click', '.pcvs-remove-bill-button', function(e) { e.preventDefault(); $('#product_bill').val(''); $('.pcvs-bill-preview').empty(); });
});
