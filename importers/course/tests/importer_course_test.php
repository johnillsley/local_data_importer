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
 * Unit tests for the local_data_importer_importers_course plugin.
 *
 * @group      local_data_importer_importers_course
 * @group      bath
 * @package    local/data_importer/importers/course
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/coursecatlib.php'); // Course category class.
require_once($CFG->dirroot . '/course/lib.php'); // Course lib functions.

class local_data_importer_importers_course_test extends advanced_testcase {

    /**
     * @var array
     */
    private $courses;
    /**
     * @var integer
     */
    private $pathitemid;

    protected function setup() {
        global $courses, $DB;

        require_once(__DIR__.'/fixtures/course_data.php');
        $this->courses = $courses;

        $this->pathitemid = $DB->insert_record("connector_pathitem",
                array(
                        "name"              => "Test pathitem",
                        "connectorid"       => 1,
                        "pathitem"          => "ABC",
                        "httpmethod"        => "get",
                        "plugincomponent"   => "importers_course",
                        "active"            => 1,
                        "importorder"       => 1,
                        "timecreated"       => 12345
                )
        );
        $DB->insert_record("local_data_importer_settings",
                array(
                        "pathitemid"        => $this->pathitemid,
                        "name"              => 'delete_courses',
                        "value"             => 1 // Do course deletes for this test.
                )
        );

        $this->resetAfterTest();
    }

    public function test_get_database_properties() {
        require_once(__DIR__.'/fixtures/database_properties.php');

        $object = new importers_course_importer($this->pathitemid);
        $class = new ReflectionClass('importers_course_importer');
        $method = $class->getMethod('get_database_properties');
        $method->setAccessible(true);
        $method->invokeArgs($object, array(1));

        $databaseproperties = $class->getProperty('databaseproperties');
        $databaseproperties->setAccessible(true);

        $this->assertTrue($databaseproperties->getValue($object) == $expecteddbproperties);
    }

    public function test_get_plugin_name() {

        $courseimporter = new importers_course_importer($this->pathitemid);
        $pluginname = $courseimporter->get_plugin_name();

        $this->assertEquals($pluginname, get_string('pluginname', $courseimporter->languagepack));
    }

    public function test_get_additional_form_elements() {

        $courseimporter = new importers_course_importer($this->pathitemid);
        $settingsarray = $courseimporter->get_additional_form_elements();
        $html = array_pop($settingsarray); // Only one additional setting to check.
        $expectedhtml = '<select id="additional_setting[delete_courses]" name="additional_setting[delete_courses]"><option value="0" selected="">'.get_string('keepcourses', 'importers_course').'</option><option value="1" selected="selected">'.get_string('deletecourse', 'importers_course').'</option></select>';

        $this->assertEquals($expectedhtml, $html);
    }

    public function test_create_courses() {
        global $DB;

        $courseimporter = data_importer_entity_importer::get_importer($this->pathitemid);
        $courseimporter->do_imports($this->courses);

        foreach ($this->courses as $c) {

            // Check Moodle course created.
            $importedcourse = $DB->get_record("course", array("idnumber" => $c["course"]["idnumber"]));
            $category = $DB->get_record("course_categories", array("id" => $importedcourse->category));
            $this->assertEquals($importedcourse->fullname, $c["course"]["fullname"]);
            $this->assertEquals($importedcourse->shortname, $c["course"]["shortname"]);
            $this->assertEquals($category->name, $c["course_categories"]["name"]);

            // Check that course creation was logged.
            $logitem = $DB->get_record($courseimporter->logtable,
                    array("course_idnumber" => $c["course"]["idnumber"], "pathitemid" => $this->pathitemid));

            $this->assertEquals($logitem->course_fullname, $c["course"]["fullname"]);
            $this->assertEquals($logitem->course_shortname, $c["course"]["shortname"]);
            $this->assertEquals($logitem->course_categories_name, $c["course_categories"]["name"]);
        }
    }

    public function test_update_courses() {
        global $DB;

        $courseimporter = data_importer_entity_importer::get_importer($this->pathitemid);
        $courseimporter->do_imports($this->courses); // Create the initial courses.

        // Create some updates for the existing courses.
        $this->courses[0]["course"]["fullname"] = "Updated course fullname";
        $this->courses[1]["course"]["shortname"] = "Updated course shortname";
        $this->courses[2]["course_categories"]["name"] = "Updated course category";
        $this->courses[2]["course"]["fullname"] = "Another updated fullname";

        $courseimporter->do_imports($this->courses); // Import the updates onto existing courses.

        foreach ($this->courses as $c) {

            // Check Moodle course modified.
            $importedcourse = $DB->get_record("course", array("idnumber" => $c["course"]["idnumber"]));
            $category = $DB->get_record("course_categories", array("id" => $importedcourse->category));
            $this->assertEquals($importedcourse->fullname, $c["course"]["fullname"]);
            $this->assertEquals($importedcourse->shortname, $c["course"]["shortname"]);
            $this->assertEquals($category->name, $c["course_categories"]["name"]);

            // Check that the log has been updated.
            $logitem = $DB->get_record("local_data_importer_course",
                    array("course_idnumber" => $c["course"]["idnumber"], "pathitemid" => $this->pathitemid));
            $this->assertEquals($logitem->course_fullname, $c["course"]["fullname"]);
            $this->assertEquals($logitem->course_shortname, $c["course"]["shortname"]);
            $this->assertEquals($logitem->course_categories_name, $c["course_categories"]["name"]);
        }
        // TODO - create a manual update to an actual Moodle course that then gets set back again.
    }

    public function test_delete_courses() {
        global $DB;
        // This test produced output when a course is deleted so will be classed as 'risky'.

        $courseimporter = data_importer_entity_importer::get_importer($this->pathitemid);
        $courseimporter->do_imports($this->courses); // Create the initial courses.

        $coursecountstart = $DB->count_records("course");

        // Remove two out of the three courses to import.
        $deleting = $this->courses;
        unset($deleting[0]);
        unset($deleting[2]);
        
        $courseimporter->do_imports($deleting); // Import the data to delete two existing courses.

        $coursecountend = $DB->count_records("course");
        $this->assertEquals(($coursecountstart - $coursecountend), 2);
        
        $deletedinlog = $DB->count_records("local_data_importer_course", array("deleted" => 1, "pathitemid" => $this->pathitemid));
        $this->assertEquals($deletedinlog, 2);

        // Try recreating the two courses.
        $courseimporter->do_imports($this->courses); // Restore back to original three course.
        $coursecountend = $DB->count_records("course");
        $this->assertEquals(($coursecountstart - $coursecountend), 0);

        $deletedinlog = $DB->count_records("local_data_importer_course", array("deleted" => 1, "pathitemid" => $this->pathitemid));
        $this->assertEquals($deletedinlog, 0);

        $totalinlog = $DB->count_records("local_data_importer_course", array("pathitemid" => $this->pathitemid));
        $this->assertEquals($totalinlog, 3);
    }

    public function test_import_nulls() {
        global $DB;

        $this->courses[1]["course"]["shortname"] = null;
        $this->courses[2]["course_categories"]["name"] = null;
        $this->courses[0]["course"]["fullname"] = null;

        $courseimporter = data_importer_entity_importer::get_importer($this->pathitemid);
        $courseimporter->do_imports($this->courses);

        $exceptions = $DB->get_records("local_data_importer_errors");
        $this->assertEquals(count($exceptions), 3);
    }

    public function test_import_long_string() {
        global $DB;

        $this->courses[0]["course"]["fullname"] = "ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $this->courses[1]["course"]["shortname"] = "ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $this->courses[2]["course_categories"]["name"] = "ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ";

        $courseimporter = data_importer_entity_importer::get_importer($this->pathitemid);
        $courseimporter->do_imports($this->courses);

        $exceptions = $DB->get_records("local_data_importer_errors");
        $this->assertEquals(count($exceptions), 3);
    }
}