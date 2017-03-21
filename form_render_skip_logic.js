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

    //check if we recieved the right json
    if(!json[0].hasOwnProperty("action") && json[0]["action"] === "for_render_skip_logic") {
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
    var table = document.getElementById("record_status_table");
    
    for(var i = 2; i < table.rows.length;i++) {
        for(var j = 1; j < table.rows[i].cells.length; j++) {
            disableLink(table.rows[i].cells[j]);
        }
    }
} 

function disableLink(cell) {
    cell.firstElementChild.style.pointerEvents = 'none';
    cell.firstElementChild.firstElementChild.style.opacity = '.1';
}

function enableLink(cell) {
    cell.firstElementChild.style.pointerEvents = 'auto';
    cell.firstElementChild.firstElementChild.style.opacity = '1';
}
        


//disables all links with the given property inside their link name
function disableLinksWithProp(property) {

    var table = document.getElementById("record_status_table");
    var reg = new RegExp(property);

    for(var i = 2; i < table.rows.length;i++) {
        for(var j = 1; j < table.rows[i].cells.length; j++) {
            var link = table.rows[i].cells[j].firstElementChild.href;

            if(reg.test(link)) {
                disableLink(table.rows[i].cells[j]);
            }
        }
    }    
}


