<?php
/*
 * Takes a json file and disables/enables certain forms for certain patients on
 * record_status_dashboard based on the given json file.
 *
 */
return function($project_id) {

    $URL = $_SERVER['REQUEST_URI'];

    //check if we are on the right page
    if(preg_match('/DataEntry\/record_status_dashboard/', $URL) == 1) {
        //get necesary information
        $patient_id = $_GET["id"];
        $project_json = json_decode('{
                           "control_field":{
                              "arm_name":"baseline_arm_1",
                              "field_name":"patient_type"
                           },
                           "instruments_to_show":[
                              {
                             "control_field_value":"1",
                             "instrument_names":[
                                "sdh_details",
                                "radiology_sdh",
                                "surgical_data",
                                "moca",
                                "gose",
                                "telephone_interview_of_cognitive_status"
                             ]
                              },
                              {
                             "control_field_value":"2",
                             "instrument_names":[
                                "sah_details",
                                "radiology_sah",
                                "delayed_neurologic_deterioration",
                                "ventriculostomysurgical_data",
                                "moca",
                                "gose",
                                "telephone_interview_of_cognitive_status"
                             ]
                              },
                              {
                             "control_field_value":"3",
                             "instrument_names":[
                                "sdh_details",
                                "sah_details"
                             ]
                              }
                           ]
                        }'
                        , true);

        $arm_name = $project_json['control_field']['arm_name'];
        $field_name = $project_json['control_field']['field_name'];

	$patient_data_structure = '{ ';

	for($i = 0; $i < count($project_json['instruments_to_show']); $i++) {
		$control_field_value = $project_json['instruments_to_show'][$i]['control_field_value'];

		if($i != 0) {
			$patient_data_structure .= ',';
		}

		$patient_data_structure .= '"' . $control_field_value . '":';
		$control_field_value_patients = REDCap::getData($project_id,'json',null,'unique_id',$arm_name,null,false,false,false,'[' . $field_name . '] = "' . $control_field_value . '"',false,null);
		$patient_data_structure .=  $control_field_value_patients;

	}

	$patient_data_structure .= '}';

	// Check if project is longitdudinal
	if (!REDCap::isLongitudinal()) {
		print('<script> console.log("frsl_dashboard could not run because the project is not longitudinal") </script>');
		return;
	}

    }else {
        echo "<script> console.log('aborting frsl dashboard home page') </script>";
        return;
    }
?>

    <script>
        var json = <?php echo json_encode($project_json) ?>;
	var patient_data_structure = <?php echo $patient_data_structure ?>;
        var control_field_name = "<?php echo $field_name ?>";
        var control_field_value;
	var arm_name = "<?php echo $arm_name ?>";

	function unionOfForms(json) {
		var instruments = json.instruments_to_show;
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



	function disableUnionOfForms(json) {
		var union = unionOfForms(json);
		for (var form in union) {
			disableFormForEveryPatient(union[form]);
		}
	}

        function enableDesiredForms(json, patient_data_structure) {
		var instruments_to_show = json.instruments_to_show;
		for(var i = 0; i < instruments_to_show.length; i++) {
			var control_value = instruments_to_show[i]['control_field_value'];
			var instruments_to_enable = instruments_to_show[i]['instrument_names'];
			var patients = patient_data_structure[control_value];
			for(var j = 0; j < patients.length; j++) {
				for(var k = 0; k < instruments_to_enable.length; k++) {
					enableFormForPatient(patients[j], instruments_to_enable[k]);
				}
			}
		}
	}

        function enableFormForPatient(patient, form) {
		var rows = document.querySelectorAll('#record_status_table tbody tr');
		var reg = new RegExp('id=' + patient['unique_id'] + '&page=' + form);

		for (var i = 0; i < rows.length; i++) {
			for (var j = 0; j < rows[i].cells.length; j++) {
				if (reg.test(rows[i].cells[j].firstElementChild.href)) {
					enableForm(rows[i].cells[j]);
				}
			}
		}
	}

        function form_render_skip_logic(json, patient_data_structure) {
		disableUnionOfForms(json);
		enableDesiredForms(json, patient_data_structure);
	}

        function disableForm(cell) {
            cell.firstElementChild.style.pointerEvents = 'none';
            if (cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '.1';
            }
        }

        function enableForm(cell) {
            cell.firstElementChild.style.pointerEvents = 'auto';
            if (cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '1';
            }
        }

	function disableFormForEveryPatient(form) {
		var rows = document.querySelectorAll('#record_status_table tbody tr');
		var reg = new RegExp('&page=' + form);

		for (var i = 0; i < rows.length; i++) {
			for (var j = 0; j < rows[i].cells.length; j++) {
				var link = rows[i].cells[j].firstElementChild.href;

				if (reg.test(link)) {
					disableForm(rows[i].cells[j]);
				}
			}
		}
	}

        $('document').ready(function() {
                form_render_skip_logic(json, patient_data_structure);
        });
    </script>
    <?php
}
?>
