# Form Render Skip Logic Hooks (FRSL)

This is a set of REDCap hooks designed to hide and show instruments based on the value of a single field on a single form.  The original use case of these tools was to facilitate a data entry workflow specific to acute brain injury diagnoses, but the tools is generalized to support the hiding (and showing) of any number of forms based on a field value on one form. 

![venn diagram of test project forms](venn_diagram_of_test_project_forms.png)

See the functional specification at [https://docs.google.com/document/d/1Ej7vCNpKOrC6X9KVpkZkHeY0v2VqQXrjuMIBQtbj1bw/edit#](https://docs.google.com/document/d/1Ej7vCNpKOrC6X9KVpkZkHeY0v2VqQXrjuMIBQtbj1bw/edit#) for functional details.

Create a new development project in REDCap using the file [test_project.xml](test_project.xml). This project includes 4 subject records. Two records have a diagnosis of SAH while the other two have a diagnosis of _SDH_. When all three hooks are installed and activated on this project, the 4 subjects will show two different sets of accessible forms based on the diagnosis.

Note: We do not recommend activating this hook globally. The references to common form and field names could result in unexpected behavior in projects not designed to use these hooks. 

## TODO

* Change global javascript variable names to reduce the risk of name collisions.
* Refactor components common to all three hooks into a library. 
* Refactor all three hooks to read configuration data from a common, external, project-centered data source.
