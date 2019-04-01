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
 * This form allows different test scenarios to be created for local_data_importer
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . "/local/data_importer/importers/entity_importer.php");
global $CFG, $DB;
require_login();

const DB_PATH_ITEMS = 'local_data_importer_path';
const DB_IMPORTER_COURSE = 'importers_course';

$html = "";

// Check for course test setup.
$action = optional_param('action', '', PARAM_RAW);
if ($action == 'Setup courses') {

    $reflector = new ReflectionClass("importers_course_importer");
    $createentity = $reflector->getMethod("create_entity");
    $createentity->setAccessible(true);
    $updateentity = $reflector->getMethod("update_entity");
    $updateentity->setAccessible(true);
    $deleteentity = $reflector->getMethod("delete_entity");
    $deleteentity->setAccessible(true);

    $pathitemid   = required_param('pathitemid', PARAM_INT);
    $coursedelete = optional_param('coursedelete', 0, PARAM_INT);
    $courseupdate = optional_param('courseupdate', 0, PARAM_INT);
    $coursecreate = optional_param('coursecreate', 0, PARAM_INT);

    $countdelete = 0;
    $countupdate = 0;
    $countcreate = 0;
    $importer = data_importer_entity_importer::get_importer($pathitemid);

    $currentcourses = $DB->get_records(DB_IMPORTER_COURSE, array("pathitemid" => $pathitemid, "deleted" => 0));
    shuffle ( $currentcourses ); // Randomise order of array.

    $pathitem = $DB->get_record(DB_PATH_ITEMS, array("id" => $pathitemid));
    $html .= html_writer::tag('p', 'Making changes to <strong>courses</strong> imported using <strong>'
            . $pathitem->name . '</strong>');
    $html .= html_writer::tag('p', 'There are currently ' . count($currentcourses) . ' courses created for this path item.');
    $html .= html_writer::start_tag('ul');
    // Do deletes first.
    if ($coursedelete > 0) {
        for ($i = 0; $i < $coursedelete; $i++) {
            if ($logcourse = array_pop($currentcourses)) {
                $courseid = $DB->get_field("course", "id", array("idnumber" => $logcourse->course_idnumber));
                ob_start();
                delete_course($courseid);
                ob_end_clean();
                $DB->delete_records(DB_IMPORTER_COURSE, array("id" => $logcourse->id));
                $countdelete++;
            }
        }
    }
    $html .= html_writer::tag('li', $countdelete . ' have been deleted.');

    // Do updates.
    if ($courseupdate > 0) {
        for ($i = 0; $i < $courseupdate; $i++) {
            if ($logcourse = array_pop($currentcourses)) {
                $courseid = $DB->get_field("course", "id", array("idnumber" => $logcourse->course_idnumber));

                $logcourse->course_fullname = $logcourse->course_fullname . " MODIFIED FOR TESTING";

                $course = new stdClass();
                $course->id             = $courseid;
                $course->fullname       = $logcourse->course_fullname;
                $course->shortname      = $logcourse->course_shortname;
                $course->category       = 1;
                $course->timemodified   = time();
                update_course(clone($course));
                $DB->update_record(DB_IMPORTER_COURSE, $logcourse);
                $countupdate++;
            }
        }
    }
    $html .= html_writer::tag('li', $countupdate . ' have been updated.');

    // Do creates.
    if ($coursecreate > 0) {
        $ref = 0;

        $importer = data_importer_entity_importer::get_importer($pathitemid);

        for ($i = 0; $i < $coursecreate; $i++) {
            do {
                $ref++;
                $idnumber = 'course' . $ref;
                $exists = $DB->get_records_sql('
                    SELECT * FROM {course}
                    WHERE idnumber = "' . $idnumber . '"
                    OR shortname = "' . $idnumber . '"');
            } while (count($exists) != 0);

            $course = array(
                    "course" => array(
                            'fullname'  => "Test Course " . $ref,
                            'shortname' => "course" . $ref,
                            'idnumber'  => "course" . $ref
                    ),
                    'course_categories' => array(
                            'name'      => "Temp test courses"
                    )
            );
            // Use reflector object and push courses one at a time.
            $createentity->invoke($importer, $course);
            $countcreate++;
        }
    }
    $html .= html_writer::tag('li', $countcreate . ' have been created.');

    $html .= html_writer::end_tag('ul');
}

$url = new moodle_url('/local/data_importer/test_setup.php');
$PAGE->set_url($url);
$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Data importer test setup');
$PAGE->set_heading('Data importer test setup');
$PAGE->navbar->add('Data importer test setup');
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Data importer test setup'));

// The form for testing courses.
$html .= html_writer::tag('h4', 'Courses');
$attributes = array('method' => 'POST', 'action' => $url);
$html .= html_writer::start_tag('form', $attributes);

$html .= "Select importer: ";
$html .= html_writer::start_tag('select', array("id" => "pathitemid", "name" => "pathitemid"));
$importers = $DB->get_records(DB_PATH_ITEMS, array("plugincomponent" => "importers_course"));

foreach ($importers as $importer) {
    $html .= html_writer::tag('option', $importer->name, array('value' => $importer->id));
}
$html .= html_writer::end_tag('select');

$html .= "<br/>Number of courses to delete: ";
$html .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'coursedelete'));
$html .= "<br/>Number of courses to update: ";
$html .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'courseupdate'));
$html .= "<br/>Number of courses to create: ";
$html .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'coursecreate'));
$html .= "<br/>";
$html .= html_writer::empty_tag('input', array(
        'type' => 'submit',
        'name' => 'action',
        'value' => 'Setup courses',
        'class' => 'btn btn-primary')
);
$html .= html_writer::end_tag('form');

// The form for the next type of sub plugin goes here....



echo $html;
echo $OUTPUT->footer();
