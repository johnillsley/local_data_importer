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
/**
 * Logic to handle editing existing connectors
 *
 * @package    local_data_importer
 * @author     Hittesh Ahuja <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once($CFG->dirroot . "/local/data_importer/forms/connector_form.php");
$returnurl = new moodle_url('/local/data_importer/index.php');
$url = new moodle_url('/local/data_importer/edit_connector.php');
$PAGE->set_url($url);
$PAGE->set_context(\context_system::instance());
require_login();
$connectorid = optional_param('connectorid', 0, PARAM_INT);
$connectordata = null;
$renderable = new local_data_importer\output\importers_page();
$connectordata = $renderable->get_single_connector_instance($connectorid);
$connectorinstance = new local_data_importer_connectorinstance();
$mform = new local_data_importer_connector_form(null, ['id' => $connectorid,
    'description' => $connectordata->description,
    'name' => $connectordata->name,
    'openapidefinitionurl' => $connectordata->openapidefinitionurl,
    'openapikey' => $connectordata->openapikey,
    'server' => $connectordata->server,
    'serverapikey' => $connectordata->serverapikey]);
if (!$mform->is_cancelled() && $formdata = $mform->get_data()) {
    if (!empty($formdata)) {
        $connectorinstance->set_id($connectorid);
        $connectorinstance->set_description($formdata->description);
        $connectorinstance->set_name($formdata->name);
        $connectorinstance->set_openapidefinitionurl($formdata->openapidefinitionurl);
        $connectorinstance->set_openapi_key($formdata->openapikey);
        $connectorinstance->set_server_apikey($formdata->serverapikey);
        $connectorinstance->set_server($formdata->apiserver);
        $connectorinstance->save(true);
        // Return to index.
        redirect($returnurl);
    }
} else if ($mform->is_cancelled()) {
    // Form is cancelled, return to index.
    redirect($returnurl);
}
$PAGE->set_heading("Edit Connector");
echo $OUTPUT->header();
$PAGE->requires->js_call_amd('local_data_importer/fetch_api_definition', 'init', []);
$mform->display();
echo $OUTPUT->footer();