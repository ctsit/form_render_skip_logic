document.addEventListener('DOMContentLoaded', function() {
    start = performance.now();
    var $links;

    // start = performance.now();
    switch (formRenderSkipLogic.location) {
        case 'data_entry_form':
            overrideNextFormButtonsRedirect();
        $links = $('.formMenuList').find('a');
            break;
        case 'record_home':
        $links = $('#event_grid_table').find('a');
            break;
        case 'record_status_dashboard':
        // $links = $('#record_status_table a');
        // $links = $('#record_status_table').find('a');
        $links = $('#record_status_table').children('tbody').find('a');
            break;
    }
    // var timetaken = performance.now() - start;
    // console.log(`${timetaken} ms`);

    if (typeof $links === 'undefined' || $links.length === 0) {
        return false;
    }

    var start = performance.now();
    $links.each(function() {
        // console.log(this);
        if (this.href != "javascript:;" &&
            this.href.indexOf(app_path_webroot + 'DataEntry/index.php?') === -1) {
            return;
        }

        var params = getQueryParameters(this.href,this.getAttribute('onclick'));
        if (!formRenderSkipLogic.formsAccess[params.id][params.event_id][params.page]) {
            // console.log(this);
            // disableForm(this);
            $(this).addClass('disabledCell');
        }
    });
    // var l = links.length;
    // for (var i = 0; i<l; i++) {
    //     var link = links[i];
    //     if (links[i].href != "javascript:;" &&
    //         links[i].href.indexOf(app_path_webroot + 'DataEntry/index.php?') === -1) {
    //         // return false;
    //         // console.log(links[i]);
    //         continue;
    //     }

    //     var params = getQueryParameters(links[i].href,links[i].getAttribute('onclick'));
    //     if (!formRenderSkipLogic.formsAccess[params.id][params.event_id][params.page]) {
    //         disableForm(links[i]);
    //     }
    // }
    var timetaken = performance.now() - start;
    console.log(`${timetaken} ms`);

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
     * parameters in the given url. When handling repeating instruments
     * (i.e. the url points to js) the onclick call is picked apart
     * and returned as the parameters.
     */
    function getQueryParameters(url, click) {
        if (url == "javascript:;") {
            var tmp = click.replace(/ |'|\);/g,'').split(',')
            var parameters = {id: tmp[1], event_id: tmp[2], page: tmp[3]}
        }
        else {
            var parameters = {};
            var queryString = getQueryString(url);
            var reg = /([^?&=]+)=?([^&]*)/g;
            var keyValuePair;

            while (keyValuePair = reg.exec(queryString)) {
                parameters[keyValuePair[1]] = keyValuePair[2];
            }
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
            // Obs.: yes, this is a weird selector - "#" prefix is not being
            // used - but this approach is needed on this page because there
            // are multiple DOM elements with the same ID - which is
            // totally wrong.
            $('a[id="submit-btn-' + buttonName + '"]').hide();
        }
    }
});
