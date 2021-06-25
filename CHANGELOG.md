# Change Log
All notable changes to the Form Render Skip Logic module will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).


## [3.3.13] - 2021-06-25
### Changed
- Fix bug preventing FRSL from working on the record_home_page with records whose record_id values have spaces in them (Travis Wilson)


## [3.3.12] - 2021-05-26
### Changed
- Fix bug that removes arm-event after 10th control field in project level configuration, credit to REDCap community user nathan.orr (mbentz)


## [3.3.11] - 2020-03-20
### Changed
- Adjust instance appending to only occur on repeating events (Kyle Chesney)
- Update minimum RC version to 8.7.2 to ensure all core lib functions exist (Kyle Chesney)
- Resize large images in the README that displayed poorly in REDCap (Philip Chase)

### Added
- Add repeating instance test projects for issues 41 and 67 (Philip Chase)


## [3.3.10] - 2019-10-14
### Changed
- Document the limited repeating event support in FRSL (Philip Chase)
- Document how to use FRSL with surveys (Philip Chase)
- Go to correct instance when using Save and Go to Next Form (Kyle Chesney)
- Fix bug on Record Home wherein FRSL would quietly fail to hide forms when returning from Save and Exit Record (Kyle Chesney)
- Address cosmetic error caused by "Delete all data on event" row of Record Home (Kyle Chesney)


## [3.3.9] - 2019-10-11
### Changed
- Use only the records for the DAG if one is specified in Record Status Dashboard (Kyle Chesney)
- Clarify which control field type is deprecated (Philip Chase)


## [3.3.8] - 2019-09-11
### Changed
- Improve performance of Record Status Dashboard by limiting the records parsed to those viewed instead of entire dataset (Kyle Chesney)
- Check for arm or ID before loading FRSL (Kyle Chesney)
- Document deprecation of advanced control fields(Kyle Chesney)


## [3.3.7] - 2019-06-04
### Changed
- Update LogicTester call for new variable in v9 (Kyle Chesney)
- Add Kyle Chesney as contributor (Kyle Chesney)


## [3.3.6] - 2019-06-03
### Changed
- Checks if records exist before preventing hidden data (Kyle Chesney)


## [3.3.5] - 2019-04-08
### Changed
- Fix for repeating instruments display bug - issue #40 (adam nunez)


## [3.3.4] - 2018-12-12
### Changed
- Move authors from config.json to authors.md and add Marly as an author (Philip Chase)
- Preventing "Unsupported operands types" error (tbembersimeao)


## [3.3.3] - 2018-08-16
### Changed
- Fixing wrong behavior of "Control mode" field on config form: part II. (Tiago Bember Simeao)
- Fixing wrong logic on 2.x - 3.x migration. (Tiago Bember Simeao)


## [3.3.2] - 2018-08-14
### Changed
- Fix wrong behavior of "Control mode" field on config form. (Tiago Bember Simeao)


## [3.3.1] - 2018-08-13
### Changed
- Refactor 2.x - 3.x migration in order to use External Modules API functions. (Tiago Bember Simeao)


## [3.3.0] - 2018-08-09
### Added
- Add "Prevent hiding of filled forms" config. (Tiago Bember Simeao)


## [3.2.0] - 2018-08-08
### Added
- Add support for migrating FRSL v2.x.x configurations to FRSL v3.x.x configurations automatically (Dileep Rajput)
- Add support for equations on Advanced control mode (Tiago Bember Simeao)
- Add support to event-relative control fields (Tiago Bember Simeao)
- Add Zenodo DOI to README (Philip Chase)


## [3.1.1] - 2018-06-04
### Changed
- Fixing control field's 2nd column visibility. (Tiago Bember Simeao)


## [3.1.0] - 2018-06-02
### Added
- Add support for data piping and smart variables in place of a control field. (Tiago Bember Simeao & Philip Chase)


## [3.0.0] - 2018-05-31
### Added
- Add support for multiple control fields. (Tiago Bember Simeao & Philip Chase)


## [2.1.0] - 2018-05-04
### Added
- Adding config checkbox to determine whether to enable FRSL when control field value is not saved yet. (Tiago Bember Simeao)

### Changed
- Preventing false alarms/errors on redirect. (Tiago Bember Simeao)
- Fixing potential conflict with LDEW. (Tiago Bember Simeao)


## [2.0.2] - 2018-04-05
### Changed
- Fixed #12: Currently FRSL does not support "Auto-continue to next survey". (Tiago Bember Simeao)


## [2.0.1] - 2018-03-02
### Changed
- Fixed #10: `Save and go to the next form` redirects to the dashboard instead of the next available instrument (Tiago Bember Simeao)


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
