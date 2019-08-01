document.addEventListener('DOMContentLoaded', function() {
    var links;

        switch (formRenderSkipLogic.location) {
            case 'data_entry_form':
                overrideNextFormButtonsRedirect();
                links = $('.formMenuList').find('a');
                break;
            case 'record_home':
                links = $('#event_grid_table').find('a');
                break;
            case 'record_status_dashboard':
                links = $('#record_status_table').children('tbody').find('a');
                break;
        }

        if (typeof links === 'undefined' || links.length === 0) {
            return false;
        }

    // a for loop benchmarks marginally faster than jQuery each, though the difference is not noticable on a webpage
    for (var i = 0, l = links.length; i<l; i++) {
            var link = links[i];
            if (links[i].href != "javascript:;" &&
                    links[i].href.indexOf(app_path_webroot + 'DataEntry/index.php?') === -1) {
                continue;
            }

            var params = getQueryParameters(links[i].href,links[i].getAttribute('onclick'));
            if (!formRenderSkipLogic.formsAccess[params.id][params.event_id][params.page]) {
                //disableForm(links[i]);
                links[i].className += 'disabledCell';
            }
        }

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
        return url.substr(url.indexOf('?'));
    }



    /**
     * Returns a set of key-value pairs that correspond to the query
     * parameters in the given url. When handling repeating instruments
     * (i.e. the url points to js) the onclick call is picked apart
     * and returned as the parameters.
     */
    function getQueryParameters(url, click, neededParams=['&id', '&page', '&event_id']) {
        if (url == "javascript:;") {
            var tmp = click.replace(/ |'|\);/g,'').split(',');
            return {id: tmp[1], event_id: tmp[2], page: tmp[3]};
        }
        else {
            const l = neededParams.length;
            const queryString = getQueryString(url);
            var parameters = {};
            let loc = 0;
            for (let i = 0; i < l; i++) {
                let this_param = neededParams[i];
                loc = queryString.indexOf(this_param) + this_param.length; // record the index of the _end_ of the desired parameter
                const partial = queryString.substr(loc + 1); // clip the string to the remainder after the parameter, store in a temp variable
                parameters[this_param.substr(1)] = partial.substr(0, partial.indexOf('&')); // record everything until the next parameter
            }
            return parameters;
        }
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
            // Obs.: yes, this is a weird selector - "#" prefix is not being
            // used - but this approach is needed on this page because there
            // are multiple DOM elements with the same ID - which is
            // totally wrong.
            $('a[id="submit-btn-' + buttonName + '"]').hide();
        }
    }
});
