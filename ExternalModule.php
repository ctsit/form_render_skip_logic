<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Form Render Skip Logic.
 */

namespace FormRenderSkipLogic\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Project;
use Survey;
use REDCap;

/**
 * ExternalModule class for REDCap Form Render Skip Logic.
 */
class ExternalModule extends AbstractExternalModule {
    /**
     * @inheritdoc
     */
    function redcap_every_page_top($project_id) {
        if (!$project_id || !in_array(PAGE, array('DataEntry/record_status_dashboard.php', 'DataEntry/record_home.php'))) {
            return;
        }

        $location = substr(PAGE, 10, strlen(PAGE) - 14);
        $this->loadFRSL($location, $this->getNumericQueryParam('id'));
    }

    /**
     * @inheritdoc
     */
    function redcap_data_entry_form_top($project_id, $record = null, $instrument, $event_id, $group_id = null) {
        if (empty($record)) {
            return;
        }

        $this->loadFRSL('data_entry_form', $record, $event_id, $instrument);
    }

    /**
     * @inheritdoc
     */
    function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash, $response_id = null, $repeat_instance = 1) {
        if (empty($record)) {
            return;
        }

        $this->overrideSurveysStatuses($record, $event_id);

        global $Proj;
        $survey_id = $Proj->forms[$instrument]['survey_id'];
        if ($Proj->surveys[$survey_id]['survey_enabled']) {
            return;
        }

        // Access denied for this survey.
        if (!$redirect_url = Survey::getAutoContinueSurveyUrl($record, $form_name, $event_id, $repeat_instance)) {
            $redirect_url = APP_PATH_WEBROOT;
        }

        redirect($redirect_url);
    }

    /**
     * @inheritdoc
     */
    function redcap_save_record($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1) {
        if ($survey_hash) {
            $this->overrideSurveysStatuses($record, $event_id);
        }
    }

    /**
     * Gets forms access matrix.
     *
     * @param string $arm
     *   The arm name.
     * @param int $record
     *   The data entry record ID.
     *
     * @return array
     *   The forms access matrix. The array is keyed as follows:
     *   - record ID
     *   -- event ID
     *   --- instrument name: TRUE/FALSE
     */
    function getFormsAccessMatrix($arm, $record = null) {
        global $Proj;

        $settings = $this->getFormattedSettings($Proj->project_id);
        $field_name = $settings['control_field']['field_name'];
        $event_name = $settings['control_field']['event_name'];

        $bl_tree = array();
        foreach ($settings['target_instruments'] as $row) {
            $form = $row['instrument_name'];
            if (empty($form)) {
                continue;
            }

            if (!isset($bl_tree[$form])) {
                $bl_tree[$form] = array();
            }

            $bl_tree[$form][] = $row['control_field_value'];
        }

        $control_data = REDCap::getData($Proj->project_id, 'array', $record, $field_name);
        if ($record && !isset($control_data[$record])) {
            // Handling new record case.
            $control_data = array($record => array());
        }

        // Getting events of the current arm.
        $events = array_keys($Proj->events[$arm]['events']);

        // Building forms access matrix.
        $forms_access = array();
        foreach ($control_data as $id => $data) {
            $control_value = isset($data[$event_name][$field_name]) ? $data[$event_name][$field_name] : '';
            $forms_access[$id] = array();

            foreach ($events as $event) {
                $forms_access[$id][$event] = array();

                foreach ($Proj->eventsForms[$event] as $form) {
                    $forms_access[$id][$event][$form] = !isset($bl_tree[$form]) || in_array($control_value, $bl_tree[$form]);
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
     *   - survey
     * @param int $record
     *   The data entry record ID.
     * @param int $event_id
     *   The event ID. Only required when $location = "data_entry_form".
     * @param string $instrument
     *   The form/instrument name.
     */
    protected function loadFRSL($location, $record = null, $event_id = null, $instrument = null) {
        global $Proj;

        $arm = $event_id ? $Proj->eventInfo[$event_id]['arm_num'] : $this->getNumericQueryParam('arm', 1);
        $next_step_path = '';
        $forms_access = $this->getFormsAccessMatrix($arm, $record);

        if ($record && $event_id && $instrument) {
            $instruments = $Proj->eventsForms[$event_id];
            $curr_forms_access = $forms_access[$record][$event_id];

            $i = array_search($instrument, $instruments) + 1;
            $len = count($instruments);

            while ($i < $len) {
                if ($curr_forms_access[$instruments[$i]]) {
                    $next_instrument = $instruments[$i];
                    break;
                }

                $i++;
            }

            if (isset($next_instrument)) {
                // Path to the next available form in the current event.
                $next_step_path = APP_PATH_WEBROOT . 'DataEntry/index.php?pid=' . $Proj->project_id . '&id=' . $record . '&event_id=' . $event_id . '&page=' . $next_instrument;
            }

            // Access denied to the current page.
            if (!$forms_access[$record][$event_id][$instrument]) {
                if (!$next_step_path) {
                    $next_step_path = APP_PATH_WEBROOT . 'DataEntry/record_home.php?pid=' . $Proj->project_id . '&id=' . $record . '&arm=' . $arm;
                }

                redirect($next_step_path);
            }
        }

        $settings = array(
            'config' => $this->getFormattedSettings($Proj->project_id),
            'location' => $location,
            'formsAccess' => $forms_access,
            'nextStepPath' => $next_step_path,
        );

        $this->setJsSettings($settings);
        $this->includeJs('js/frsl.js');
    }

    /**
     * Checks for non authorized surveys and disables them for the current
     * request.
     *
     * @param int $record
     *   The data entry record ID.
     * @param int $event_id
     *   The event ID.
     */
    protected function overrideSurveysStatuses($record, $event_id) {
        global $Proj;

        $arm = $Proj->eventInfo[$event_id]['arm_num'];
        $forms_access = $this->getFormsAccessMatrix($arm, $record);

        foreach ($forms_access[$record][$event_id] as $form => $value) {
            if ($value || empty($Proj->forms[$form]['survey_id'])) {
                continue;
            }

            // Disabling surveys that are not allowed.
            $survey_id = $Proj->forms[$form]['survey_id'];
            $Proj->surveys[$survey_id]['survey_enabled'] = 0;
        }
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
     * Gets numaric URL query parameter.
     *
     * @param string $param
     *   The parameter name
     * @param mixed $default
     *   The default value if query parameter is not available.
     *
     * @return mixed
     *   The parameter from URL if available. The default value provided is
     *   returned otherwise.
     */
    function getNumericQueryParam($param, $default = null) {
        return empty($_GET[$param]) || !is_numeric($_GET[$param]) ? $default : $_GET[$param];
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
