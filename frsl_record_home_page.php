<?php
/*
 * When performing data entry for a new subject user can only see demographics
 * until the desired form, diagnosis, in demographics is filled. Afterwards user
 * can only certain forms based on the diagnosis they entered.
 *
 * TODO:
 * 1. write the hook
 * 	a. find current patient unique id
 * 	a. see if a diagnosis has been selected for said patient
 *	b. disable all forms except demographics until diagnosis is selected
 *	c. enable forms depending on the diagnosis one it has been selected
 * 2. test the hook
 * 3. factor out repeated code across all fsrl hooks into a common library
 */
return function($project_id) {
	?>
	<script>
		function getCurrentPatientId(){
			var id;
			var url = document.URL;
			
			//search url for "&id={some number}" query string
			var idString = /&id=\d+/g.exec(url);
			if(idString !== null && idString.length === 1) {
				idString = idString[0];
			}else {
				//could not find or found too many ids
				return null;
			}
			
			//extract patient id number from idString
			id = /\d+/g.exec(idString)[0];

			return id;
		}

		//printing patient id for debugging purposes
		console.log("current unique patient id: " + getCurrentPatientId());
	</script>
	<?php	
}
?>
