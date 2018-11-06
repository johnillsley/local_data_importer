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

$connectorid = optional_param('connectorid', 0, PARAM_INT);
if($connectorid){
    // Existing connector.
    $url->param('connectorid', $connectorid);
}
require_login();

$mform = new local_data_importer_connector_form(null);
if ($mform->is_cancelled()) {
    echo "cancelled";
    redirect($returnurl);
}
if (!$mform->is_cancelled() && $data = $mform->get_data()) {
    var_dump($data);

}
echo $OUTPUT->header();
$mform->display();

echo $OUTPUT->footer();