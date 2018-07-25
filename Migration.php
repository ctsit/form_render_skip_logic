<?php

namespace FormRenderSkipLogic\Migration;

use ExternalModules\ExternalModules;

class Migration {

    private $PREFIX;

    function __construct($prefix) {
        $this->PREFIX = $prefix;
    }

    /**
     * checks if FRSL settings for specified version exist. Does not support version
     * 1. Will return false for any invalid version.
     * @param string $version, module version in REDCap format e.g. 'v3.1.1'
     * @return boolean, true if they exist, false otherwise
     */
     function checkIfVersionSettingsExist($version) {
       $module_id = $this->getFRSLModuleId();

       $q = "SELECT 1 FROM redcap_external_module_settings WHERE external_module_id='$module_id' AND `key` IN ";

       if (preg_match("/v2\.[0-9]+(\.[0-9]+)?/", $version)) {
          $q .= "('control_field', 'event_name', 'event_name', 'field_name', 'enabled_before_ctrl_field_is_set', 'target_instruments', 'instrument_name', 'control_field_value')";
       } else if (preg_match("/v3\.[0-9]+(\.[0-9]+)?/", $version)) {
          $q .= "('control_fields', 'control_mode', 'control_event_id', 'control_field_key', 'control_piping', 'control_default_value', 'branching_logic', 'condition_operator', 'condition_value', 'target_forms', 'target_events_select', 'target_events')";
       } else {
         return false;
       }

       $result = ExternalModules::query($q);

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
       $result = ExternalModules::query($q);
       $id = $result->fetch_assoc()['external_module_id'];

       return $id;
     }

    /**
     * gets FRSLv2.x.x settings for each as an associative array indexed by
     * project_id, where each project setting is an associative array indexed by
     * setting name and maps to a setting value
     * @return array $settings
     */
     function getV2Settings() {
       $module_id = $this->getFRSLModuleId();

       //get old settings data
       $q = "SELECT project_id, `key`, value FROM redcap_external_module_settings WHERE external_module_id='$module_id' AND `key` IN ('control_field', 'event_name', 'event_name', 'field_name', 'enabled_before_ctrl_field_is_set', 'target_instruments', 'instrument_name', 'control_field_value')";
       $result = ExternalModules::query($q);

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
     function convertV2SettingsToV3Settings($old_settings) {
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

     /**
      * stores FRSL_v3.X created by convert2XSettingsTo3XSettings method into db
      * @param array $settings, array of v3 settings indexed by project_id and
      * maps to a array of setting keys pointing to their associated values.
      */
     function storeV3Settings($settings) {
       $module_id = $this->getFRSLModuleId();

       foreach ($settings as $project_id => $setting) {
         $q = "INSERT INTO redcap_external_module_settings (external_module_id, project_id, `key`, type, value) VALUES ";

         //build query
         $values_to_insert = [];
         foreach ($setting as $key => $value) {
           $values_to_insert[] = "($module_id, $project_id, '$key', 'json-array', '$value')";
         }

         $q .= join(",", $values_to_insert);
         ExternalModules::query($q);
       }
     }
}

 ?>
