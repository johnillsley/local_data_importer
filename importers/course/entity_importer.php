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
 * This file defines the course entity importer which extends base plugin local_data_importer.
 *
 * @package    local/data_importer/importers/course
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__.'/../entity_importer.php'); // Parent class definition.
require_once($CFG->libdir . '/coursecatlib.php'); // Course category class.
require_once($CFG->dirroot . '/course/lib.php'); // Course lib functions.

/**
 * Class representing an entity importer to handle courses.
 */
class data_importer_course_importer extends data_importer_entity_importer {

    public function __construct($id) {

        $this->importerplugin = 'local_data_importer_course';
        $this->languagepack = 'importers_course';
        $this->pathitemid = $id;

        $this->responses = array(
                'course' => array(
                        'fullname',
                        'shortname',
                        'idnumber'
                ),
                'course_categories' => array(
                        'name'
                )
        );
        $this->uniquekey = 'course' . $this->tablefieldseperator . 'idnumber';
        $this->parameters = array('sits_mappings' => array('sits_code', 'acyear', 'period_code')); // TODO - check this.
    }

    /**
     * Creates a single course using data that has already been validated.
     * Uses default settings from site admin course defaults.
     * Creates a record of the course creation locally so that it will not be created again.
     *
     * @param array $item contains all the data required to create a course
     * @throws Exception if the course could not be created
     * @return void
     */
    protected function create_entity($item = array()) {

        try {
            $categoryid = $this->get_course_category_id($item);

            // Create course.
            $course = new stdClass();
            $course->fullname    = $item['course']['fullname'];
            $course->shortname   = $item['course']['shortname'];
            $course->idnumber    = $item['course']['idnumber'];
            $course->timecreated = time();
            $course->category    = $categoryid;
            // TODO - visible is set + review other settings
            // https://moodle.bath.ac.uk/admin/settings.php?section=coursesettings .

            // Apply course default settings.
            $courseconfig = get_config('moodlecourse');
            $course->format             = $courseconfig->format;
            $course->newsitems          = $courseconfig->newsitems;
            $course->showgrades         = $courseconfig->showgrades;
            $course->showreports        = $courseconfig->showreports;
            $course->maxbytes           = $courseconfig->maxbytes;
            $course->groupmode          = $courseconfig->groupmode;
            $course->groupmodeforce     = $courseconfig->groupmodeforce;
            $course->visible            = $courseconfig->visible;
            $course->visibleold         = $courseconfig->visible;
            $course->lang               = $courseconfig->lang;
            $course->enablecompletion   = $courseconfig->enablecompletion;
            $course->numsections        = $courseconfig->numsections;
            $course->startdate          = usergetmidnight(time());

            if (create_course($course)) {
                // Save action in data_importer_course_log DB table.
                $logitem = $this->prepare_for_log($item, $course->timecreated);
                $logitem->deleted = 0;
                $this->local_log($logitem);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Updates a single course using data that has already been validated.
     * Updates the local log so that the updated import is recorded.
     *
     * @param array $item contains all the data required to update the course
     * @throws Exception if the course could not be updated
     * @return void
     */
    protected function update_entity($item = array()) {
        global $DB;

        // TODO - check an additional setting to decide whether course get updated.
        try {
            $current = $DB->get_record("course", array("idnumber" => $item['course']['idnumber']));

            $categoryid = $this->get_course_category_id($item);

            $course = new stdClass();
            $course->id             = $current->id;
            $course->fullname       = $item["course"]["fullname"];
            $course->shortname      = $item["course"]["shortname"];
            $course->category       = $categoryid;
            $course->timemodified   = time();
            update_course(clone($course)); // Need to clone as update_course changes $course which breaks the check below.

            // Check update happened ok - as the update_course function does not return anything to indicate success.
            if ($DB->get_record("course", (array)$course)) {
                // Save action in data_importer_course_log DB table.
                $logitem = $this->prepare_for_log($item, $course->timemodified);
                $logitem->deleted = 0;
                $this->local_log($logitem);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes a single course on condition that the additional setting delete_courses has been set to 1 for the pathitem.
     * Updates the local log to indicate that the course has been updated
     *
     * @param array $item contains all the data required to delete the course
     * @throws Exception if the course could not be deleted
     * @return void
     */
    protected function delete_entity($item = array()) {
        global $DB;

        if ($this->get_setting('delete_courses') == 1) {
            try {
                $courseid = $DB->get_field("course", "id", array("idnumber" => $item->course_idnumber));

                if (delete_course($courseid)) {
                    // Save action in data_importer_course_log DB table.
                    $logitem = new stdClass();
                    $logitem->course_idnumber = $item->course_idnumber;
                    $logitem->deleted = 1;
                    $this->local_log($logitem);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Sorts all the course items extracted from the external web service into three groups.
     * Using the local log of previously imported courses the course items are sorted into
     * either create, update or delete arrays.
     *
     * @param array of $items that have been extracted from the external web service
     * @return object holding separate arrays for create, update and delete items
     */
    protected function sort_items($items = array()) {
        global $DB;

        $action = new stdClass();
        $action->create = array();
        $action->update = array();
        $action->delete = array();

        $k = explode($this->tablefieldseperator, $this->uniquekey);
        $uniquekeytable = $k[0];
        $uniquekeyfield = $k[1];

        // Get all existing items imported into Moodle from local log.
        $records = $DB->get_records($this->importerplugin, array("deleted" => 0, "pathitemid" => $this->pathitemid));

        // Set keys to unique field value.
        $current = array();
        foreach ($records as $record) {
            $current[$record->course_idnumber] = $record;
            // TODO - check characters in key.
        }

        foreach ($items as $item) {

            $key = $item[$uniquekeytable][$uniquekeyfield];
            // TODO - get actual course properties NOT LOGGED as someone could have updated locally!!!!
            if (array_key_exists($key, $current)) {
                // Item already exists so check if it needs updating.
                if ($current[$key]->course_fullname != $item['course']['fullname'] ||
                        $current[$key]->course_shortname != $item['course']['shortname'] ||
                        $current[$key]->course_categories_name != $item['course_categories']['name']) {

                    $action->update[] = $item;
                }
            } else {
                // Item needs adding.
                $action->create[] = $item;
            }
            unset($current[$key]); // Take off the list to delete.
        }
        // What is left in $current need to be deleted. Note format of $current is different to $item.
        $action->delete = $current;

        return $action;
    }

    public function provide_web_service_parameters() {

        return null;
    }

    /**
     * Outputs an array of form elements to create settings that are unique to this type of plugin.
     *
     * @return array of html form elements to be added to the form when an instance of this plugin is created
     */
    public function get_additional_form_elements() {

        $additionalsettings = array();

        $settingname = 'delete_courses';
        $options = array();
        $options[0] = get_string('keepcourses', 'importers_course');
        $options[1] = get_string('deletecourse', 'importers_course');

        $additionalsettings[] = $this->get_html_additional_setting($settingname, $options);
        // TODO - What about the label?

        // TODO - how about a setting to prevent update of course fullname and/or shortname?
        // TODO - Don't do updates - or only update categories.
        return $additionalsettings;
    }

    /**
     * Formats course data from the import ready to be stored in the local log.
     *
     * @param array $item - the course data from the external web service.
     * @param integer $time - the time the course was modified.
     * @return object $importer that can be written to the local log.
     */
    private function prepare_for_log($item, $time) {

        $importer = new stdClass();
        $importer->course_fullname          = $item['course']['fullname'];
        $importer->course_shortname         = $item['course']['shortname'];
        $importer->course_idnumber          = $item['course']['idnumber'];
        $importer->course_categories_name   = $item['course_categories']['name'];
        $importer->timemodified             = $time;
        return $importer;
    }

    /**
     * Return the course category id for the course category name supplied.
     * If the course category name is not found a new course category is created.
     *
     * @param array $item - the course data from the external web service.
     * @throws Exception if the course category cannot be found and creation of a new course category fails.
     * @return integer $categoryid the course category id for the course category name supplied.
     */
    private function get_course_category_id($item) {
        global $DB;
        // TODO - What happens if more than one course category has the same name?
        if (!$category = $DB->get_record("course_categories", array("name" => $item['course_categories']['name']))) {
            if (!$category = coursecat::create(array("name" => $item['course_categories']['name']))) {
                throw new \Exception("Cannot get a valid course category id to create a course");
            }
        }
        return $category->id;
    }
}