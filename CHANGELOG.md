# Change Log
All notable changes to the REDCap Deployment project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [2.0] - 2017-11-22
### Changed
- Turn FRSL into a external module. (Tiago Bember Simeao)


## [1.1.0] - 2017-07-14
### Changed
- Replace the test project with a simple, non-longitudinal project (Philip Chase, Taryn Stoffs)

### Added
- Add support for record_id_field variable in config data read from custom project settings (Philip Chase, Dileep Rajput)
- Enable frsl_dashboard on non-longitudinal projects (Philip Chase, Dileep Rajput)


## [1.0.0] - 2017-06-19
### Changed
- Use redcap_custom_project_settings extension to fetch the settings (suryayalla, Philip Chase)
- Remove warnings about global hook activation from README as we fixed that issue (Philip Chase)

## [0.2.0] - 2017-05-25
### Changed
- Wrap all javascript variables and functions in a javascript object to avoid namespace collisions (Dileep)
- Modify frsl_record_home_page.php to activate on non-numeric record_id's (Dileep)
- Provide a more complex test project that uses repeating instruments in hidden forms and non-numeric record_ids (Philip Chase)

### Added
- Add support for repeating instruments (Dileep)
- Add 'Developer Notes' section to README (Philip Chase)
- Activate more instruments for control_field_value of 2 in example configuration (Philip Chase)
- Add Apache 2.0 license file

## [0.1.0] - 2017-04-27
### Added
- initial release for Form Render Skip Logic hooks
- frsl_dashboard.php: a hook to disable form links on a per-record basis on the record status dashboard
- frsl_data_collection_instruments.php: a hook to hide forms listed in the left hand menu while doing data entry and replace the "Save & next form" button with a second "Save & Stay" button.
- frsl_record_home_page.php: a hook to hide forms on the record home page.
- empty_test_project.xml: a fully defined test project with no data
- test_project.xml: a fully defined test project with 4 records
- venn_diagram_of_test_project_forms.png: a conceptual diagram of forms displayed or not displayed
