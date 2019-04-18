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
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_data_importer_importers_course_testcase extends advanced_testcase {

    const DB_CONNECTOR = 'local_data_importer_connect';
    const DB_PATHITEM = 'local_data_importer_path';
    const DB_RESPONSE = 'local_data_importer_resp';
    const DB_SETTINGS = 'local_data_importer_setting';
    const DB_LOCAL_LOG = 'importers_course';
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

        $connectorid = $DB->insert_record(self::DB_CONNECTOR,
                array(
                        "name"                  => "Connector",
                        "description"           => "Test",
                        "openapidefinitionurl"  => "https://api.swaggerhub.com/",
                        "openapikey"            => "ABC",
                        "server"                => "samis-dev-stutalk.bath.ac.uk",
                        "serverapikey"          => "XYZ",
                        "timemodified"          => 12345,
                        "timecreated"           => 12345
                )
        );

        $this->pathitemid = $DB->insert_record(self::DB_PATHITEM,
                array(
                        "name"              => "Test pathitem",
                        "connectorid"       => $connectorid,
                        "pathitem"          => "ABC",
                        "httpmethod"        => "get",
                        "plugincomponent"   => "importers_course",
                        "active"            => 1,
                        "importorder"       => 1,
                        "timecreated"       => 12345
                )
        );
        $DB->insert_records(self::DB_SETTINGS, array(
                array(
                        "pathitemid"        => $this->pathitemid,
                        "name"              => 'course_delete',
                        "value"             => get_string('deletecourse', 'importers_course') // Do course deletes for this test.
                ),
                array(
                        "pathitemid"        => $this->pathitemid,
                        "name"              => 'course_visible',
                        "value"             => get_string('show') // Set course to visible when created.
                )
            )
        );
        // Need to create a response mapping for the unique field (course_idnumber).
        // Normally all response fields would be mapped but only unique fields need to be mapped to pass test.
        $DB->insert_record(self::DB_RESPONSE, array(
                'pathitemid'            => $this->pathitemid,
                'pluginresponsetable'   => 'course',
                'pluginresponsefield'   => 'idnumber',
                'pathitemresponse'      => 'test',
                'timecreated'           => 1234,
                'timemodified'          => 1234
        ));
        $this->resetAfterTest();
    }

    public function test_get_database_properties() {
        require_once(__DIR__.'/fixtures/database_properties.php');

        $courseimporter = new importers_course_importer($this->pathitemid);
        $class = new ReflectionClass('importers_course_importer');
        $method = $class->getMethod('get_database_properties');
        $method->setAccessible(true);
        $method->invokeArgs($courseimporter, array(1));

        $databaseproperties = $class->getProperty('databaseproperties');
        $databaseproperties->setAccessible(true);

        $this->assertTrue($databaseproperties->getValue($courseimporter) == $expecteddbproperties);
    }

    public function test_get_plugin_name() {

        $courseimporter = new importers_course_importer($this->pathitemid);
        $pluginname = $courseimporter->get_plugin_name();

        $this->assertEquals(get_string('pluginname', $courseimporter->languagepack), $pluginname);
    }

    public function test_get_additional_form_elements() {

        $courseimporter = new importers_course_importer($this->pathitemid);
        $formdata = $courseimporter->get_additional_form_elements();

        $expected = array(
                'course_visible' => array(
                        'field_label' => get_string('settinghidecourse', 'importers_course'),
                        'field_type' => 'select',
                        'options' => [
                                get_string('show') => get_string('show'),
                                get_string('hide') => get_string('hide')
                        ]),
                'course_delete' => array(
                        'field_label' => get_string('settingdeletecourse', 'importers_course'),
                        'field_type' => 'select',
                        'options' => [
                                get_string('keepcourses', 'importers_course') => get_string('keepcourses', 'importers_course'),
                                get_string('deletecourse', 'importers_course') => get_string('deletecourse', 'importers_course')
                        ])
        );

        $this->assertEquals($expected, $formdata);
    }

    public function test_create_courses() {
        global $DB;

        $courseimporter = new importers_course_importer($this->pathitemid);
        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses);

        foreach ($this->courses as $c) {

            // Check Moodle course created.
            $importedcourse = $DB->get_record("course", array("idnumber" => $c["course"]["idnumber"]));
            $category = $DB->get_record("course_categories", array("id" => $importedcourse->category));
            $this->assertEquals($importedcourse->fullname, $c["course"]["fullname"]);
            $this->assertEquals($importedcourse->shortname, $c["course"]["shortname"]);
            $this->assertEquals($importedcourse->visible, 1);
            $this->assertEquals($category->name, $c["course_categories"]["name"]);

            // Check that course creation was logged.
            $logitem = $DB->get_record($courseimporter->logtable,
                    array("course_idnumber" => $c["course"]["idnumber"], "pathitemid" => $this->pathitemid));
            $this->assertEquals($logitem->course_fullname, $c["course"]["fullname"]);
            $this->assertEquals($logitem->course_shortname, $c["course"]["shortname"]);
            $this->assertEquals($logitem->course_categories_name, $c["course_categories"]["name"]);
        }

        // Update visible default and test that the course is hidden.
        $visibledefault = $DB->get_record(self::DB_SETTINGS,
                array("pathitemid" => $this->pathitemid, "name" => 'course_visible'));
        $visibledefault->value = get_string('hide');
        $DB->update_record(self::DB_SETTINGS, $visibledefault);
        // Create another course with new setting.
        $anothercourse = array();
        $anothercourse[] = array(
                'course' => array(
                        'fullname' => 'XYZ full',
                        'shortname' => 'XYZ short',
                        'idnumber' => 'XYZ idnumber'
                ),
                'course_categories' => array(
                        'name' => 'XYZ cat'
                )
        );
        $sortedcourses = $courseimporter->sort_items($anothercourse);
        $courseimporter->do_imports($sortedcourses);

        $importedcourse = $DB->get_record("course", array("idnumber" => 'XYZ idnumber'));
        $this->assertEquals(0, $importedcourse->visible);
    }

    public function test_update_courses() {
        global $DB;

        $courseimporter = new importers_course_importer($this->pathitemid);
        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses); // Create the initial courses.

        // Create some updates for the existing courses.
        $this->courses[0]["course"]["fullname"] = "Updated course fullname";
        $this->courses[1]["course"]["shortname"] = "Updated course shortname";
        $this->courses[2]["course_categories"]["name"] = "Updated course category";
        $this->courses[2]["course"]["fullname"] = "Another updated fullname";

        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses); // Import the updates onto existing courses.

        foreach ($this->courses as $c) {

            // Check Moodle course modified.
            $importedcourse = $DB->get_record("course", array("idnumber" => $c["course"]["idnumber"]));
            $category = $DB->get_record("course_categories", array("id" => $importedcourse->category));
            $this->assertEquals($importedcourse->fullname, $c["course"]["fullname"]);
            $this->assertEquals($importedcourse->shortname, $c["course"]["shortname"]);
            $this->assertEquals($category->name, $c["course_categories"]["name"]);

            // Check that the log has been updated.
            $logitem = $DB->get_record(self::DB_LOCAL_LOG,
                    array("course_idnumber" => $c["course"]["idnumber"], "pathitemid" => $this->pathitemid));
            $this->assertEquals($logitem->course_fullname, $c["course"]["fullname"]);
            $this->assertEquals($logitem->course_shortname, $c["course"]["shortname"]);
            $this->assertEquals($logitem->course_categories_name, $c["course_categories"]["name"]);
        }
    }

    public function test_delete_courses() {
        global $DB;
        // This test produced output when a course is deleted so will be classed as 'risky'.

        $courseimporter = new importers_course_importer($this->pathitemid);
        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses); // Create the initial courses.

        $coursecountstart = $DB->count_records("course");

        // Remove two out of the three courses to import.
        $deleting = $this->courses;
        unset($deleting[0]);
        unset($deleting[2]);

        $sortedcourses = $courseimporter->sort_items($deleting);
        $courseimporter->do_imports($sortedcourses); // Import the data to delete two existing courses.

        $coursecountend = $DB->count_records("course");
        $this->assertEquals(2, ($coursecountstart - $coursecountend));

        $deletedinlog = $DB->count_records(self::DB_LOCAL_LOG, array("deleted" => 1, "pathitemid" => $this->pathitemid));
        $this->assertEquals(2, $deletedinlog);

        // Try recreating the two courses.
        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses); // Restore back to original three course.
        $coursecountend = $DB->count_records("course");
        $this->assertEquals(0, ($coursecountstart - $coursecountend));

        $deletedinlog = $DB->count_records(self::DB_LOCAL_LOG, array("deleted" => 1, "pathitemid" => $this->pathitemid));
        $this->assertEquals(0, $deletedinlog);

        $totalinlog = $DB->count_records(self::DB_LOCAL_LOG, array("pathitemid" => $this->pathitemid));
        $this->assertEquals(3, $totalinlog);
    }

    public function test_import_nulls() {
        global $DB;

        $this->courses[1]["course"]["shortname"] = null;
        $this->courses[2]["course_categories"]["name"] = null;
        $this->courses[0]["course"]["fullname"] = null;

        $courseimporter = data_importer_entity_importer::get_importer($this->pathitemid);
        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses);

        $exceptions = $DB->get_records("local_data_importer_errors");
        $this->assertEquals(3, count($exceptions));
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

        $courseimporter = new importers_course_importer($this->pathitemid);
        $sortedcourses = $courseimporter->sort_items($this->courses);
        $courseimporter->do_imports($sortedcourses);

        $exceptions = $DB->get_records("local_data_importer_errors");
        $this->assertEquals(3, count($exceptions));
    }
}