<?php
    $stated_pt = file_get_contents('/var/www/redcap/hooks/sample.json');
    //print $stated_pt;
//TODO: 
//use json_decode? or pass json to JS for more familiar parsing
//locate patient_type and pass it to the REDCap::getData function call
//use json_encode? to port REDCap::getData output to JS
//check for instruments of concern on returned json/JS object and enable their links
return function() {
    print "<script>
    console.log('hello');</script>";
print '<script>
    $(\'document\').ready(function(){
        var json = [
          { "action":"form_render_skip_logic",
            "instruments_to_show" : [
                {"logic":"[visit_1_arm_1][patient_type] = \'1\'",
                 "instrument_names": ["sdh_details", "past_medical_history_sah_sdh"]},
                {"logic":"[visit_1_arm_1][patient_type] = \'2\'",
                 "instrument_names": ["sah_details", "past_medical_history_sah_sdh"]},
                {"logic":"[visit_1_arm_1][patient_type] = \'3\'",
                 "instrument_names": ["medications_sah_sdh"]}
                ]
            }
        ];

        render_form_skip_logic(json);

        function render_form_skip_logic(json) {

            // Check the current url to make sure you are on the record status dashboard page
            if(!/record_status_dashboard/.test(document.URL)) {
                console.log("render_form_skip_logic is not running!");
                return null;
            }
            else {
            console.log("it is running!");
            }

            //check if we recieved the right json
            if(!json[0].hasOwnProperty("action") && json[0]["action"] === "form_render_skip_logic") {
                console.log("render_form_skip_logic is not running due to a json error");
                return null;
            }
            
            //check if we only want to hide a certain elements, defaults to hiding everything
            if(json[0].hasOwnProperty("instruments_to_hide")) {
               for(var i = 0; i < json[0]["instruments_to_hide"].length; i++) {
                    disableLinksWithProp(json[0]["instruments_to_hide"][i]);
               } 
            } else {
                disableAllLinks();
            }
            
            if(json[0].hasOwnProperty("instruments_to_show")) {
                for(var i = 0; i < json[0]["instruments_to_show"];i++) {
                    //go through each cell
                    //if right visit_and_arm and right patient_type
                    //then enable all instruments in instruments_to_show
                }
            }
        }

        //disables all links within the record table.
        function disableAllLinks() {
            var rows = document.querySelectorAll(\'#record_status_table tbody tr\');
            
            for(var i = 0; i < rows.length;i++) {
                for(var j = 0; j < rows[i].cells.length; j++) {
                    disableLink(rows[i].cells[j]);
                }
            }
        } 

        function disableLink(cell) {
            cell.firstElementChild.style.pointerEvents = \'none\';
            if(cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = \'.1\';
            }
        }

        function enableLink(cell) {
            cell.firstElementChild.style.pointerEvents = \'auto\';
            if(cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = \'1\';
            }
        }
                


        //disables all links with the given property inside their link name
        function disableLinksWithProp(property) {

            var rows = document.querySelectorAll(\'#record_status_table tbody tr\');
            var reg = new RegExp(property);

            for(var i = 0; i < rows.length;i++) {
                for(var j = 0; j < rows[i].cells.length; j++) {
                    var link = table.rows[i].cells[j].firstElementChild.href;

                    if(reg.test(link)) {
                        disableLink(rows[i].cells[j]);
                    }
                }
            }    
        }
}
);
</script>';
}
?>


