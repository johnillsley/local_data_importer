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
require_once($CFG->dirroot . "/local/data_importer/forms/connector_form.php");
require_once($CFG->dirroot . "/local/data_importer/forms/importer/edit_importer.php");
$url = new moodle_url('/local/data_importer/index.php');
$PAGE->set_url($url);
require_login();
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('incourse');

// Print the header.
$PAGE->navbar->add('Data Importer');
$PAGE->set_title("Data Importer");
$PAGE->set_heading("Data Importer");
$action = optional_param('action', 'list_connectors', PARAM_RAW);
$connectorid = optional_param('connectorid', null, PARAM_INT);
$subplugin = optional_param('subplugin', null, PARAM_RAW);
$pathitemid = optional_param('pathitemid', null, PARAM_INT);
$confirmdelete = optional_param('confirmdelete', null, PARAM_INT);
$connectorinstance = new local_data_importer_connectorinstance();
$importerform = new local_data_importer_connector_form();
$pathiteminstance = new local_data_importer_connectorpathitem();
if ($formdata = $importerform->get_data()) {
    // Process the data.
    $server = '';
    // Additionally get the servers.
    if (isset($_POST['apiserver'])) {
        $server = $_POST['apiserver'];
    }

    if (!empty($formdata)) {
        if (isset($_POST['connectorid'])) {
            $connectorinstance->setid($_POST['connectorid']);
        }
        $connectorinstance->setdescription($formdata->connector_description);
        $connectorinstance->setname($formdata->connector_name);
        $connectorinstance->set_openapidefinitionurl($formdata->openapidefinitionurl);
        $connectorinstance->setopenapikey($formdata->openapikey);
        $connectorinstance->set_server_apikey($formdata->serverapikey);
        $connectorinstance->setserver($server);
        try {
            $retid = $connectorinstance->save(true);
            $displaynoticegood = "New connector added with id: $retid";
        } catch (\Exception $e) {
            $displaynoticebad = $e->getMessage();
        }

    }
    // Pass it on.
}
if (!empty($displaynoticegood)) {
    echo $OUTPUT->notification($displaynoticegood, 'notifysuccess');    // Good (usually green).
} else if (!empty($displaynoticebad)) {
    echo $OUTPUT->notification($displaynoticebad);                     // Bad (usuually red).
}
switch ($action) {
    case 'add_connector':
        $PAGE->set_heading("Add a new connector");
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');
        // ADD / EDIT CONNECTOR .
        $PAGE->requires->js_call_amd('local_data_importer/fetch_api_definition', 'init', []);
        echo $importerform->display();
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
        echo $renderer->delete_connector_page($connectorid);
        break;
    case 'edit_importer':
        $PAGE->set_heading("Edit Importer");
        echo $OUTPUT->header();
        if (isset($pathitemid)) {
            $renderable = new local_data_importer\output\importers_page();
            $pathitemdata = $renderable->get_single_path_item_instance($pathitemid);
            $importereditform = new local_data_importer_edit_importer_form(null, $pathitemdata);
            echo $importereditform->display();
        }
        break;
    case 'deletepathitem':
        $PAGE->set_heading("Delete Pathitem");
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');
        echo $renderer->delete_pathitem_page($connectorid);
        break;
    default:
        // LIST ALL.
        echo $OUTPUT->header();
        $renderer = $PAGE->get_renderer('local_data_importer');

        if ($confirmdelete == 1) {
            // Delete connector.
            if (isset($connectorid)) {
                $connectorinstance->setid($connectorid);
                try {
                    $connectorinstance->delete();
                } catch (\Exception $e) {
                    echo $OUTPUT->notification($e->getMessage());    // Good (usually green).
                }

            }
            if (isset($pathitemid)) {
                // Delete pathitem.
                $pathiteminstance->set_id($connectorid);
                try {
                    $pathiteminstance->delete();
                } catch (\Exception $e) {
                    echo $OUTPUT->notification($e->getMessage());    // Good (usually green).
                }

            }
        }
        echo $renderer->index_page();
        break;
}
echo $OUTPUT->footer();