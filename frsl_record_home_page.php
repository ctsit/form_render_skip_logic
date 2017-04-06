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
 * 2. test the hook
 * 3. factor out repeated code across all fsrl hooks into a common library
 */
return function($project_id) {

	$patient_id = $_GET["id"];
	$patient_data = REDcap::getData($project_id, 'json', $patient_id, "patient_type", 1, null, false, false, null, null, null);

	?>
	<script>
		var patient_data = <?php echo $patient_data ?>;
		var patient_type;
		var enableForms = false;

		//printing patient id for debugging purposes
		console.log("php unique id is: " + " <?php echo $patient_id ?>");
		console.log(patient_data);

		if(patient_data.length > 1) {
			console.log("cannot find patient type, too much data returned");
		} else if(patient_data.length === 0) {
			console.log("patient_type is not yet defined");
		} else {
			console.log("patient type is defined and is " + patient_data[0]["patient_type"]);
			patient_type = patient_data[0];
			enableForms = true;
		}



	</script>
	<?php	
}
?>
