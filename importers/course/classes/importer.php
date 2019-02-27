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
require_once($CFG->dirroot . '/local/data_importer/importers/entity_importer.php'); // Parent class definition.
require_once($CFG->libdir . '/coursecatlib.php'); // Course category class.
require_once($CFG->dirroot . '/course/lib.php'); // Course lib functions.

/**
 * Class representing an entity importer to handle courses.
 */
class importers_course_importer extends data_importer_entity_importer {

    public function __construct($pathitemid) {

        $this->pathitemid   = $pathitemid;
        $this->logtable     = 'local_data_importer_course';
        $this->languagepack = 'importers_course';

        $this->responses = array(
                'course' => array(
                        'fullname'  => array(),
                        'shortname' => array(),
                        'idnumber'  => array("unique")
                ),
                'course_categories' => array(
                        'name'      => array()
                )
        );
        // TODO - $this->parameters = null; for this sub plugin.
        $this->parameters = array(
                'some_parameter',
                'another_parameter',
                'and_yet_another_one');
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
                $this->local_log($item, $course->timecreated);
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
                $this->local_log($item, $course->timemodified);
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
                    $this->local_log($item, time(), true);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }
    
    public function get_parameters() {

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