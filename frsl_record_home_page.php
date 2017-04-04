<?php
/*
 * When performing data entry for a new subject user can only see demographics
 * until the desired form, diagnosis, in demographics is filled. Afterwards user
 * can only certain forms based on the diagnosis they entered.
 *
 * TODO:
 * 1. write the hook
 * 	a. see if a diagnosis has been selected
 *	b. disable all forms except demographics until diagnosis is selected
 *	c. enable forms depending on the diagnosis one it has been selected
 * 2. test the hook
 * 3. factor out repeated code across all fsrl hooks into a common library
 */
return function($project_id) {
	
}
?>
