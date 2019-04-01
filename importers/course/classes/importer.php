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

        parent::__construct($pathitemid);

        $this->logtable     = 'importers_course';
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

        $categoryid = $this->get_course_category_id($item);
        $courseconfig = get_config('moodlecourse');
        $coursevisible = $this->get_setting('course_visible');
        $courseconfig->visible = ($coursevisible == get_string('show')) ? 1 : 0;

        // Create course.
        $course = new stdClass();
        $course->fullname           = $item['course']['fullname'];
        $course->shortname          = $item['course']['shortname'];
        $course->idnumber           = $item['course']['idnumber'];
        $course->timecreated        = time();
        $course->category           = $categoryid;
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
            $this->local_log($item, $course->timecreated, 'created');
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
            $this->local_log($item, $course->timemodified, 'updated');
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

        if ($this->get_setting('course_delete') == get_string('deletecourse', 'importers_course')) {
            $courseid = $DB->get_field("course", "id", array("idnumber" => $item->course_idnumber));
            ob_start();
            if (delete_course($courseid)) {
                $this->local_log($item, time(), 'deleted');
            }
            ob_end_clean();
        }
    }

    public function get_parameters() {

        $parameters = array();
        // TODO - Remove settings below they are for testing only - should return array().
        $parameters = array(
                array('some_parameter' => 'X1', 'another_parameter' => 'Y1'),
                array('some_parameter' => 'X1', 'another_parameter' => 'Y2'),
                array('some_parameter' => 'X2', 'another_parameter' => 'Y1'),
                array('some_parameter' => 'X2', 'another_parameter' => 'Y2'),
        );

        return $parameters;
    }

    /**
     * Outputs an array of form elements to create settings that are unique to this type of plugin.
     *
     * additional field type
     * additional field name
     * additional options
     * additional required or not
     * additional label
     * @return array of html form elements to be added to the form when an instance of this plugin is created
     */
    public function get_additional_form_elements() {

        $additionalsettings = array();

        // Course Visbility Setting. [ default course created should be visible or hidden ?].
        $additionalsettings['course_visible'] = array(
            'field_label' => get_string('settinghidecourse', 'importers_course'),
            'field_type' => 'select',
            'options' => [
                get_string('show') => get_string('show'),
                get_string('hide') => get_string('hide')
            ]
        );

        // Course Deletion Setting. [ delete course if deleted from source ? ].
        $additionalsettings['course_delete'] = array(
            'field_label' => get_string('settingdeletecourse', 'importers_course'),
            'field_type' => 'select',
            'options' => [
                get_string('keepcourses', 'importers_course') => get_string('keepcourses', 'importers_course'),
                get_string('deletecourse', 'importers_course') => get_string('deletecourse', 'importers_course')
            ]
        );

        return $additionalsettings;

        // TODO - how about a setting to prevent update of course fullname and/or shortname?
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
        if (!$category = $DB->get_record("course_categories", array("idnumber" => $item['course_categories']['name']))) {
            if (!$category = coursecat::create(array(
                    "name" => $item['course_categories']['name'],
                    "idnumber" => $item['course_categories']['name'],
            ))) {
                throw new \Exception("Cannot get a valid course category id to create a course");
            }
        }
        return $category->id;
    }
}