<?php
//TODO: 
//use json_decode? or pass json to JS for more familiar parsing
//locate patient_type and pass it to the REDCap::getData function call
//use json_encode? to port REDCap::getData output to JS
//check for instruments of concern on returned json/JS object and enable their links
return function() {
    $SDH_type = REDCap::getData(13,'json',null,null,1,null,false,false,false,'[patient_type] = "1"',null,null);
    //print $SDH_type;
    $SAH_type = REDCap::getData(13,'json',null,null,1,null,false,false,false,'[patient_type] = "2"',null,    null);
    $UBI_type = REDCap::getData(13,'json',null,null,1,null,false,false,false,'[patient_type] = "3"',null,    null);
    ?>
    <script>
    console.log("hello");
    var patient_types = {
        "sdh" : {"type": <?php echo $SDH_type ?>, "records": []},
        "sah" : {"type": <?php echo $SAH_type ?>, "records": []},
        "ubi" : {"type": <?php echo $UBI_type ?>, "records": []}
    };
    function pop_records(patient_types) {
        for (var patient_type in patient_types) {
            //console.log(Object.values(type));
            var patient_json = patient_types[patient_type];
            //var record = patient_json[type];
            for (var record in patient_json["type"]) {
               patient_json["records"].push(patient_json["type"][record].unique_id); 
            }
            console.log(patient_json.records);
        }
    }
    pop_records(patient_types);
    $('document').ready(function(){
        var json = [
          { "action":"form_render_skip_logic",
            "instruments_to_show" : [
                {"logic":"[visit_1_arm_1][patient_type] = '1'",
                 "instrument_names": ["sdh_details", "past_medical_history_sah_sdh"]},
                {"logic":"[visit_1_arm_1][patient_type] = '2'",
                 "instrument_names": ["sah_details", "past_medical_history_sah_sdh"]},
                {"logic":"[visit_1_arm_1][patient_type] = '3'",
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
                for(var index in json[0]["instruments_to_show"]) {
                    var pair = json[0]["instruments_to_show"][index];
                    if(pair.logic){
                            console.log(pair.instrument_names);
                        for(var instrument in pair.instrument_names) {
                            console.log('enableing ' + pair.instrument_names[instrument]);
                            enableLinksWithProp(pair.instrument_names[instrument]);
                        }
                    } 
                    //go through each cell
                    //if right visit_and_arm and right patient_type
                    //then enable all instruments in instruments_to_show
                }
            }
        }

        //disables all links within the record table.
        function disableAllLinks() {
            var rows = document.querySelectorAll('#record_status_table tbody tr');
            
            for(var i = 0; i < rows.length;i++) {
                for(var j = 0; j < rows[i].cells.length; j++) {
                    disableLink(rows[i].cells[j]);
                }
            }
        } 

        function disableLink(cell) {
            cell.firstElementChild.style.pointerEvents = 'none';
            if(cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '.1';
            }
        }

        function enableLink(cell) {
            cell.firstElementChild.style.pointerEvents = 'auto';
            if(cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '1';
            }
        }
                


        //enables all links with the given property inside their link name
        function enableLinksWithProp(property) {

            var rows = document.querySelectorAll('#record_status_table tbody tr');
            var reg = new RegExp(property);

            for(var i = 0; i < rows.length;i++) {
                for(var j = 0; j < rows[i].cells.length; j++) {
                    var link = rows[i].cells[j].firstElementChild.href;

                    if(reg.test(link)) {
                        enableLink(rows[i].cells[j]);
                    }
                }
            }    
        }
    
    
        //disables all links with the given property inside their link name
        function disableLinksWithProp(property) {

            var rows = document.querySelectorAll('#record_status_table tbody tr');
            var reg = new RegExp(property);

            for(var i = 0; i < rows.length;i++) {
                for(var j = 0; j < rows[i].cells.length; j++) {
                    var link = rows[i].cells[j].firstElementChild.href;

                    if(reg.test(link)) {
                        disableLink(rows[i].cells[j]);
                    }
                }
            }    
        }
    }
);
</script>
<?php
}
?>


