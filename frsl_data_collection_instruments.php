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
	if(preg_match('/event_id=40/',$URL) == 1){
		$patient_id = $_GET["id"];

		$SDH_type = REDCap::getData($project_id,'json',null,null,1,null,false,false,false,'[patient_type] = "1"',null,null);

    	$SAH_type = REDCap::getData($project_id,'json',null,null,1,null,false,false,false,'[patient_type] = "2"',null,null);

    	$UBI_type = REDCap::getData($project_id,'json',null,null,1,null,false,false,false,'[patient_type] = "3"',null,null);

    	$patient_data = REDcap::getData($project_id, 'json', "$patient_id", "patient_type", 1, null,false, false, false, null, null, null);
	}
	else{
		return;
	}
?>
    <script>

    var patient_data = <?php echo $patient_data ?>;
    var patient_type = patient_data[0].patient_type;

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