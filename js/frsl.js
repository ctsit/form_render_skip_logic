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
        if (this.href != "javascript:;" && 
            this.href.indexOf(app_path_webroot + 'DataEntry/index.php?') === -1) {
            return;
        }

        var params = getQueryParameters(this.href,this.getAttribute('onclick'));
        try {
            if (!formRenderSkipLogic.formsAccess[params.id][params.event_id][params.page]) {
                disableForm(this);
            }
        } catch (err) {
            if (this.firstChild.getAttribute('title') === 'Delete this event') {
                // on record home the final row is "delete all data on event" buttons and should not be processed
                return;
            }
        }
    });

    tidyUpDisplay();

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
        if ( $(cell).parent().hasClass('formMenuList') ) {
            $(cell).parent().remove();
        }
    }

    /**
     * Hides all opaque elements so that we have a nicer display
     */
    function tidyUpDisplay() {
        //-- for the table on the record home page, remove any empty rows
        if ( $('#event_grid_table').length ) {
            //-- loop over all rows
            $('#event_grid_table').find('tr').each(function(rowIndex, r){
                if ( rowIndex > 0 ) {
                    var numCells = $(this).find('td').length;
                    var numOpaque = 0;
                    $(this).find('td').each(function(colIndex, c){
                        if ( $(this).find('a[style*="opacity: 0.1"]').length ) {
                            numOpaque++;
                        }
                        if ( c.innerHTML.length === 0 ) {
                            numOpaque++;
                        }
                    });
                    if ( numOpaque == (numCells - 1 )  ) {
                        $(this).addClass('pleaseRemoveMeFRSL');
                    }
                }
            });
            $('.pleaseRemoveMeFRSL').remove();
            //-- Now reset the highlighting
            $('#event_grid_table').find('tr').each(function(rowIndex, r){
                if ( rowIndex > 0 ) {
                    $(this).removeClass('even');
                    $(this).removeClass('odd');
                    if (rowIndex % 2 === 0) {
                        $(this).addClass('even');
                    } else {
                        $(this).addClass('odd');
                    }
                }
            });
            //-- Now tidy-up any columns that should be tidied up
            var numRows = $('#event_grid_table tbody').find('tr').length - 1;
            var numCols = $('#event_grid_table').find('th').length;
            var emptyCol = [];
            $('#event_grid_table').find('th').each(function(colIndex, c){
                emptyCol[colIndex] = 1;
            });
            $('#event_grid_table tbody').find('tr').each(function(rowIndex, r){
                $(this).find('td').each(function(colIndex, c){
                    if ( c.innerHTML.length > 0 && emptyCol[colIndex] == 1 ) {
                        emptyCol[colIndex] = 0;
                    }
                });
            });

            $(emptyCol).each(function(idx,val){
                if ( val === 1 ) {
                    $('#event_grid_table').find('tr').each(function(rowIndex, r){
                        $(this).find('td,th').each(function(colIndex, c){
                            if ( colIndex === idx ) {
                                $(c).hide();
                            }
                        });
                    });
                }
            });

        }

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
