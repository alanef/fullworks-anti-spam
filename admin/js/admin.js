(function ($) {
    const {__, _x, _n, sprintf} = wp.i18n;
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    $(function () {
        $(document).on('click', '.fwas-allow-deny .edit', function (e) {
            e.preventDefault();

            var td = $(this).closest('td');
            var allow_deny = td;
            var type = td.siblings('.type');
            var value = td.siblings('.value');
            var notes = td.siblings('.notes');
            var actions = allow_deny.find('.row-actions').html();  // <<< Save the .row-actions content
            var selected_allow_deny = allow_deny.find('.select-item').data('select');  // <<< Save allowDenySelect initial value
            var selected_allow_deny_text = allow_deny.find('.select-item').text();  // <<< Save allowDenySelect initial value
            var selected_type = type.find('.select-item').data('select');  // <<< Save typeSelect initial value
            var selected_type_text = type.find('.select-item').text();  // <<< Save typeSelect initial value
            var selected_value = value.text().trim();  // <<< Save inputValue initial value
            var selected_notes = notes.text().trim();

            if (allow_deny.find('select').length === 0 && type.find('select').length === 0
                && value.find('input').length === 0) {

                var allow_deny_options = '<select class="allowDenySelect">' +
                    '<option value="allow"' + (selected_allow_deny === 'allow' ? ' selected' : '') + '>' +
                    __('Allow', 'fullworks-anti-spam') + '</option>' +
                    '<option value="deny"' + (selected_allow_deny === 'deny' ? ' selected' : '') + '>' +
                    __('Deny', 'fullworks-anti-spam') + '</option>' +
                    '</select>';
                var type_options = '<select class="typeSelect">' +
                    '<option value="IP"' + (selected_type === 'IP' ? ' selected' : '') + '>'
                    + __('IP', 'fullworks-anti-spam') + '</option>' +
                    '<option value="email"' + (selected_type === 'email' ? ' selected' : '') + '>'
                    + __('Email', 'fullworks-anti-spam') + '</option>' +
                    '<option value="string"' + (selected_type === 'string' ? ' selected' : '') + '>'
                    + __('Expression', 'fullworks-anti-spam') + '</option>' +
                    '</select>';

                allow_deny.html(allow_deny_options);
                type.html(type_options);
                value.html('<input type="text" id="inputValue" value="' + selected_value + '"/>');
                notes.html('<textarea style="width: 100%" id="inputNotes" >' + selected_notes + '</textarea>');
                td.append('<br><br><button class="button button-primary edit-submit" type="button"'
                    + ' data-actions="' + encodeURIComponent(actions) + '">' + __('Save', 'fullworks-anti-spam') + '</button>');
                td.append('&nbsp;<button class="button edit-cancel" type="button" data-allow-deny="' + selected_allow_deny + '" '
                    + 'data-allow-deny-text="' + selected_allow_deny_text + '" data-type="' + selected_type + '" '
                    + 'data-type-text="' + selected_type_text + '" data-value="' + selected_value + '" data-notes="' + selected_notes
                    + '" data-actions="' + encodeURIComponent(actions) + '">' // include the actions data
                    + __('Cancel', 'fullworks-anti-spam') + '</button>');
            }
        });
        $(document).on("click", ".fwas-allow-deny .button.edit-cancel", function (e) {
            e.preventDefault();
            var btn = $(this);
            var td = btn.closest('td');
            td.parent().next('.error-message').remove();
            var allow_deny = td;
            var type = td.siblings('.type');
            var value = td.siblings('.value');
            var notes = td.siblings('.notes');
            // retrieve the stored values from the button
            var selected_allow_deny = btn.data('allow-deny');
            var selected_allow_deny_text = btn.data('allow-deny-text');
            var selected_type = btn.data('type');
            var selected_type_text = btn.data('type-text');
            var selected_value = btn.data('value');
            var selected_notes = btn.data('notes');
            // Retrieve the row-actions data
            var actions = decodeURIComponent(btn.data('actions'));


            // Reset everything to its initial state
            allow_deny.html('<div class="' + selected_allow_deny + ' select-item" data-selected="' + selected_allow_deny + '">' + selected_allow_deny_text + '</div>');
            allow_deny.append('<div class="row-actions">' + actions + '</div>');
            type.html('<div class="' + selected_type + ' select-item" data-selected="' + selected_type + '">' + selected_type_text + '</div>');

            value.text(selected_value);
            notes.html(selected_notes);


            // Remove Save and Cancel buttons
            td.find('.edit-submit, .edit-cancel').remove();
        });
        $(document).on("click", ".fwas-allow-deny .button.edit-submit", function (e) {
            e.preventDefault();
            var btn = $(this);
            var td = btn.closest('td');
            var allow_deny = td
            var type = td.siblings('.type');
            var value = td.siblings('.value');
            var notes = td.siblings('.notes');

            var updated_allow_deny = allow_deny.find('select option:selected').val();
            var updated_type = type.find('select option:selected').val();
            var updated_value = value.find('input').val();
            var updated_notes = notes.find('textarea').val();
            // Retrieve the row-actions data
            var actions = decodeURIComponent(btn.data('actions'));

            var allow_deny_mapping = {
                'allow': __('Allow', 'fullworks-anti-spam'),
                'deny': __('Deny', 'fullworks-anti-spam'),
            };

            var type_mapping = {
                'IP': __('IP', 'fullworks-anti-spam'),
                'email': __('Email', 'fullworks-anti-spam'),
                'string': __('Expression', 'fullworks-anti-spam'),
            };
            // Get the checkbox value
            var ID = td.parent().find('input[type=checkbox]').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    'action': 'fwantispam_ajax_handler',
                    'ID': ID,
                    'type': updated_type,
                    'value': updated_value,
                    'allow_deny': updated_allow_deny,
                    'notes': updated_notes,
                    'nonce': fwantispam_ajax_object.nonce
                },
                success: function (response) {
                    // Remove any existing error message row
                    td.parent().next('.error-message').remove();

                    if (response.success === true) {
                        td.html('<button class="button" type="button" id="submit">' + __('Save', 'fullworks-anti-spam') + '</button>');
                        allow_deny.html('<div class="' + updated_allow_deny + ' select-item" data-select="' + updated_allow_deny + '">' + allow_deny_mapping[updated_allow_deny] + '</div>');
                        allow_deny.append('<div class="row-actions">' + actions + '</div>');
                        type.html('<div class="' + updated_type + ' select-item" data-select="' + updated_type + '">' + type_mapping[updated_type] + '</div>');
                        value.text(updated_value);
                        notes.text(updated_notes);
                        $(this).remove();
                    } else {
                        var errorMessageRow = '<tr class="error-message"><td colspan="4" class="notice notice-error inline"><p>' + response.data + '</p></td></tr>';
                        $(errorMessageRow).insertAfter(td.parent());
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    // Remove any existing error message row
                    td.parent().next('.error-message').remove();

                    alert('An error occurred: ' + errorThrown);
                    var errorMessageRow = '<tr class="error-message"><td colspan="4" class="notice notice-error inline"><p>' + 'An error occurred: ' + errorThrown + '</p></td></tr>';
                    $(errorMessageRow).insertAfter(td.parent());
                }
            });
        });
        // add new allow deny rule
        $('.fwas-allow-deny #fwas-add-rule').click(function (e) {
            e.preventDefault();
            $('#the-list').prepend('<tr class="inputRow">' +
                '<td></td>' +
                '<td class="allow_deny column-allow_deny column-primary">' +
                '<select class="allowDenySelect"><option value="allow">' + __('Allow', 'fullworks-anti-spam') + '</option><option value="deny">' + __('Deny', 'fullworks-anti-spam') + '</option></select></td>' +
                '<td class="type column-type">' +
                '<select class="typeSelect"><option value="IP">' + __('IP', 'fullworks-anti-spam') + '</option><option value="email">' + __('Email', 'fullworks-anti-spam') + '</option><option value="string">' + __('Expression', 'fullworks-anti-spam') + '</option></select></td>' +
                '<td class="value column-value">' +
                '<input type="text" class="inputValue" />' +
                '<br><br><button class="button" type="button" id="fwas-allow-deny-submit">' + __('SAVE', 'fullworks-anti-spam') + '</button></td>' +
                '<td class="notes column-notes">' +
                '<textarea style="width: 100%" class="inputNotes" /></textarea>' +
                '</tr>' +
                '</td></tr><tr><td colspan="4" class="response"></td></tr>'
            );
        });

        $('.fwas-allow-deny #the-list').on('click', '#fwas-allow-deny-submit', function (e) {
            e.preventDefault();
            var row = $(this).closest('tr');
            var form = row.closest('form');
            var allow_deny = row.find('.allowDenySelect').val();
            var type = row.find('.typeSelect').val();
            var value = row.find('.inputValue').val();
            var notes = row.find('.inputNotes').val();
            var ID = row.find('#ID').length ? row.find('#ID').val() : null;
            // find the form this is in  and clear any thing that is a notice in the form's parent
            form.parent().prev('.fwas-notice').remove();


            $.ajax({
                url: ajaxurl,  // This is a variable that WordPress already sets for you
                type: 'POST',
                data: {
                    'action': 'fwantispam_ajax_handler',
                    'ID': ID,
                    'type': type,
                    'value': value,
                    'allow_deny': allow_deny,
                    'notes': notes,
                    'nonce': fwantispam_ajax_object.nonce
                },
                success: function (response) {
                    if (response.success === true) {
                        alert(__('Rule saved', 'fullworks-anti-spam'));
                        location.reload(true);
                    } else {
                        // put a notice error immediately above the containing form
                        form.parent().before('<div class="fwas-notice notice notice-error inline"><p>' + response.data + '</p></div>');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    // put a notice error immediately above the containing form
                    form.parent().before('<div class="fwas-notice notice notice-error inline"><p>' + __('An error occurred: ', 'fullworks-anti-spam') + errorThrown + '</p></div>');
                }
            });
        });

        

        // Handle dismiss upgrade notice
        $('.fwas-upgrade-notice .notice-dismiss').on('click', function (e) {
            e.preventDefault();
            var $notice = $(this).closest('.fwas-upgrade-notice');

            $.ajax({
                url: fwantispam_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    'action': 'fwas_dismiss_upgrade_notice',
                    'nonce': fwantispam_ajax_object.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $notice.fadeOut(300, function () {
                            $(this).remove();
                        });
                    }
                },
                error: function () {
                    // Still remove the notice even if AJAX fails
                    $notice.fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            });
        });
    });

})(jQuery);
