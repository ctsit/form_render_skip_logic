<?php
/**
 * @file
 * Provides ExternalModule class for REDCap Form Render Skip Logic.
 */

namespace FormRenderSkipLogic\ExternalModule;

use Calculate;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use FormRenderSkipLogic\Migration\Migration;
use Form;
use LogicTester;
use Piping;
use Project;
use Records;
use Survey;
use RCView;
use REDCap;

require_once dirname(__FILE__) . '/Migration.php';

/**
 * ExternalModule class for REDCap Form Render Skip Logic.
 */
class ExternalModule extends AbstractExternalModule {
    protected static $accessMatrix;

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
        if (!$redirect_url = Survey::getAutoContinueSurveyUrl($record, $instrument, $event_id, $repeat_instance)) {
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
     * @inheritdoc
     */
    function redcap_module_system_change_version($version, $old_version) {
        $this->migrateSettings();
    }

    /**
     * @inheritdoc
     */
    function redcap_module_system_enable($version) {
        $this->migrateSettings();
    }

    /**
     * Gets forms access matrix.
     *
     * @param int $event_id
     *   The current event ID.
     * @param int $record
     *   The current record ID.
     *
     * @return array
     *   The forms access matrix. The array is keyed as follows:
     *   - record ID
     *   -- event ID
     *   --- instrument name: TRUE/FALSE
     */
    function getFormsAccessMatrix($event_id = null, $record) {
        if (isset(self::$accessMatrix)) {
            return self::$accessMatrix;
        }

        global $Proj;

        if ($event_id) {
            $events = array($event_id);
        }
        else {
            // Getting events of the current arm.
            $arm = $event_id ? $Proj->eventInfo[$event_id]['arm_num'] : $this->getNumericQueryParam('arm', 1);
            $events = array_keys($Proj->events[$arm]['events']);
        }

        $target_forms = array();
        $settings = $this->getFormattedSettings($Proj->project_id);

        $control_fields = array();
        $control_fields_keys = array($Proj->table_pk);

        $i = 0;
        foreach ($settings['control_fields'] as $cf) {
            if ($cf['control_mode'] == 'default') {
                if (!$cf['control_field_key']) {
                    // Checking for required fields in default mode.
                    continue;
                }

                $control_fields_keys[] = $cf['control_field_key'];
            }

            if ($cf['control_mode'] == 'advanced' && !$cf['control_piping']) {
                // Checking for required fields in advanced mode.
                continue;
            }

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
                $target_events = $bl['target_events_select'] ? array_intersect($bl['target_events'], $events) : $events;

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

        $control_data = REDCap::getData($Proj->project_id, 'array', $record, $control_field_keys, $events);
        if ($record && !isset($control_data[$record])) {
            // Handling new record case.
            $control_data = array($record => array());
        }

        // Building forms access matrix.
        $forms_access = array();
        foreach ($control_data as $id => $data) {
            $control_values = array();

            foreach ($control_fields as $i => $cf) {
                $b = $cf['condition_value'];
                $control_values[$i] = array();

                if ($cf['control_mode'] == 'advanced') {
                    // On advanced mode, it is required to run Piping for each
                    // event in order to handle relative variables.
                    foreach ($events as $ev) {
                        $a = $cf['control_default_value'];

                        $piped = Piping::pipeSpecialTags($cf['control_piping'], $Proj->project_id, $id, $ev, null, null, true);
                        $piped = Piping::replaceVariablesInLabel($piped, $id, $ev, 1, array(), false);

                        if ($piped !== '') {
                            $piped = Calculate::formatCalcToPHP($piped);

                            // The trick here is to enable Piping with span
                            // wrappers and then replace them with quotes, so
                            // the strings (empty or not) will properly be
                            // quoted in the formula.
                            $piped = preg_replace('/<span[^>]*piping_receiver[^>]*>/', '"', str_replace('</span>', '"', $piped));
                            $piped = strval(LogicTester::evaluateCondition($piped));

                            if ($piped !== '') {
                                $a = $piped;
                            }
                        }

                        $control_values[$i][$ev] = $this->_calculateCondition($a, $b, $cf['condition_operator']);
                    }
                }
                else {
                    $fd = $cf['control_field_key'];

                    if ($ev = $cf['control_event_id']) {
                        $a = $this->_getDataIfExists($id, $ev, $fd, $data, $cf['control_default_value']);
                        $matches = $this->_calculateCondition($a, $b, $cf['condition_operator']);
                        $control_values[$i] = array_combine($events, array_fill(0, count($events), $matches));
                    }
                    else {
                        // If event has not been specified, we need to get the
                        // the control field for each event.
                        foreach ($events as $ev) {
                            $a = $this->_getDataIfExists($id, $ev, $fd, $data, $cf['control_default_value']);
                            $control_values[$i][$ev] = $this->_calculateCondition($a, $b, $cf['condition_operator']);
                        }
                    }
                }
            }

            $forms_access[$id] = array();

            foreach ($events as $event_id) {
                $forms_access[$id][$event] = array();

                foreach ($Proj->eventsForms[$event_id] as $form) {
                    $access = true;

                    if (isset($target_forms[$event_id][$form])) {
                        $access = false;

                        foreach ($target_forms[$event_id][$form] as $i) {
                            if ($control_values[$i][$event_id]) {
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

        self::$accessMatrix = $forms_access;
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

        $next_step_path = '';
        $forms_access = $this->getFormsAccessMatrix($event_id, $record);

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
                    $arm = $event_id ? $Proj->eventInfo[$event_id]['arm_num'] : $this->getNumericQueryParam('arm', 1);
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

        $forms_access = $this->getFormsAccessMatrix($event_id, $record);

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

        $output .= RCView::br() . RCView::a(array('href' => 'javascript:;', 'onclick' => 'helpPopup("ss78");'), $lang['design_165']);
        return RCView::br() . RCView::span(array('class' => 'frsl-piping-helper'), $output);
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
     * Auxiliary function to calculate condition.
     */
    function _calculateCondition($a, $b, $op = '=') {
        switch ($op) {
            case '>':
                return $a > $b;
            case '>=':
                return $a >= $b;
            case '<':
                return $a < $b;
            case '<=':
                return $a <= $b;
            case '<>':
                return $a !== $b;
        }

        return $a === $b;
    }

    /**
     * Auxiliary function to get field data if exits.
     */
    function _getDataIfExists($record, $event_id, $field_name, $data, $default = '') {
        if (!isset($data[$event_id][$field_name])) {
            return $default;
        }

        global $Proj;

        if (!Records::formHasData($record, $Proj->metadata[$field_name]['form_name'], $event_id)) {
            return $default;
        }

        return $data[$event_id][$field_name];
    }

    /**
     * migrates stored module settings from v2.x.x to v3.x.x if needed.
     */
    function migrateSettings() {
        $migrate = new Migration($this->PREFIX);

        //migrate settings only if version 2 settings exist and version 3 settings
        //do not exist.
        if ($migrate->checkIfVersionSettingsExist("v2.0.0") && !$migrate->checkIfVersionSettingsExist("v3.0.0")) {
            $old_setting = $migrate->getV2Settings();
            $new_setting = $migrate->convertV2SettingsToV3Settings($old_setting);
            $migrate->storeV3Settings($new_setting);
        }
    }
}
