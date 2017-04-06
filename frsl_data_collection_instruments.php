<?php
/*
 *Changes the left-hand menu of data collection instruments to only show
 *instruments appropriate to diagnosis
 *
 * TODO:
 * 1. write frsl_data_collection_instruments
 * 	a. find out patient diagnosis
 * 	b. find out which instruments are appropriate to show
 * 	c. find out how to manipulate left-hand menu to show only desired
 * 	   insturments
 * 2. test frsl_data_collection_instruments
 * 3. factor out repeated code across all fsrl hooks into a common library
 */

return function($project_id) {

	$patient_id = $_GET["id"];
	$patient_data = REDcap::getData($project_id, 'json', $patient_id, "patient_type", 1, null, false, false, null, null, null);

    <script>

    
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

    function enableDesiredForms(json, type) {
        var index = 0;
        var record = patient_types[type].records;
        for (var rec in record) {
            var instruments = json[0].instruments_to_show[index].instrument_names
            for (var instrument in instruments) {
                enable_required_forms(instruments[instrument]);
            }
        }
        index++; 
    }

    function disable_all_forms(){
    	var arr = document.getElementsByClassName('formMenuList')
    	for(var i=0;i<arr.length;i++){
			document.getElementsByClassName('formMenuList')[i].style.visibility = 'hidden';
		}
    }

    function enable_required_forms(form){
    	var arr = document.getElementsByClassName('formMenuList')
    	for(var i=0; i<arr.length;i++){
    		var str = arr[i].getElementsByTagName('a')[1].getAttribute('id');
    		if(str == form)
    			arr[i].style.visibility = 'visible';
    	}
    }


    </script>

}
?>
