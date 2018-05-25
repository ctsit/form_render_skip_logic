formRenderSkipLogic.rebuildControlFields = function(defaultValues) {
    var defaults = {};
    if (typeof defaultValues !== 'undefined' && defaultValues.length > 0) {
        $.each(defaultValues, function(i, group) {
            $.each(group, function(j, value) {
                defaults['condition_key____' + i + '____' + j] = value;
            });
        });
    }

    var cf = {};

    $('[name^="control_event_id____"], [name^="control_field_key____"]').each(function() {
        var parts = $(this).prop('name').split('____');
        var key = parts[0];
        var i = parts[1];

        if (typeof cf[i] === 'undefined') {
            cf[i] = {};
        }

        cf[i][key] = $(this).val();
    });

    var options = '';
    $.each(cf, function(i, tuple) {
        var label = [];
        $.each(tuple, function(key, value) {
            if (value === '') {
                return false;
            }

            label.push($('select[name="' + key + '____' + i + '"] option[value="' + value + '"]').text());
        });

        if (label.length === 2) {
            options += '<option class="custom-opt" value="' + i + '">' + label.join(' - ') + '</option>';
        }
    });

    $('select[name^="condition_key____"').each(function() {
        var currVal = $(this).val();
        if (currVal === '') {
            var name = $(this).prop('name');
            if (typeof defaults[name] !== 'undefined') {
                currVal = defaults[name];
            }
        }

        $(this).find('.custom-opt').remove();
        $(this).append(options);

        var $selected = $(this).find('option[value="' + currVal + '"]');
        if ($selected.length === 0) {
            $selected = $(this).find('option[value=""]');
        }

        $selected.prop('selected', true);
    });
};

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

            var params = {
                moduleDirectoryPrefix: formRenderSkipLogic.modulePrefix,
                pid: ExternalModules.PID
            };

            $.post(ExternalModules.BASE_URL + 'manager/ajax/get-settings.php', params, function(data) {
                if (data.status !== 'success') {
                    return;
                }

                var defaultValues = {};
                if (typeof data.settings.condition_key !== 'undefined') {
                    defaultValues = data.settings.condition_key.value;
                }

                formRenderSkipLogic.rebuildControlFields(defaultValues);
            });

            $('[name^="control_event_id____"], [name^="control_field_key____"]').change(formRenderSkipLogic.rebuildControlFields);
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
    });
});
