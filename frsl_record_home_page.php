<?php
/*
 * When performing data entry for a new subject user can only see demographics
 * until the desired form, diagnosis, in demographics is filled. Afterwards user
 * can only certain forms based on the diagnosis they entered.
 *
 */

return function($project_id) {

	$URL = $_SERVER['REQUEST_URI'];

	//check if we are on the right page
	if(preg_match('/record_home\.php\?.*&id=\w+/', $URL) == 1) {
		//get necesary information
		$patient_id = $_GET["id"];
		
		// Read configuration data from redcap_custom_project_settings data store
		$my_extension_name = 'form_render_skip_logic';
		require_once "../../plugins/redcap_custom_project_settings/cps_lib.php";
		$cps = new cps_lib();
		$my_settings = $cps->getAttributeData($project_id, $my_extension_name);

		$project_json = json_decode($my_settings, true);

		$arm_name = $project_json['control_field']['arm_name'];
		$field_name = $project_json['control_field']['field_name'];
		$patient_data = REDCap::getData($project_id, 'json', $patient_id, $field_name, $arm_name, null, false, false, null, null, null);
		$instrument_names = json_encode(REDCap::getInstrumentNames());
	}else {
		//abort the hook
		return;
	}
?>

	<script>
		//create frsl_record_home_page object to avoid namespace collisions
		var frsl_record_home_page = {};

		frsl_record_home_page.json = <?php echo json_encode($project_json) ?>;
		frsl_record_home_page.instrumentNames = <?php echo $instrument_names ?>;
		frsl_record_home_page.patient_data = <?php echo $patient_data ?>;
		frsl_record_home_page.control_field_name = "<?php echo $field_name ?>";
		frsl_record_home_page.control_field_value;

		//checks to see if a patient type has been selected for the current patient
		frsl_record_home_page.ControlFieldValueIsSet = function(data) {
			if (data.length == 1 && data[0].hasOwnProperty(this.control_field_name)) {
				return true;
			}
			return false;
		}

		//resets the color of the rows after elements have been hidden
		frsl_record_home_page.recolorRows = function() {
			var rows = $('.labelform').parent();
			var even = false;
			for(i = 0; i < rows.length; i++) {
				var currentRow = $(rows[i]);
				if(currentRow.is(":visible")) {
					if(even && currentRow.hasClass("odd")) {
						currentRow.attr('style', 'background-color: #eeeeee !important');
					} else if (!even && currentRow.hasClass("even")) {
						currentRow.attr('style', 'background-color: #fcfef5 !important');
					}
					even = !even;
				}
			}
		}

		//disables all rows in rows that have row headers that are a member of targets
		frsl_record_home_page.disableRows = function(rows, targets) {

		    for (var i = 0; i < rows.length; i++) {
					var rowText = $(rows[i].cells[0]).text();
					if (targets.indexOf(rowText) !== -1) {
						this.hideRow(rows[i]);
					}
		    }
		}

		//enables all rows in rows that have row headers that are a member of targets
		frsl_record_home_page.enableRows = function(rows, targets) {
		    for (var i = 0; i < rows.length; i++) {
					var rowText = $(rows[i].cells[0]).text();

					if (targets.indexOf(rowText) !== -1) {
					    this.showRow(rows[i]);
					}
		    }
		}

		//given a row, it displayes the row on the page
		frsl_record_home_page.showRow = function(row) {
		    $(row).show();
		}

		//given a row, it displayes the row on the page
		frsl_record_home_page.hideRow = function(row) {
		    $(row).hide();
		}

		//given an array of instrument names, return an array of their corresponding labels in the same order
		frsl_record_home_page.convertNamesToLabels = function(instrumentNames) {
			var conversionTable = <?php echo $instrument_names ?>;
			var output = [];

			for(var i = 0; i < instrumentNames.length; i++) {
				output.push(conversionTable[instrumentNames[i]]);
			}

			return output;
		}


		frsl_record_home_page.frsl_record_home_page = function(json, control_field_value) {
			var rows = $('.labelform').parent();

			//disable the table layered on top table we want to modify
			$("table.dataTable.no-footer.DTFC_Cloned").hide();

			var instruments_to_show = this.json["instruments_to_show"];

			//disable union of all instruments in instruments to show
			for(var i = 0; i < instruments_to_show.length; i++) {
				var instrumentNames = instruments_to_show[i]["instrument_names"];
				var instrumentLabels = this.convertNamesToLabels(instrumentNames);
				this.disableRows(rows, instrumentLabels);
			}

			if(control_field_value === false) {
				this.recolorRows(rows);
				return;
			}

			//parse logic and show only the desired instruments
			for(var i = 0; i < instruments_to_show.length; i++) {
				var value = instruments_to_show[i]["control_field_value"];
				var instrumentNames = instruments_to_show[i]["instrument_names"];
				var instrumentLabels = this.convertNamesToLabels(instrumentNames);

				if(value == control_field_value) {
					this.enableRows(rows, instrumentLabels);
				}
			}

			this.recolorRows(rows);
		}

		$(document).ready(function(){

			//set patient type if it exists
			if(frsl_record_home_page.ControlFieldValueIsSet(frsl_record_home_page.patient_data)){
				frsl_record_home_page.control_field_value = frsl_record_home_page.patient_data[0][frsl_record_home_page.control_field_name];
			} else {
				frsl_record_home_page.control_field_value = false;
			}

			frsl_record_home_page.frsl_record_home_page(frsl_record_home_page.json, frsl_record_home_page.control_field_value);
		});

		//disable collapse table button since it undoes any changes this hook makes
		$(window).load(function(){
			$('button[title="Collapse/uncollapse table"]').hide();
		});


	</script>
	<?php
}
?>
