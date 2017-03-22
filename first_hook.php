<?php
/**
 *  * MAKE ALL SURVEY PAGES HAVE A RED BACKGROUND
 *   */
return function()
{
    ?><style type="text/css">body { background: yellow; }</style><?php
    // This will select all patients of the SDH type (first option, as indicated by '[patient_type]= "1"')
    $data = REDCap::getData(13,'json',null,null,1,null,false,false,false,'[patient_type] = "1"',null,null);
    /*$myfile = fopen("first.json", "c") or die("Cannot open file!");
    file_put_contents($myfile, "START");
    file_put_contents($myfile,$data);
    file_put_contents($myfile, "FINISH");
    fclose($myfile);*/
    print $data;
}

?>