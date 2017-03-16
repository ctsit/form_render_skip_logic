# Form Render Skip Logic Hooks (FRSL)

This is a set of REDCap hooks designed to hide and show instruments based on the value of a single field on a single form.  The hooks are designed to manipulate a proper subset of the forms in the REDCap project.

![venn diagram of test project forms](venn_diagram_of_test_project_forms.png)

See the functional specification at [https://docs.google.com/document/d/1Ej7vCNpKOrC6X9KVpkZkHeY0v2VqQXrjuMIBQtbj1bw/edit#](https://docs.google.com/document/d/1Ej7vCNpKOrC6X9KVpkZkHeY0v2VqQXrjuMIBQtbj1bw/edit#) for functional details.

See [design\_and\_prioritization\_of\_frsl\_hooks.jpg](design_and_prioritization_of_frsl_hooks.jpg) for prioritization of the work and technical details for each hook.

Create a new development project in REDCap using the file [test_project.xml](test_project.xml).  This test project has form named as described in the file [form\_and\_event\_names\_from\_test\_project.csv](form_and_event_names_from_test_project.csv).

