<?php
//TODO: 
//use json_decode? or pass json to JS for more familiar parsing
//locate patient_type and pass it to the REDCap::getData function call
//use json_encode? to port REDCap::getData output to JS
//check for instruments of concern on returned json/JS object and enable their links
return function($project_id) {

    if ($project_id != NULL) {
    $SDH_type = REDCap::getData($project_id,'json',null,null,1,null,false,false,false,'[patient_type] = "1"',null,null);

    $SAH_type = REDCap::getData($project_id,'json',null,null,1,null,false,false,false,'[patient_type] = "2"',null,    null);

    $UBI_type = REDCap::getData($project_id,'json',null,null,1,null,false,false,false,'[patient_type] = "3"',null,    null);
    }
    ?>

    <script>
        var json = [{
            "action": "form_render_skip_logic",
            "instruments_to_show": [{
                    "logic": "[visit_1_arm_1][patient_type] = '1'",
                    "instrument_names": ["sdh_details", "past_medical_history_sah_sdh"]
                },
                {
                    "logic": "[visit_1_arm_1][patient_type] = '2'",
                    "instrument_names": ["sah_details", "past_medical_history_sah_sdh"]
                },
                {
                    "logic": "[visit_1_arm_1][patient_type] = '3'",
                    "instrument_names": ["medications_sah_sdh"]
                }
            ]
        }];

        var patient_types = {
            "sdh": {
                "type": <?php echo ($project_id != NULL) ? $SDH_type : "''";?>,
                "records": []
            },
            "sah": {
                "type": <?php echo ($project_id != NULL) ? $SAH_type : "''";?>,
                "records": []
            },
            "ubi": {
                "type": <?php echo ($project_id != NULL) ? $UBI_type : "''";?>,
                "records": []
            }
        };

        function pop_records(patient_types) {
            for (var patient_type in patient_types) {
                var patient_json = patient_types[patient_type];
                for (var record in patient_json["type"]) {
                    patient_json["records"].push(patient_json["type"][record].unique_id);
                }
            }
        }

        function disableUnionOfForms(json) {
            var instruments = json[0].instruments_to_show
            for (var names in instruments) {
                var forms = instruments[names].instrument_names
                for (var form in forms) {
                    var form_to_disable = forms[form];
                    disableFormsWithProp(form_to_disable);
                }
            }
        }

        function enableDesiredForms(json, patient_types) {
            var index = 0;
            for (var type in patient_types) {
                var record = patient_types[type].records;
                for (var rec in record) {
                    var instruments = json[0].instruments_to_show[index].instrument_names
                    for (var instrument in instruments) {
                        enableFormsForPatientId(record[rec], instruments[instrument]);
                    }
                }
                index++;
            }
        }

        function enableFormsForPatientId(id, form) {
            var rows = document.querySelectorAll('#record_status_table tbody tr');
            var reg = new RegExp(form);

            for (var i = 0; i < rows.length; i++) {
                if (rows[i].cells[0].innerText == id) {
                    for (var j = 0; j < rows[i].cells.length; j++) {
                        if (reg.test(rows[i].cells[j].firstElementChild.href)) {
                            enableForm(rows[i].cells[j]);
                            return;
                        }
                    }
                }
            }
        }

        function render_form_skip_logic(json) {

            // Check the current url to make sure you are on the record status dashboard page
            if (!/record_status_dashboard/.test(document.URL)) {
                console.log("render_form_skip_logic is not running!");
                return null;
            } else {
                console.log("it is running!");
            }

            //check if we recieved the right json
            if (!json[0].hasOwnProperty("action") && json[0]["action"] === "form_render_skip_logic") {
                console.log("render_form_skip_logic is not running due to a invalid json object");
                return null;
            }

            //check if we only want to hide a certain elements, defaults to hiding union of instruments to show
            if (json[0].hasOwnProperty("instruments_to_hide")) {
                for (var i = 0; i < json[0]["instruments_to_hide"].length; i++) {
                    disableFormsWithProp(json[0]["instruments_to_hide"][i]);
                }
            } else {
                disableUnionOfForms(json);
            }

            if (json[0].hasOwnProperty("instruments_to_show")) {
                enableDesiredForms(json, patient_types);
            }
        }

        function disableForm(cell) {
            cell.firstElementChild.style.pointerEvents = 'none';
            if (cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '.1';
            }
        }

        function enableForm(cell) {
            cell.firstElementChild.style.pointerEvents = 'auto';
            if (cell.firstElementChild.firstElementChild) {
                cell.firstElementChild.firstElementChild.style.opacity = '1';
            }
        }

        function disableFormsWithProp(property) {

            var rows = document.querySelectorAll('#record_status_table tbody tr');
            var reg = new RegExp(property);

            for (var i = 0; i < rows.length; i++) {
                for (var j = 0; j < rows[i].cells.length; j++) {
                    var link = rows[i].cells[j].firstElementChild.href;

                    if (reg.test(link)) {
                        disableForm(rows[i].cells[j]);
                    }
                }
            }
        }

        $('document').ready(function() {
            if ( <?php echo ($project_id != NULL) ? "true" : "false";?> ) {
                pop_records(patient_types);
                render_form_skip_logic(json);
            }
        });
    </script>
    <?php
}
?>
