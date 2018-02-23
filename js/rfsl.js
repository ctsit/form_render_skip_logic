document.addEventListener('DOMContentLoaded', function() {
    var $links;

    switch (formRenderSkipLogic.location) {
        case 'data_entry_form':
            overrideNextFormButtonsRedirect();
            $links = $('.formMenuList a');
            break;
        case 'record_home':
            $links = $('#event_grid_table a');
            break;
        case 'record_status_dashboard':
            $links = $('#record_status_table a');
            break;
    }

    if (typeof $links === 'undefined' || $links.length === 0) {
        return false;
    }

    $links.each(function() {
        if (this.href.indexOf(app_path_webroot + 'DataEntry/index.php?') === -1) {
            return;
        }

        var params = getQueryParameters(this.href);
        if (!formRenderSkipLogic.formsAccess[params.id][params.event_id][params.page]) {
            disableForm(this);
        }
    });

    /**
     * Overrides next form buttons to redirect to the next available step.
     */
    function overrideNextFormButtonsRedirect() {
        // Handling "Save & Go to Next Form" button.
        if (formRenderSkipLogic.nextStepPath) {
            formRenderSkipLogic.saveNextForm = function() {
                appendHiddenInputToForm('save-and-redirect', formRenderSkipLogic.nextStepPath);
                dataEntrySubmit('submit-btn-savecontinue');
                return false;
            }

            // Overriding submit callback.
            $('[id="submit-btn-savenextform"]').attr('onclick', 'formRenderSkipLogic.saveNextForm()');
        }
        else {
            removeButtons('savenextform');
        }

        // Handling "Ignore and go to next form" button on required fields
        // dialog.
        $('#reqPopup').on('dialogopen', function(event, ui) {
            var buttons = $(this).dialog('option', 'buttons');

            $.each(buttons, function(i, button) {
                if (button.name !== 'Ignore and go to next form') {
                    return;
                }

                if (formRenderSkipLogic.nextStepPath) {
                    buttons[i] = function() {
                        window.location.href = formRenderSkipLogic.nextStepPath;
                    };
                }
                else {
                    delete buttons[i];
                }

                return false;
            });

            $(this).dialog('option', 'buttons', buttons);
        });
    }

    /**
     * Disables a link to a form.
     */
    function disableForm(cell) {
        cell.style.pointerEvents = 'none';
        cell.style.opacity = '.1';
    }

    /**
     * Returns the query string of the given url string.
     */
    function getQueryString(url) {
        url = decodeURI(url);
        return url.match(/\?.+/)[0];
    }

    /**
     * Returns a set of key-value pairs that correspond to the query
     * parameters in the given url.
     */
    function getQueryParameters(url) {
        var parameters = {};
        var queryString = getQueryString(url);
        var reg = /([^?&=]+)=?([^&]*)/g;
        var keyValuePair;

        while (keyValuePair = reg.exec(queryString)) {
            parameters[keyValuePair[1]] = keyValuePair[2];
        }

        return parameters;
    }

    /**
     * Removes the given submit buttons set.
     */
    function removeButtons(buttonName) {
        var $buttons = $('button[name="submit-btn-' + buttonName + '"]');

        // Check if buttons are outside the dropdown menu.
        if ($buttons.length !== 0) {
            $.each($buttons, function(index, button) {
                // Get first button in dropdown-menu.
                var replacement = $(button).siblings('.dropdown-menu').find('a')[0];

                // Modify button to behave like $replacement.
                button.id = replacement.id;
                button.name = replacement.name;
                button.onclick = replacement.onclick;
                button.innerHTML = replacement.innerHTML;

                // Get rid of replacement.
                $(replacement).remove();
            });
        }
        else {
            // Disable button inside the dropdown menu.
            $('a[onclick="dataEntrySubmit(\'submit-btn-' + buttonName + '\');return false;"]').hide();
        }
    }
});
