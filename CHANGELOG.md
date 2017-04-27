# Change Log
All notable changes to the REDCap Deployment project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).


## [0.1.0] - 2017-04-27
### Added
- initial release for Form Render Skip Logic hooks
- frsl_dashboard.php: a hook to disable form links on a per-record basis on the record status dashboard
- frsl_data_collection_instruments.php: a hook to hide forms listed in the left hand menu while doing data entry and replace the "Save & next form" button with a second "Save & Stay" button.
- frsl_record_home_page.php: a hook to hide forms on the record home page.
- empty_test_project.xml: a fully defined test project with no data
- test_project.xml: a fully defined test project with 4 records
- venn_diagram_of_test_project_forms.png: a conceptual diagram of forms displayed or not displayed
