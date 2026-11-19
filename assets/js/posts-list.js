/* global jQuery, inlineEditPost, echoadsPostsList, wp */
/**
 * Posts > All Posts: fills the EchoAds box in Quick Edit from the row's audio status icon, and
 * starts audio generation through the same admin-ajax action as the post editor's meta box.
 */
(function ($) {
    'use strict';

    var settings = window.echoadsPostsList;
    if (!settings || typeof inlineEditPost === 'undefined') {
        return;
    }
    var i18n = settings.i18n;

    function statusIcon(postId) {
        return $('#post-' + postId).find('.echoads-audio-status');
    }

    function speak(text) {
        if (wp && wp.a11y && wp.a11y.speak) {
            wp.a11y.speak(text);
        }
    }

    function showMessage($message, text, type) {
        $message.removeClass('is-success is-error').addClass(type ? 'is-' + type : '').text(text);
        speak(text);
    }

    // Prefer the backend's own message (e.g. a rejected API key) over the generic HTTP one.
    function errorText(data) {
        var text = i18n.failed;
        if (!data || typeof data !== 'object') {
            return text;
        }
        var parsed = data.response_body_parsed;
        var detail = parsed && typeof parsed.message === 'string' ? parsed.message : data.error_message;
        if (data.message) {
            text += ' ' + data.message;
        }
        if (detail) {
            text += ' (' + detail + ')';
        }
        return text;
    }

    // Updates the row's icon without reloading the list; the next page load reads the saved meta.
    function markRow(postId, status) {
        var $icon = statusIcon(postId);
        $icon
            .removeClass(function (index, classes) {
                return (classes.match(/echoads-audio-status--\S+/g) || []).join(' ');
            })
            .addClass('echoads-audio-status--' + status.state)
            .attr({
                'data-echoads-state': status.state,
                'data-echoads-action': '',
                title: status.label
            });
        $icon.find('.dashicons').attr('class', 'dashicons ' + status.icon);
        $icon.find('.screen-reader-text').text(status.label);
    }

    function fillQuickEdit(postId) {
        var $box = $('#edit-' + postId).find('.echoads-quick-edit');
        var $icon = statusIcon(postId);
        if (!$box.length || !$icon.length) {
            return;
        }

        var action = $icon.attr('data-echoads-action') || '';
        var $status = $box.find('.echoads-quick-edit-status').text($icon.attr('title') || '');
        if (!action && $icon.attr('data-echoads-state') === 'none') {
            $status.append('<br>', document.createTextNode(i18n.publishFirst));
        }

        $box.find('.echoads-quick-edit-actions').toggle(action !== '');
        $box.find('.echoads-quick-edit-generate').prop('disabled', false).attr('data-echoads-action', action);
        $box.find('.echoads-quick-edit-message').removeClass('is-success is-error').text('');
    }

    // Quick Edit clones one hidden template per row, so fill it each time a row opens.
    var originalEdit = inlineEditPost.edit;
    inlineEditPost.edit = function (id) {
        var result = originalEdit.apply(this, arguments);
        fillQuickEdit(typeof id === 'object' ? this.getId(id) : id);
        return result;
    };

    $(document).on('click', '.echoads-quick-edit-generate', function (event) {
        event.preventDefault();

        var $button = $(this);
        var postId = parseInt(($button.closest('tr.inline-edit-row').attr('id') || '').replace('edit-', ''), 10);
        if (!postId) {
            return;
        }

        var $box = $button.closest('.echoads-quick-edit');
        var $spinner = $box.find('.spinner');
        var $message = $box.find('.echoads-quick-edit-message');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        showMessage($message, i18n.sending, '');

        $.post(settings.ajaxUrl, {
            action: 'echoads_generate_audio',
            post_id: postId,
            regenerate: $button.attr('data-echoads-action') === 'regenerate' ? 'true' : 'false',
            nonce: settings.nonce
        })
            .done(function (response) {
                if (response && response.success) {
                    markRow(postId, settings.queued);
                    $box.find('.echoads-quick-edit-actions').hide();
                    $box.find('.echoads-quick-edit-status').text(settings.queued.label);
                    showMessage($message, i18n.started, 'success');
                } else {
                    var data = response && response.data;
                    // E.g. the post's language is not set up in EchoAds: the row shows that right away.
                    if (data && data.status && data.status.state) {
                        markRow(postId, data.status);
                        $box.find('.echoads-quick-edit-status').text(data.status.label);
                    }
                    showMessage($message, errorText(data), 'error');
                    $button.prop('disabled', false);
                }
            })
            .fail(function (xhr) {
                showMessage($message, errorText(xhr.responseJSON && xhr.responseJSON.data), 'error');
                $button.prop('disabled', false);
            })
            .always(function () {
                $spinner.removeClass('is-active');
            });
    });
})(jQuery);
