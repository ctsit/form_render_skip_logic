<?php
/*
 *Changes the left-hand menu of data collection instruments to only show
 *instruments appropriate to diagnosis
 *
 * TODO:
 *
 * 1. factor out repeated code across all fsrl hooks into a common library
 * 2. Information retrieval of patient data fails without calling redcap getdata for SDH_TYPE,
 *    SAH_TYPE and UBI_TYPE
 */

return function($project_id) {

    $URL = $_SERVER['REQUEST_URI'];
    if(preg_match('/DataEntry/', $URL) == 1) {

        //get necesary information
        $patient_id = $_GET["id"];
        $project_json = json_decode('{
                                         "control_field":{
                                            "arm_name":"visit_1_arm_1",
                                            "field_name":"patient_type"
                                         },
                                         "instruments_to_show":[
                                            {
                                               "control_field_value":"1",
                                               "instrument_names":[
                                                  "sdh_details",
                                                  "past_medical_history_sah_sdh"
                                               ]
                                            },
                                            {
                                               "control_field_value":"2",
                                               "instrument_names":[
                                                  "sah_details",
                                                  "past_medical_history_sah_sdh"
                                               ]
                                            },
                                            {
                                               "control_field_value":"3",
                                               "instrument_names":[
                                                  "medications_sah_sdh"
                                               ]
                                            }
                                         ]
                                      }'
                                      , true);

        $arm_name = $project_json['control_field']['arm_name'];
        $field_name = $project_json['control_field']['field_name'];
        $patient_type = REDCap::getData($project_id, 'json', $patient_id, $field_name, $arm_name, null, false, false, null, null, null);
        print($patient_type);
    } else {
        //abort the hook
        echo "<script> console.log('aborting frsl record home page') </script>";
        return;
    }
?>
    <script>

    var patient_type_json = <?php echo $patient_type ?>;
    var patient_type = patient_type_json[0].patient_type;

    var str;
    if(patient_type == 1){
    	str = "sdh";
    }
    if(patient_type == 2){
    	str = "sah";
    }
    if(patient_type == 3){
    	str = "ubi"
    }
    var json = [{
            "action": "form_render_skip_logic",
            "instruments_to_show": [{
                    "logic": "[visit_1_arm_1][patient_type] = '1'",
                    "instrument_names": ["sdh_details", "past_medical_history_sah_sdh"]
                },
                {
                    "logic": "[visit_1_arm_1][patient_type] = '2'",
                    "instrument_names": ["sah_details", "past_medical_history_sah_sdh"]
                },
                {
                    "logic": "[visit_1_arm_1][patient_type] = '3'",
                    "instrument_names": ["medications_sah_sdh"]
                }
            ]
        }];

    function enable_desired_forms(type) {
        var index = patient_type - 1;
        var instruments = json[0].instruments_to_show[index].instrument_names
        for (var instrument in instruments) {
            enable_required_forms(instruments[instrument]);
        }
    }

    function disable_all_forms(){
    	var arr = document.getElementsByClassName('formMenuList')
    	for(var i=0;i<arr.length;i++){
			document.getElementsByClassName('formMenuList')[i].style.display = 'none';
		}
    }

    function enable_required_forms(form){

    	var arr = document.getElementsByClassName('formMenuList')
    	for(var i=0; i<arr.length;i++){
    		var str = arr[i].getElementsByTagName('a')[1].getAttribute('id').match(/\[(.*?)\]/)[1];
    		if(str == form)
    			arr[i].style.display = 'block';
    	}
    }

    $('document').ready(function() {
    		disable_all_forms();
    		enable_desired_forms(str);

        });

    </script>
    <?php

}
?>