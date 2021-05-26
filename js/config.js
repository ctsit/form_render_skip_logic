$(document).ready(function() {
    var $modal = $('#external-modules-configure-modal');
    $modal.on('show.bs.modal', function() {
        // Making sure we are overriding this modules's modal only.
        if ($(this).data('module') !== formRenderSkipLogic.modulePrefix) {
            return;
        }

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld === 'undefined') {
            ExternalModules.Settings.prototype.resetConfigInstancesOld = ExternalModules.Settings.prototype.resetConfigInstances;
        }

        ExternalModules.Settings.prototype.resetConfigInstances = function() {
            ExternalModules.Settings.prototype.resetConfigInstancesOld();

            if ($modal.data('module') !== formRenderSkipLogic.modulePrefix) {
                return;
            }

            $modal.addClass('frsl');

            var place2ndColumnField = function($row) {
                if ($row.hasClass('frsl-moved') || !$row.is(':visible')) {
                    return;
                }

                var $element = $row.find('.external-modules-input-element');
                var $target = $row.prev().find('.external-modules-input-element');

                $element.css('width', ($target.parent().width() - $target.outerWidth(true) - 10) + 'px');
                $element.position({
                    my: 'left+10 top',
                    at: 'right top',
                    of: $target[0],
                    collision: "none"
                });

                $row.addClass('frsl-moved');
            }

            var branchingLogicCheckboxes = function($checkbox) {
                var prefix = $checkbox.attr('name').replace('_select', '');
                $target = $modal.find('select[name^="' + prefix + '"]').parent().parent();

                if ($checkbox.is(':checked')) {
                    $target.show();
                }
                else {
                    $target.hide();
                }
            };

            var branchingLogicRadios = function($radio) {
                $radio.prop('checked', true);

                var suffix =  $radio.attr('name').slice($radio.attr('name').search('____'),$radio.attr('name').length);
                var selectorShow = '[name="control_event_id' + suffix + '"], [name="control_field_key' + suffix + '"]';
                var selectorHide = '[name="control_piping' + suffix + '"]';

                if ($radio.val() === 'advanced') {
                    var aux = selectorShow;
                    selectorShow = selectorHide;
                    selectorHide = aux;
                }

                $(selectorShow).parent().parent().show();
                $(selectorHide).parent().parent().hide();

                place2ndColumnField($('[name="control_event_id' + suffix + '"]').parent().parent());
            };

            var $checkboxes = $modal.find('tr[field="target_events_select"] .external-modules-input-element');
            $checkboxes.each(function() {
                branchingLogicCheckboxes($(this));
            });

            $checkboxes.change(function() {
                branchingLogicCheckboxes($(this));
            });

            $modal.find('tr[field="control_mode"]').each(function() {
                var $checked = $(this).find('.external-modules-input-element:checked');

                if ($checked.length === 0) {
                    $(this).find('.external-modules-input-element').each(function() {
                        // This is a workaround for an EM bug that does not load
                        // radios default values properly.
                        if (typeof this.attributes.checked !== 'undefined') {
                            $checked = $(this);
                            return false;
                        }
                    });

                    if ($checked.length === 0) {
                        $checked = $(this).find('.external-modules-input-element[value="default"]');
                    }
                }

                branchingLogicRadios($checked);
            });

            $modal.find('tr[field="control_mode"] .external-modules-input-element').change(function() {
                branchingLogicRadios($(this));
            });

            $modal.find('tr[field="control_piping"] td:first-child').each(function() {
                if ($(this).find('.frsl-piping-helper').length === 0) {
                    $(this).append(formRenderSkipLogic.helperButtons);
                }
            });

            $modal.find('tr[field="condition_value"]').each(function() {
                place2ndColumnField($(this));
            });
        };
    });

    $modal.on('hide.bs.modal', function() {
        // Making sure we are overriding this modules's modal only.
        if ($(this).data('module') !== formRenderSkipLogic.modulePrefix) {
            return;
        }

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld !== 'undefined') {
            ExternalModules.Settings.prototype.resetConfigInstances = ExternalModules.Settings.prototype.resetConfigInstancesOld;
        }

        $modal.removeClass('frsl');
    });
});
