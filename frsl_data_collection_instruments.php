<?php
/*
 *Changes the left-hand menu of data collection instruments to only show
 *instruments appropriate to diagnosis
 *
 */

return function($project_id) {

    $URL = $_SERVER['REQUEST_URI'];
    if(preg_match('/DataEntry\/index.php/', $URL) == 1) {

        //get necesary information
        $patient_id = $_GET["id"];

        // Read configuration data from redcap_custom_project_settings data store
        $my_extension_name = 'form_render_skip_logic';
        require_once "../../plugins/custom_project_settings/cps_lib.php";
        $cps = new cps_lib();
        $my_settings = $cps->getAttributeData($project_id, $my_extension_name);

        $project_json = json_decode($my_settings, true);

        $arm_name = $project_json['control_field']['arm_name'];
        $field_name = $project_json['control_field']['field_name'];
        $control_field_value = REDCap::getData($project_id, 'json', $patient_id, $field_name, $arm_name, null, false, false, null, null, null);
    } else {
        //abort the hook
        echo "<script> console.log('aborting frsl data collection instruments page') </script>";
        return;
    }
?>
    <script>

    //create frsl_data_collection_instruments object to avoid namespace collisions
		var frsl_data_collection_instruments = {};

    frsl_data_collection_instruments.json = <?php echo json_encode($project_json) ?>;
    frsl_data_collection_instruments.control_field_value = <?php echo $control_field_value ?>;
    frsl_data_collection_instruments.control_field_name = "<?php echo $field_name ?>";
    frsl_data_collection_instruments.control_value;

    frsl_data_collection_instruments.unionOfForms = function(json) {
        var instruments = this.json.instruments_to_show;
        var union = [];
        for (var names in instruments) {
            var forms = instruments[names].instrument_names;
            for (var form in forms) {
                var form_name = forms[form];
                if (union.indexOf(form_name) === -1) {
                  union.push(form_name);
                }
            }
        }
        return union;
    }


    //checks to see if a control value has been set for the subject record
    frsl_data_collection_instruments.controlValueFound = function(data) {
        if (data.length == 1 && data[0].hasOwnProperty(this.control_field_name)) {
            return true;
        }
        return false;
    }

    frsl_data_collection_instruments.get_instrument_names_object = function(control_value, json) {
        for(var i = 0; i < this.json.instruments_to_show.length; i++)
        {
          if(this.json.instruments_to_show[i].control_field_value == control_value)
          {
            return this.json.instruments_to_show[i].instrument_names;
          }
        }
    }

    frsl_data_collection_instruments.enable_desired_forms = function() {
        var instruments = this.get_instrument_names_object(this.control_value, this.json)

        // json.instruments_to_show[index].instrument_names
        for (var instrument in instruments) {
            this.enable_required_forms(instruments[instrument]);
        }
    }

    frsl_data_collection_instruments.disable_all_forms = function() {
      var arr = document.getElementsByClassName('formMenuList');
      var formsToDisable = this.unionOfForms(this.json);
      for(var i=0;i<arr.length;i++){
        var str = arr[i].getElementsByTagName('a')[1].getAttribute('id').match(/\[(.*?)\]/)[1];
        if(formsToDisable.indexOf(str) !== -1) {
          document.getElementsByClassName('formMenuList')[i].style.display = 'none';
        }
      }
    }

    frsl_data_collection_instruments.enable_required_forms = function(form){
    	var arr = document.getElementsByClassName('formMenuList')
    	for(var i=0; i<arr.length;i++){
    		var str = arr[i].getElementsByTagName('a')[1].getAttribute('id').match(/\[(.*?)\]/)[1];
    		if(str == form)
    			arr[i].style.display = 'block';
    	}
    }

    $('document').ready(function() {
        //set control value if it exists
        if(frsl_data_collection_instruments.controlValueFound(frsl_data_collection_instruments.control_field_value)){
            frsl_data_collection_instruments.control_value = frsl_data_collection_instruments.control_field_value[0][frsl_data_collection_instruments.control_field_name];
        } else {
            frsl_data_collection_instruments.control_value = false;
        }

        frsl_data_collection_instruments.disable_all_forms();
        frsl_data_collection_instruments.enable_desired_forms();

        });

    // Remap SaveNextForm to SaveContinue
    $(window).load(function()  {
      $('a[onclick="dataEntrySubmit(\'submit-btn-savenextform\');return false;"]').text('Save & Stay');
      $('a[onclick="dataEntrySubmit(\'submit-btn-savenextform\');return false;"]').attr('onClick','dataEntrySubmit(\'submit-btn-savecontinue\');return false;');
    });


    </script>
    <?php

}
?>
