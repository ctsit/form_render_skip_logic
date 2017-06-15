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

		// Read configuration data from redcap_custom_project_settings data store
		$my_extension_name = 'form_render_skip_logic';
		require_once "../../plugins/redcap_custom_project_settings/cps_lib.php";
		global $conn;
		$cps = new cps_lib($conn);
		$my_settings = $cps->getAttributeData($project_id, $my_extension_name);

		$project_json = json_decode($my_settings, true);

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
		return;
	}

    } else {
			//abort hook
			return;
    }
?>

  <script>

	//create frsl_dashboard object to avoid namespace collisions
	var frsl_dashboard = {};

	frsl_dashboard.json = <?php echo json_encode($project_json) ?>;
	frsl_dashboard.patient_data_structure = <?php echo $patient_data_structure ?>;
	frsl_dashboard.control_field_name = "<?php echo $field_name ?>";
	frsl_dashboard.control_field_value;
	frsl_dashboard.arm_name = "<?php echo $arm_name ?>";

	//given a json, return an array of the union of forms inside it
	frsl_dashboard.unionOfForms = function(json) {
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

	//disables every form in the union of every instruments_name array in json
	frsl_dashboard.disableUnionOfForms = function(json) {
		var union = this.unionOfForms(json);
		for (var form in union) {
			this.disableFormForEveryPatient(union[form]);
		}
	}

	//enables the appropriate forms for each patient
	frsl_dashboard.enableDesiredForms = function(json, patient_data_structure) {
		var instruments_to_show = json.instruments_to_show;
		for(var i = 0; i < instruments_to_show.length; i++) {
			var control_value = instruments_to_show[i]['control_field_value'];
			var instruments_to_enable = instruments_to_show[i]['instrument_names'];
			var patients = patient_data_structure[control_value];
			for(var j = 0; j < patients.length; j++) {
				for(var k = 0; k < instruments_to_enable.length; k++) {
					this.enableFormForPatient(patients[j], instruments_to_enable[k]);
				}
			}
		}
	}

	frsl_dashboard.enableFormForPatient = function(patient, form) {
		var rows = document.querySelectorAll('#record_status_table tbody tr');
		var reg = new RegExp('id=' + patient['unique_id'] + '&page=' + form);

		for (var i = 0; i < rows.length; i++) {
			for (var j = 0; j < rows[i].cells.length; j++) {
				if (reg.test(rows[i].cells[j].firstElementChild.href)) {
					this.enableForm(rows[i].cells[j]);
				}
			}
		}
	}

	frsl_dashboard.form_render_skip_logic = function(json, patient_data_structure) {
		this.disableUnionOfForms(json);
		this.enableDesiredForms(json, patient_data_structure);
	}

	frsl_dashboard.disableForm = function(cell) {
	    cell.style.pointerEvents = 'none';
	    cell.style.opacity = '.1';
	}

	frsl_dashboard.enableForm = function(cell) {
	    cell.style.pointerEvents = 'auto';
	    cell.style.opacity = '1';
	}

	frsl_dashboard.disableFormForEveryPatient = function(form) {
		var rows = document.querySelectorAll('#record_status_table tbody tr');
		var reg = new RegExp('&page=' + form);

		for (var i = 0; i < rows.length; i++) {
			for (var j = 0; j < rows[i].cells.length; j++) {
				var link = rows[i].cells[j].firstElementChild.href;

				if (reg.test(link)) {
					this.disableForm(rows[i].cells[j]);
				}
			}
		}
	}

	$('document').ready(function() {
		frsl_dashboard.form_render_skip_logic(frsl_dashboard.json, frsl_dashboard.patient_data_structure);
	});
  </script>
<?php
}
?>
