# REDCap Form Render Skip Logic Hooks (FRSL)

This is a set of REDCap hooks designed to hide and show instruments based on the value of a single field on a single form.  The original use case of these tools was to facilitate a data entry workflow specific to acute brain injury diagnoses, but the tools is generalized to support the hiding (and showing) of any number of forms based on a field value on one form.

![venn diagram of test project forms](venn_diagram_of_test_project_forms.png)

See the functional specification at [https://docs.google.com/document/d/1Ej7vCNpKOrC6X9KVpkZkHeY0v2VqQXrjuMIBQtbj1bw/edit#](https://docs.google.com/document/d/1Ej7vCNpKOrC6X9KVpkZkHeY0v2VqQXrjuMIBQtbj1bw/edit#) for functional details.

## Testing

As shipped, the hooks are unconfigured.  You will need to set configuration data as described in the section [Customizing the FRSL hooks](#customizing).  That section provides configuration data that works with the [test_project.xml](test_project.xml). You can view the normal operation of the hooks by building a test project from this file and using the unmodified hooks.  The project includes 4 subject records. Two records have a diagnosis of SAH while the other two have a diagnosis of _SDH_. When all three hooks are installed and activated on this project, the 4 subjects will show two different sets of accessible forms based on the diagnosis.

## Activating FRSL Hooks

If you are deploying these hooks using UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can activate these hooks with those tools as well.  If you had an environment named `vagrant` the activation would look like this:

    MY_PID=123
    fab instance:vagrant activate_hook:redcap_every_page_top,frsl_dashboard,$MY_PID
    fab instance:vagrant activate_hook:redcap_every_page_top,frsl_record_home_page,$MY_PID
    fab instance:vagrant activate_hook:redcap_every_page_top,frsl_data_collection_instruments,$MY_PID


## Deploying the FRSL hooks in other environments

These hooks are designed to be activated as redcap_every_page_top hook functions. They are dependent on a hook framework that calls _anonymous_ PHP functions such as UF CTS-IT's [Extensible REDCap Hooks](https://github.com/ctsit/extensible-redcap-hooks) ([https://github.com/ctsit/extensible-redcap-hooks](https://github.com/ctsit/extensible-redcap-hooks)).  If you are not use such a framework, each hook will need to be edited by changing `return function($project_id)` to `function redcap_every_page_top($project_id)`.


## Customizing the FRSL hooks <a name="customizing"></a>

The FRSL hooks read configuration data via the UF CTS-IT's [Custom Project Settings](https://github.com/ctsit/redcap_custom_project_settings) ([https://github.com/ctsit/redcap_custom_project_settings](https://github.com/ctsit/redcap_custom_project_settings)) This extension adds a new project configuration section to REDCap Project Setup tab. The new section allows configuration data for REDCap extensions such as FRSL to be saved to a REDCap project's configuration.

For FRSL you will need to use the CPS extension to add an entry named 'form_render_skip_logic' to your project. This new entry should have JSON data that looks something like this:

    {
       "control_field":{
          "arm_name":"baseline_arm_1",
          "field_name":"patient_type"
       },
       "instruments_to_show":[
          {
             "control_field_value":"1",
             "instrument_names":[
                "sdh_details",
                "radiology_sdh",
                "surgical_data",
                "moca",
                "gose",
                "telephone_interview_of_cognitive_status"
             ]
          },
          {
             "control_field_value":"2",
             "instrument_names":[
                "sah_details",
                "radiology_sah",
                "delayed_neurologic_deterioration",
                "ventriculostomysurgical_data",
                "moca",
                "gose",
                "telephone_interview_of_cognitive_status"
             ]
          },
          {
             "control_field_value":"3",
             "instrument_names":[
                "sdh_details",
                "sah_details"
             ]
          }
       ]
    }

Customize the values for arm_name and field_name to decribe your project's control field.  Generally this is field on a form and event very early in your project's data colleciton workflow.

In the instruments_to_show section, add as many entries as your project needs. In each instruments_to_show entry, set the value for the control field and the instrument_names that should be shown when the control field has that value. Note that instruments not named within an instruments_to_show entry will _always_ be shown.


## Developer Notes

When using the local test environment provided by UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can use the deployment tools to configure these hooks for testing in the local VM.  If clone this repo as a child of the redcap_deployment repo, you can configure from the root of the redcap_deployment repo like this:

    fab instance:vagrant test_hook:redcap_every_page_top,form_render_skip_logic/frsl_dashboard.php
    fab instance:vagrant test_hook:redcap_every_page_top,form_render_skip_logic/frsl_record_home_page.php
    fab instance:vagrant test_hook:redcap_every_page_top,form_render_skip_logic/frsl_data_collection_instruments.php


## TODO

* Refactor components common to all three hooks into a library.
* Add a Contributors file
