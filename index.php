<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once("../../config.php");
require_once($CFG->dirroot . "/local/data_importer/dataimporterform.php");
$PAGE->set_url('/local/data_importer/index.php');
require_login();
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('incourse');

/// Print the header
$PAGE->navbar->add('Data Importer');
$PAGE->set_title("Connectors");
$PAGE->set_heading("Connectors");
$action = optional_param('action', 'list_connectors', PARAM_RAW);
$connectorid = optional_param('connectorid', null, PARAM_INT);
$importer_form = new local_data_importer_form();
if ($importer_form->is_submitted()) {
    // process the data
    $formdata = $importer_form->get_data();
    $server = '';
    //Additionally get the servers
    if (isset($_POST['apiserver'])) {
        $server = $_POST['apiserver'];
    }
    $connectorInstance = new local_data_importer_connectorinstance();
    if (!empty($formdata)) {
        $connectorInstance->setdescription($formdata->connector_description);
        $connectorInstance->setname($formdata->connector_name);
        $connectorInstance->set_openapidefinitionurl($formdata->openapidefinitionurl);
        $connectorInstance->setopenapikey($formdata->openapikey);
        $connectorInstance->set_server_apikey($formdata->serverapikey);
        $connectorInstance->setserver($server);
        try {
            $retid = $connectorInstance->save(true);
            $displaynoticegood = "New connector added with id: $retid";
        } catch (\Exception $e) {
            $displaynoticebad = $e->getMessage();
        }

    }
    // pass it on
}
if (!empty($displaynoticegood)) {
    echo $OUTPUT->notification($displaynoticegood, 'notifysuccess');    // good (usually green)
} else if (!empty($displaynoticebad)) {
    echo $OUTPUT->notification($displaynoticebad);                     // bad (usuually red)
}
switch ($action) {
    case 'add_connector':
        $PAGE->set_heading("Add a new connector");
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');
        // ******* ADD / EDIT CONNECTOR ********
        $PAGE->requires->js_call_amd('local_data_importer/fetch_api_definition', 'init', []);
        echo $importer_form->display();
        break;
    case 'edit_connector':
        $PAGE->set_heading("Edit connector");
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');
        echo $renderer->edit_connector_page($connectorid);
        break;
    case 'delete_connector':
        $PAGE->set_heading("Delete connector");
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');
        echo $renderer->edit_connector_page($connectorid);
        break;
    default:
        // ******* LIST ALL ********
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');
        echo $renderer->connectors_page();
        break;
}
echo $OUTPUT->footer();