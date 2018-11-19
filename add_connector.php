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
$returnurl = new moodle_url('/local/data_importer/index.php');
$url = new moodle_url('/local/data_importer/add_connector.php');
$PAGE->set_url($url);
$PAGE->set_context(\context_system::instance());
require_login();
$connectorid = optional_param('connectorid', 0, PARAM_INT);
$connectordata = null;
if ($connectorid) {
    // Existing connector.
    $url->param('connectorid', $connectorid);
    $renderable = new local_data_importer\output\importers_page();
    $connectordata = $renderable->get_single_connector_instance($connectorid);
    var_dump($connectordata);

}
$connectorinstance = new local_data_importer_connectorinstance();
$mform = new local_data_importer_connector_form(null);
$mform->set_data($connectordata);
if ($mform->is_cancelled()) {
    echo "cancelled";
    redirect($returnurl);
}
if (!$mform->is_cancelled() && $formdata = $mform->get_data()) {
    $server = '';
    // Additionally get the servers.
    if (isset($_POST['apiserver'])) {
        $server = $_POST['apiserver'];
    }
    if (!empty($formdata)) {
        if (isset($_POST['connectorid'])) {
            $connectorinstance->setid($_POST['connectorid']);
        }
        $connectorinstance->setdescription($formdata->description);
        $connectorinstance->setname($formdata->name);
        $connectorinstance->set_openapidefinitionurl($formdata->openapidefinitionurl);
        $connectorinstance->setopenapikey($formdata->openapikey);
        $connectorinstance->set_server_apikey($formdata->serverapikey);
        $connectorinstance->setserver($server);
        try {
            $retid = $connectorinstance->save(true);
            // Redirect to index page.
            redirect($returnurl);
        } catch (\Exception $e) {
            $displaynoticebad = $e->getMessage();
        }

    }
}
echo $OUTPUT->header();
$PAGE->requires->js_call_amd('local_data_importer/fetch_api_definition', 'init', []);
$mform->display();


echo $OUTPUT->footer();