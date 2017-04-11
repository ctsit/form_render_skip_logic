<?php
/*
 * When performing data entry for a new subject user can only see demographics
 * until the desired form, diagnosis, in demographics is filled. Afterwards user
 * can only certain forms based on the diagnosis they entered.
 *
 * TODO:
 * 1. write the hook
 * 	a. find current patient unique id <DONE>
 * 	a. see if a diagnosis has been selected for said patient <DONE>
 *	b. disable all forms except demographics until diagnosis is selected
 *	c. enable forms depending on the diagnosis one it has been selected
 *	d. do not screw up function of the drop down button
 * 2. test the hook
 * 3. factor out repeated code across all fsrl hooks into a common library
 */
return function($project_id) {

	$URL = $_SERVER['REQUEST_URI'];
	
	//check if we are on the right page
	if(preg_match('/record_home\.php\?.*&id=\d+/', $URL) == 1) {

		//get necesary information	
		$patient_id = $_GET["id"];
		$patient_data = REDcap::getData($project_id, 'json', $patient_id, "patient_type", 1, null, false, false, null, null, null);
		$instrument_names = json_encode(REDcap::getInstrumentNames());

	}else {
		//abort the hook
		echo "<script> console.log('aborting frsl record home page') </script>";
		return;
	}
?>

	<script>

		var json = [{"action ":"form_render_skip_logic",
			    "instruments_to_show" : [
				            {"logic":"[visit_1_arm_1][patient_type] = '1'",
					     "instrument_names": ["sdh_details", "past_medical_history_sah_sdh"]},
					    {"logic":"[visit_1_arm_1][patient_type] = '2'",
					      "instrument_names": ["sah_details", "past_medical_history_sah_sdh"]},
					    {"logic":"[visit_1_arm_1][patient_type] = '3'",
					     "instrument_names": ["medications_sah_sdh"]}
				       ]
	}];

		var instrumentNames = <?php echo $instrument_names ?>;
		var patient_data = <?php echo $patient_data ?>;
		var patient_type;

		//set patient type if it exists	
		if(patientTypeFound(patient_data)){
			patient_type = patient_data[0]['patient_type'];
		} else {
			patient_type = false;	
		}	

		//checks to see if a patient type has been selected for the current patient	
		function patientTypeFound(data) {
			if (data.length == 1 && data[0].hasOwnProperty("patient_type")) {
				return true;
			}
			return false;	
		}

		//resets the color of the rows after elements have been hidden
		function recolorRows(rows) {
			var even = false;
			for(i = 0; i < rows.length; i++) {
				var currentRow = $(rows[i]);
				if(!CurrentRow.is(":hidden")) {
					if(even && currentRow.hasClass("odd")) {
						currentRow.removeClass("odd");
						currentRow.addClass("even");
					} else if (!even && currentRow.hasClass("even")) {
						currentRow.removeClass("even")
						currentRow.addClass("odd");
					}
					even = !even;
				}
			}
		}

		function swapKeyValue(json) {
			var swappedJson = {};
			for(var key in json) {
				swappedJson[json[key]] = key;
			}
			return swappedJson;
		}

		function disableRows(rows, targets) {

		    for (var i = 0; i < rows.length; i++) {
			 var rowText = $(rows[i].cells[0]).text();
			 if (targets.indexOf(rowText) !== -1) {
				   hideRow(rows[i]);
			 }
		    }
		
		    recolorRows(rows);
		}


		function disableAllRows(rows) {
		    for (var i = 0; i < rows.length; i++) {
			    hideRow(rows[i]);
			}
		    }


		function enableRows(rows, targets) {
		    console.log(rows.length);
		    for (var i = 0; i < rows.length; i++) {
			var rowText = $(rows[i].cells[0]).text();

			if (targets.indexOf(rowText) !== -1) {
			    showRow(rows[i]);
			}
		    }

		    recolorRows(rows);
		}

				
		function showRow(row) {
		    $(row).show();
		}

		function hideRow(row) {
		    $(row).hide();
		}
			

		function frsl_record_home_page(json, instrumentNames, patientData, patientType) {
			var rows = $('.labelform').parent();
			$("table.dataTable.no-footer.DTFC_Cloned").hide();

			if(!patientTypeFound(patientData)) {
				disableAllRows(rows);
				enableRows(rows, ['Demographic Data (SAH & SDH)']);
				return;
			}	

			var instruments_to_show = json["instruments_to_show"];
			for(var i = 0; i < instruments_to_show.length; i++) {
				var logic = instruments_to_show[i]["logic"];
				
				//parse logic to find right patient type object

				
			}	

		}



		$('document').ready(function(){
			//printing for debugging purposes
			console.log('patient_type: ' + patient_type);	
			console.log("php unique id is: " + " <?php echo $patient_id ?>");
			console.log(<?php print("'$URL'") ?>);

			frsl_record_home_page(json, instrumentNames, patient_data, patient_type);
		});

			
	</script>
	<?php	
}
?>
