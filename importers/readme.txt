IMPORTER MODULES
----------------

These are sub plugins of local/data_importer.

Each of these modules contains a number of expected components:

  classes/importer.php: the connection to Moodle APIs. 
  This extends the class \local_data_importer\data_importer_entity_importer
  Methods that must be implemented in this class are...
   - create_entity()
   - update_entity()
   - get_parameters()
   - get_additional_form_elements()

  version.php: defines some meta-info and provides upgrading code

  db/install.xml: an SQL dump of all the required db tables and data

  lang/en/importers_PLUGINNAME.php: a language string file

  view.php: a page to view a particular instance

  event/PLUGINNAME_created.php Moodle event file to log changes to Moodle data
