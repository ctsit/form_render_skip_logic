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
            $this->setJsSettings(array('modulePrefix' => $this->PREFIX));
            $this->includeJs('js/config.js');

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
        $settings = $this->getFormattedSettings($Proj->project_id);

        $target_forms = array();
        foreach ($settings['target_forms'] as $ti) {
            $form = $ti['target_form'];
            if (!isset($target_forms[$form])) {
                $target_forms[$form] = array();
            }

            $target_forms[$form][] = $ti;
        }

        $control_fields = array();
        foreach ($settings['control_fields'] as $cf) {
            $control_fields[$cf['control_field_key']] = $cf['control_field_key'];
        }

        $control_data = REDCap::getData($Proj->project_id, 'array', $record, $control_fields);
        if ($record && !isset($control_data[$record])) {
            // Handling new record case.
            $control_data = array($record => array());
        }

        // Getting events of the current arm.
        $events = array_keys($Proj->events[$arm]['events']);

        // Building forms access matrix.
        $forms_access = array();

        foreach ($control_data as $id => $data) {
            $control_values = array();
            foreach ($settings['control_fields'] as $i => $cf) {
                $ev = $cf['control_event_id'];
                $fd = $cf['control_field_key'];
                $value = $cf['control_default_value'];

                if (isset($data[$ev][$fd]) && Records::formHasData($id, $Proj->metadata[$fd]['form_name'], $ev)) {
                    $value = $data[$ev][$fd];
                }

                $control_values[$i] = $value;
            }

            $forms_access[$id] = array();

            foreach ($events as $event_id) {
                $forms_access[$id][$event] = array();

                foreach ($Proj->eventsForms[$event_id] as $form) {
                    $access = true;

                    if (isset($target_forms[$form])) {
                        foreach ($target_forms[$form] as $tf) {
                            if ($tf['target_event_select'] && !in_array($event_id, $tf['target_event_ids'])) {
                                // This rule does not apply for this event,
                                // skipping.
                                continue;
                            }

                            $access = true;
                            foreach ($tf['conditions'] as $cond) {
                                $a = $control_values[$cond['condition_key']];
                                $b = $cond['condition_value'];

                                if (($a === '' || $b  === '') && $a !== $b) {
                                    // Avoiding misleading comparisons
                                    // involving empty strings.
                                    $access = $cond['condition_operator'] == '<>';
                                }
                                else {
                                    switch ($cond['condition_operator']) {
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
                                            $matches = $a != $b;
                                            break;
                                        default:
                                            $matches = $a == $b;
                                    }
                                }

                                if (!$matches) {
                                    // This set of conditions is not satisfied.
                                    $access = false;
                                    break;
                                }
                            }

                            if ($access) {
                                // At least one set of conditions is fully
                                // satisfied, so the form should be displayed.
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
     * Sets JS settings.
     *
     * @param array $settings
     *   A keyed array containing settings for the current page.
     */
    protected function setJsSettings($settings) {
        echo '<script>formRenderSkipLogic = ' . json_encode($settings) . ';</script>';
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
}
