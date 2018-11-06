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
require_once($CFG->dirroot . "/local/data_importer/forms/importer/edit_importer.php");
$url = new moodle_url('/local/data_importer/edit_importer.php');
$PAGE->set_url($url);
require_login();
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('incourse');
// Print the header.
$PAGE->navbar->add('Data Importer');
$PAGE->set_title("Data Importer");
$PAGE->set_heading("Data Importer");
echo $OUTPUT->header();
$action = optional_param('action', null, PARAM_RAW);
$pathitemid = required_param('pathitemid',PARAM_INT);
$pathitemname = optional_param('pathitemname', null, PARAM_INT);
$renderable = new local_data_importer\output\importers_page();

    $pathitemdata = $renderable->get_single_path_item_instance($pathitemid);
    $importereditform = new local_data_importer_edit_importer_form(null, $pathitemdata);

if ($formdata = $importereditform->get_data()) {
    var_dump($formdata);
    die;
}
switch ($action) {
    case 'save':
        if (isset($pathitemid) && isset($pathitemname)) {
            $pathitemobject = new local_data_importer_connectorpathitem();
            $object = $pathitemobject->get_by_id($pathitemid);
            $object->set_name($pathitemname);
            $object->save();
        }


        break;
}
echo $importereditform->display();
echo $OUTPUT->footer();


