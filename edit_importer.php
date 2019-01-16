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
 * Logic to handle editing existing importers
 *
 * @package    local_data_importer
 * @author     Hittesh Ahuja <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
$returnurl = new moodle_url('/local/data_importer/index.php');
$action = optional_param('action', null, PARAM_RAW);
$pathitemid = required_param('pathitemid', PARAM_INT);
$pathitemname = optional_param('pathitemname', null, PARAM_INT);
$renderable = new local_data_importer\output\importers_page();
$pathitemdata = $renderable->get_single_path_item_instance($pathitemid);
 $pathiteminstance = new local_data_importer_connectorpathitem();
$mform = new local_data_importer_edit_importer_form(null, $pathitemdata);
if (!$mform->is_cancelled() && $formdata = $mform->get_data()) {
    if (!empty($formdata)) {
        $pathiteminstance->set_id($formdata->pathitemid);
        $pathiteminstance->set_name($formdata->pathitemname);
        $pathiteminstance->set_connector_id($formdata->connnectorid);
        $pathiteminstance->set_path_item($formdata->pathitem);
        $pathiteminstance->set_active(true);
        $pathiteminstance->set_http_method($formdata->httpmethod);
        $pathiteminstance->set_plugin_component($formdata->subplugin);
        try{
            $pathiteminstance->save(true);

        }
        catch (\Exception$e){
            var_dump("Message:".$e->getMessage());
        }
        // Return to index.
        redirect($returnurl);
    }
} else if ($mform->is_cancelled()) {
    // Form is cancelled, return to index.
    redirect($returnurl);
}
echo $OUTPUT->header();
$PAGE->set_heading("Edit Importer");
$mform->display();
echo $OUTPUT->footer();


