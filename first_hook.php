<?php
/**
 *  * MAKE ALL SURVEY PAGES HAVE A RED BACKGROUND - easy verification/test
 *	* TODO
 *	* Replace $pt with a parameter used in the function call (from the json that determines what fields to show)
 *   */
return function()
{
    ?><style type="text/css">body { background: yellow; }</style><?php
    // Declare patient type, 1 = SDH, 2 = SAH, 3 = UBI
    $pt = 1;
    // This will select all patients of the SDH type (first option, as indicated by '[patient_type]= "1"')
    $data = REDCap::getData(13,'json',null,null,1,null,false,false,false,'[patient_type] = "$pt"',null,null);
    /*$myfile = fopen("first.json", "c") or die("Cannot open file!");
    file_put_contents($myfile, "START");
    file_put_contents($myfile,$data);
    file_put_contents($myfile, "FINISH");
    fclose($myfile);*/
    print $data;
}

?>