<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Form Render Skip Logic.
 */

namespace FormRenderSkipLogic\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use Form;
use Piping;
use Project;
use Records;
use Survey;
use RCView;
use REDCap;

/**
 * ExternalModule class for REDCap Form Render Skip Logic.
 */
class ExternalModule extends AbstractExternalModule {
    /**
     * @inheritdoc
     */
    function redcap_every_page_top($project_id) {
        if (strpos(PAGE, 'ExternalModules/manager/project.php') !== false) {
            $this->setJsSettings(array('modulePrefix' => $this->PREFIX, 'helperButtons' => $this->getPipingHelperButtons()));
            $this->includeJs('js/config.js');
            $this->includeCss('css/config.css');

            return;
        }

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
            $record = $this->getNumericQueryParam('id');
        }

        $this->loadFRSL('data_entry_form', $record, $event_id, $instrument);
    }

    /**
     * @inheritdoc
     */
    function redcap_survey_page_top($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash, $response_id = null, $repeat_instance = 1) {
        if (empty($record)) {
            $record = $this->getNumericQueryParam('id');
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

        $this->redirect($redirect_url);
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

        // Getting events of the current arm.
        $events = array_keys($Proj->events[$arm]['events']);

        $target_forms = array();
        $settings = $this->getFormattedSettings($Proj->project_id);

        $control_fields = array();
        $control_fields_keys = array();

        $i = 0;
        foreach ($settings['control_fields'] as $cf) {
            if ($cf['control_mode'] == 'default' && (!$cf['control_event_id'] || !$cf['control_field_key'])) {
                // Checking for required fields in default mode.
                continue;
            }

            if ($cf['control_mode'] == 'advanced' && !$cf['control_piping']) {
                // Checking for required fields in advanced mode.
                continue;
            }

            $control_fields_keys[] = $cf['control_field_key'];
            if (empty($cf['control_default_value']) && !is_numeric($cf['control_default_value'])) {
                $cf['control_default_value'] = '';
            }

            $branching_logic = $cf['branching_logic'];
            unset($cf['branching_logic']);

            foreach ($branching_logic as $bl) {
                if (empty($bl['target_forms'])) {
                    continue;
                }

                $control_fields[$i] = $cf + $bl;
                $target_events = $bl['target_events_select'] ? $bl['target_events'] : $events;

                foreach ($target_events as $event_id) {
                    if (!isset($target_forms[$event_id])) {
                        $target_forms[$event_id] = array();
                    }

                    foreach ($bl['target_forms'] as $form) {
                        if (!isset($target_forms[$event_id][$form])) {
                            $target_forms[$event_id][$form] = array();
                        }

                        $target_forms[$event_id][$form][] = $i;
                    }
                }

                $i++;
            }
        }

        $control_data = REDCap::getData($Proj->project_id, 'array', $record, $control_field_keys);
        if ($record && !isset($control_data[$record])) {
            // Handling new record case.
            $control_data = array($record => array());
        }

        // Building forms access matrix.
        $forms_access = array();
        foreach ($control_data as $id => $data) {
            $control_values = array();
            foreach ($control_fields as $i => $cf) {
                $ev = $cf['control_event_id'];
                $fd = $cf['control_field_key'];

                $a = $cf['control_default_value'];
                $b = $cf['condition_value'];

                if ($cf['control_mode'] == 'advanced') {
                    $piped = Piping::replaceVariablesInLabel($cf['control_piping'], $id, $event_id, 1, array(), true, null, false);
                    if ($piped !== '') {
                        $a = $piped;
                    }
                }
                else {
                    if (isset($data[$ev][$fd]) && Records::formHasData($id, $Proj->metadata[$fd]['form_name'], $ev)) {
                        $a = $data[$ev][$fd];
                    }
                }

                switch ($cf['condition_operator']) {
                    case '>':
                        $matches = $a > $b;
                        break;
                    case '>=':
                        $matches = $a >= $b;
                        break;
                    case '<':
                        $matches = $a < $b;
                        break;
                    case '<=':
                        $matches = $a <= $b;
                        break;
                    case '<>':
                        $matches = $a !== $b;
                        break;
                    default:
                        $matches = $a === $b;
                }

                $control_values[$i] = $matches;
            }

            $forms_access[$id] = array();

            foreach ($events as $event_id) {
                $forms_access[$id][$event] = array();

                foreach ($Proj->eventsForms[$event_id] as $form) {
                    $access = true;

                    if (isset($target_forms[$event_id][$form])) {
                        $access = false;

                        foreach ($target_forms[$event_id][$form] as $i) {
                            if ($control_values[$i]) {
                                // If one condition is satisfied, the form
                                // should be displayed.
                                $access = true;
                                break;
                            }
                        }
                    }

                    $forms_access[$id][$event_id][$form] = $access;
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

                $this->redirect($next_step_path);
                return;
            }
        }

        $settings = array(
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
     * @return array
     *   The formatted settings.
     */
    function getFormattedSettings($project_id = null) {
        $settings = $this->getConfig();

        if ($project_id) {
            $settings = $settings['project-settings'];
            $values = ExternalModules::getProjectSettingsAsArray($this->PREFIX, $project_id);
        }
        else {
            $settings = $settings['system-settings'];
            $values = ExternalModules::getSystemSettingsAsArray($this->PREFIX);
        }

        return $this->_getFormattedSettings($settings, $values);
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
     * Redirects user to the given URL.
     *
     * This function basically replicates redirect() function, but since EM
     * throws an error when an exit() is called, we need to adapt it to the
     * EM way of exiting.
     */
    protected function redirect($url) {
        if (headers_sent()) {
            // If contents already output, use javascript to redirect instead.
            echo '<script>window.location.href="' . $url . '";</script>';
        }
        else {
            // Redirect using PHP.
            header('Location: ' . $url);
        }

        $this->exitAfterHook();
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
     * Includes a local CSS file.
     *
     * @param string $path
     *   The relative path to the css file.
     */
    protected function includeCss($path) {
        echo '<link rel="stylesheet" href="' . $this->getUrl($path) . '">';
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

    /**
     * Gets Piping helper buttons.
     */
    protected function getPipingHelperButtons() {
        global $lang;

        $this->includeCss('css/piping-helper.css');
        $buttons = array(
            'green' => array(
                'callback' => 'smartVariableExplainPopup',
                'contents' => '[<i class="fas fa-bolt fa-xs"></i>] ' . $lang['global_146'],
            ),
            'purple' => array(
                'callback' => 'pipingExplanation',
                'contents' => RCView::img(array('src' => APP_PATH_IMAGES . 'pipe.png')) . $lang['info_41'],
            ),
        );

        $output = '';
        foreach ($buttons as $color => $btn) {
            $output .= RCView::button(array('class' => 'btn btn-xs btn-rc' . $color . ' btn-rc' . $color . '-light', 'onclick' => $btn['callback'] . '(); return false;'), $btn['contents']);
        }

        return RCView::span(array('class' => 'frsl-piping-helper'), $output);
    }

    /**
     * Auxiliary function for getFormattedSettings().
     */
    protected function _getFormattedSettings($settings, $values, $inherited_deltas = array()) {
        $formatted = array();

        foreach ($settings as $setting) {
            $key = $setting['key'];
            $value = $values[$key]['value'];

            foreach ($inherited_deltas as $delta) {
                $value = $value[$delta];
            }

            if ($setting['type'] == 'sub_settings') {
                $deltas = array_keys($value);
                $value = array();

                foreach ($deltas as $delta) {
                    $sub_deltas = array_merge($inherited_deltas, array($delta));
                    $value[$delta] = $this->_getFormattedSettings($setting['sub_settings'], $values, $sub_deltas);
                }

                if (empty($setting['repeatable'])) {
                    $value = $value[0];
                }
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }

    /**
     * checks if FRSL version 2.X settings exist.
     */
     function CheckIfVersion2SettingsExist() {
       $module_id = $this->getFRSLModuleId();

       //search for existing 2.X settings
       $q = "SELECT 1 FROM redcap_external_module_settings WHERE external_module_id='$module_id' AND `key` IN ('control_field', 'event_name', 'event_name', 'field_name', 'enabled_before_ctrl_field_is_set', 'target_instruments', 'instrument_name', 'control_field_value')";
       $result = $this->query($q);

       //if we got something return true, otherwise false
       $settings_exist = !empty($result->fetch_assoc());

       return $settings_exist;
     }

    /**
     * gets external module_id for FRSL.
     * cannot use ExternalModules::getIdForPrefix() because it is private.
     */
     function getFRSLModuleId() {
       $q = "SELECT external_module_id FROM redcap_external_modules where directory_prefix = '" . $this->PREFIX . "'" ;
       $result = $this->query($q);
       $id = $result->fetch_assoc()['external_module_id'];

       return $id;
     }

    /**
     * gets FRSLv2.x.x settings for each as an associative array indexed by
     * project_id, where each project setting is an associative array indexed by
     * setting name and maps to a setting value
     * @return array $settings
     */
     function getV2XSettings() {
       $module_id = $this->getFRSLModuleId();

       //get old settings data
       $q = "SELECT project_id, `key`, value FROM redcap_external_module_settings WHERE external_module_id='$module_id' AND `key` IN ('control_field', 'event_name', 'event_name', 'field_name', 'enabled_before_ctrl_field_is_set', 'target_instruments', 'instrument_name', 'control_field_value')";
       $result = $this->query($q);

       //create data stucture to represent old settings data
       $settings = [];
       while($row = $result->fetch_assoc()) {
         $project_id = $row["project_id"];
         $key = $row["key"];
         $value = $row["value"];
         $settings[$project_id][$key] = $value;
       }

       return $settings;
     }

    /**
     * converts settings from from FRSL_v2.X to FRSL_v3.X
     * @param array $old_settings, array of v2 settings indexed by project_id
     * @return array $new_settings, array of v3 settings indexed by project_id
     */
     function convert2XSettingsTo3XSettings($old_settings) {
       $new_settings = [];
       foreach ($old_settings as $project_id => $old_setting) {

         //generate some of the new settings using the old "instrument_name" values
         $old_instrument_names = json_decode($old_setting["instrument_name"]);
         $old_instrument_count = count($old_instrument_names);
         $branching_logic = [];
         $condition_operator = [];
         $target_forms = [];
         $target_events_select = [];
         $target_events = [];

         for($i = 0; $i < $old_instrument_count; $i++) {
           $branching_logic[] = true;
           $condition_operator[] = null;
           $target_forms[] = [$old_instrument_names[$i]];
           $target_events_select[] = false;
           $target_events[] = [null];
         }

         //convert to nested JSON-arrays for storage
         $branching_logic = "[" . json_encode($branching_logic) . "]";
         $condition_operator = "[" . json_encode($condition_operator) . "]";
         $target_forms = "[" . json_encode($target_forms) . "]";
         $target_events_select =  "[" . json_encode($target_events_select) . "]";
         $target_events = "[" . json_encode($target_events) . "]";

         //create sub-structure for project setting
         $setting = [];

         /*added extra angle brackets around some values because every new config
          is stored as a JSON-array or as a nested JSON-array*/
         $setting["control_fields"] = $old_setting["control_field"];
         $setting["control_mode"] = '["default"]';
         $setting["control_event_id"] = $old_setting["event_name"];
         $setting["control_field_key"] = $old_setting["field_name"];
         $setting["control_piping"] = "[null]";
         $setting["control_default_value"] = "[null]";
         $setting["branching_logic"] = $branching_logic;
         $setting["condition_value"] = "[" . $old_setting["control_field_value"] . "]";
         $setting["condition_operator"] = $condition_operator;
         $setting["target_forms"] = $target_forms;
         $setting["target_events_select"] = $target_events_select;
         $setting["target_events"] = $target_events;

         //store in main data structure
         $new_settings[$project_id] = $setting;
       }

       return $new_settings;

     }

}
