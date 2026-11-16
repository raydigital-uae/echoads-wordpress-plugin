/* global jQuery, echoadsSettings */
jQuery(function ($) {
    var i18n = echoadsSettings.i18n;

    $('.echoads-color-picker').wpColorPicker();

    // API key visibility toggle
    $('#api-key-toggle').on('click', function (e) {
        e.preventDefault();
        var input = $('#' + echoadsSettings.apiKeyField);
        var showing = input.attr('type') === 'text';

        input.attr('type', showing ? 'password' : 'text');
        $(this).find('.eye-open').toggle(showing);
        $(this).find('.eye-closed').toggle(!showing);
        $(this).attr('title', showing ? i18n.showKey : i18n.hideKey);
    });

    // Health check runs on the server (admin-ajax), with the values currently in the form.
    $('#health-check-button').on('click', function (e) {
        e.preventDefault();
        var statusIndicator = $('#health-status');
        var responseDiv = $('#health-check-response');
        var apiKey = $('#' + echoadsSettings.apiKeyField).val();
        var baseUrl = $('#' + echoadsSettings.baseUrlField).val();

        function setStatus(className, text) {
            statusIndicator.removeClass().addClass('echoads-status-indicator ' + className).text(text);
        }

        if (!apiKey || !baseUrl) {
            setStatus('echoads-status-error', i18n.missingConfig);
            responseDiv.text(i18n.missingConfigMsg);
            return;
        }

        setStatus('echoads-status-loading', i18n.testing);
        responseDiv.empty();

        $.post(echoadsSettings.ajaxUrl, {
            action: 'echoads_health_check',
            nonce: echoadsSettings.nonce,
            api_key: apiKey,
            base_url: baseUrl
        })
            .done(function (response) {
                var data = response && response.data ? response.data : {};
                if (response && response.success) {
                    setStatus('echoads-status-success', i18n.success);
                    responseDiv.text(JSON.stringify(data.body, null, 2));
                    return;
                }
                setStatus('echoads-status-error', i18n.failed);
                var text = data.message || '';
                if (data.body) {
                    text += '\n' + (typeof data.body === 'string' ? data.body : JSON.stringify(data.body, null, 2));
                }
                responseDiv.text(text);
            })
            .fail(function (xhr, status, error) {
                setStatus('echoads-status-error', i18n.failed);
                responseDiv.text('Error: ' + error);
            });
    });
});
