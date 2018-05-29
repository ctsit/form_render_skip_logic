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

            var branchingLogic = function($checkbox) {
                var prefix = $checkbox.attr('name').replace('_select', '');
                $target = $modal.find('select[name^="' + prefix + '"]').parent().parent();

                if ($checkbox.is(':checked')) {
                    $target.show();
                }
                else {
                    $target.hide();
                }
            };

            var $checkboxes = $modal.find('tr[field="target_events_select"] .external-modules-input-element');
            $checkboxes.each(function() {
                branchingLogic($(this));
            });

            $checkboxes.change(function() {
                branchingLogic($(this));
            });

            $modal.find('tr[field="control_field_key"], tr[field="condition_value"]').each(function() {
                $(this).find('.external-modules-input-element').position({
                    my: 'left+10 top',
                    at: 'right top',
                    of: $(this).prev().find('.external-modules-input-element')[0],
                    collision: "none"
                });
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
