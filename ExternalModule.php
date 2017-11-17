<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Form Render Skip Logic.
 */

namespace FormRenderSkipLogic\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Project;
use REDCap;

/**
 * ExternalModule class for REDCap Form Render Skip Logic.
 */
class ExternalModule extends AbstractExternalModule {
    /**
     * @inheritdoc
     */
    function hook_every_page_top($project_id) {
        if (!$project_id) {
            return;
        }

        $record = null;

        switch (PAGE) {
            case 'DataEntry/record_home.php':
                if (empty($_GET['id'])) {
                    break;
                }

                $record = $_GET['id'];

            case 'DataEntry/record_status_dashboard.php':
                $location = substr(PAGE, 10, strlen(PAGE) - 14);
                $arm = empty($_GET['arm']) ? 1 : $_GET['arm'];

                $this->loadRFSL($location, $arm, $record);
                break;
        }
    }

    /**
     * @inheritdoc
     */
    function hook_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null) {
        if (empty($record)) {
            return;
        }

        global $Proj;
        $this->loadRFSL('data_entry_form', $Proj->eventInfo[$event_id]['arm_num'], $record, $event_id, $instrument);
    }

    /**
     * Gets forms access matrix.
     *
     * @param string $arm
     *   The arm name.
     * @param int $record
     *   The data entry record ID.
     * @param array $settings
     *
     * @return array
     *   The forms access matrix. The array is keyed as follows:
     *   - record ID
     *   -- event ID
     *   --- instrument name: TRUE/FALSE
     */
    function getFormsAccessMatrix($arm, $record = null, $settings = null) {
        global $Proj;

        if (!$settings) {
            $settings = $this->getFormattedSettings($Proj->project_id);
        }

        $field_name = $settings['control_field']['field_name'];
        $event_name = $settings['control_field']['event_name'];

        $bl_tree = array();
        foreach ($settings['instruments_to_show'] as $row) {
            $value = $row['control_field_value'];
            if (!isset($bl_tree[$value])) {
                $bl_tree[$value] = array();
            }

            $bl_tree[$value][] = $row['instrument_name'];
        }

        $control_data = REDCap::getData($Proj->project_id, 'array', $record, $field_name);

        // Building forms access matrix.
        $forms_access = array();
        foreach ($control_data as $id => $data) {
            $control_value = $data[$event_name][$field_name];
            $forms_access[$id] = array();

            foreach (array_keys($Proj->events[$arm]['events']) as $event) {
                $forms_access[$id][$event] = array();

                foreach ($Proj->eventsForms[$event] as $form) {
                    if ($settings['control_mode'] == 'hide') {
                        $access = !isset($bl_tree[$control_value]) || !in_array($form, $bl_tree[$control_value]);
                    }
                    else {
                        $access = isset($bl_tree[$control_value]) && in_array($form, $bl_tree[$control_value]);
                    }

                    $forms_access[$id][$event][$form] = $access;
                }
            }
        }

        return $forms_access;
    }

    /**
     * Loads main feature functionality.
     *
     * @param string $location
     *   The location to apply FRSL. Can be:
     *   - data_entry_form
     *   - record_home
     *   - record_status_dashboard
     * @param string $arm
     *   The arm name.
     * @param int $record
     *   The data entry record ID.
     * @param int $event_id
     *   The event ID. Only required when $location = "data_entry_form".
     * @param string $instrument
     *   The form/instrument name.
     */
    protected function loadRFSL($location, $arm, $record = null, $event_id = null, $instrument = null) {
        global $Proj;

        $next_step_path = '';
        $forms_access = $this->getFormsAccessMatrix($arm, $record, $settings['config']);

        if ($record && $event_id && $instrument) {
            if (!$forms_access[$record][$event_id][$instrument]) {
                // Access denied to the current page.
                redirect(APP_PATH_WEBROOT . 'DataEntry/record_home.php?pid=' . $Proj->project_id . '&id=' . $record . '&arm=' . $arm);
            }

            $instruments = array_keys($forms_access[$record][$event_id]);
            $curr_forms_access = $forms_access[$record][$event_id];

            $i = array_search($instrument, $instruments) + 1;
            $len = count($instruments);

            while ($i < $len) {
                if ($curr_forms_access[$instruments[$i]]) {
                    // Path to the next available form in the current event.
                    $next_step_path = APP_PATH_WEBROOT . 'DataEntry/index.php?pid=' . $Proj->project_id . '&id=' . $record . '&event_id=' . $event_id . '&page=' . $instruments[$i];
                    break;
                }

                $i++;
            }
        }

        $settings = array(
            'config' => $this->getFormattedSettings($Proj->project_id),
            'location' => $location,
            'formsAccess' => $forms_access,
            'nextStepPath' => $next_step_path,
        );

        $this->setJsSettings($settings);
        $this->includeJs('js/rfsl.js');
    }

    /**
     * Formats settings into a hierarchical key-value pair array.
     *
     * @param int $project_id
     *   Enter a project ID to get project settings.
     *   Leave blank to get system settings.
     *
     * @return array $formmated
     *   The formatted settings.
     */
    function getFormattedSettings($project_id = null) {
        $config = $this->getConfig();

        if ($project_id) {
            $type = 'project';
            $settings = ExternalModules::getProjectSettingsAsArray($this->PREFIX, $project_id);
        }
        else {
            $type = 'system';
            $settings = ExternalModules::getSystemSettingsAsArray($this->PREFIX);
        }

        $formatted = array();
        foreach ($config[$type . '-settings'] as $field) {
            $key = $field['key'];

            if ($field['type'] == 'sub_settings') {
                // Handling sub settings.
                $formatted[$key] = array();

                if ($field['repeatable']) {
                    // Handling repeating sub settings.
                    foreach (array_keys($settings[$key]['value']) as $delta) {
                        foreach ($field['sub_settings'] as $sub_setting) {
                            $sub_key = $sub_setting['key'];
                            $formatted[$key][$delta][$sub_key] = $settings[$sub_key]['value'][$delta];
                        }
                    }
                }
                else {
                    foreach ($field['sub_settings'] as $sub_setting) {
                        $sub_key = $sub_setting['key'];
                        $formatted[$key][$sub_key] = reset($settings[$sub_key]['value']);
                    }
                }
            }
            else {
                $formatted[$key] = $settings[$key]['value'];
            }
        }

        return $formatted;
    }

    /**
     * Includes a local JS file.
     *
     * @param string $path
     *   The relative path to the js file.
     */
    protected function includeJs($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

    /**
     * Sets JS settings.
     *
     * @param array $settings
     *   A keyed array containing settings for the current page.
     */
    protected function setJsSettings($settings) {
        echo '<script>formRenderSkipLogic = ' . json_encode($settings) . ';</script>';
    }
}
