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
 * Unit tests for the local/data_importer/classes/data_fetcher.php.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/data_importer/tests/fixtures/importer_test.php');

/**
 * Class local_data_importer_data_fetcher_testcase
 */
class local_data_importer_data_fetcher_testcase extends advanced_testcase {

    /**
     * @var integer
     */
    private $pathitemid;

    /**
     * Create a connector and pathitem instance.
     * Connector and pathitem required so that data_fetcher can be instantiated.
     * Their methods/properties are not required for testing.
     * Also need the tests/fixtures/importer_test.php to instantiate and test_get_parameter_mappings().
     */
    public function setUp() {

        $this->resetAfterTest(true);

        // Create test connector instance.
        $connector = new local_data_importer_connectorinstance();
        $connector->set_name("Test connector");
        $connector->set_description("Test connector");
        $connector->set_openapidefinitionurl("Test openAPI url");
        $connector->set_openapi_key("Test openAPI key");
        $connector->set_server("Test server");
        $connector->set_server_apikey("Test server key");
        $connector->set_timecreated(1234);
        $connector->set_timemodified(1234);
        $connectorid = $connector->save(true);

        // Create test pathitem instance.
        $pathitem = new local_data_importer_connectorpathitem();
        $pathitem->set_name("Test importer");
        $pathitem->set_connector_id($connectorid); // No need to create a new connector instance (?).
        $pathitem->set_path_item("/FUNCTION/PARAM1/{value1}/PARAM2/{value2}/PARAM3/{value3}");
        $pathitem->set_active(true);
        $pathitem->set_http_method('GET');
        $pathitem->set_plugin_component('importers_test');
        $this->pathitemid = $pathitem->save(true);
    }

    /**
     * Test for method local_data_importer_data_fetcher->update_from_pathitem().
     */
    public function test_update_from_pathitem() {

        // Top level importer function. Not easy to unit test?
        // TODO - Look to see if this can be unit tested.
    }

    /**
     * Test for method local_data_importer_data_fetcher->transform_parameters().
     */
    public function test_transform_parameters() {

        // See test importer.... data_importer/tests/fixtures/importer_test.php.

        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);

        // Start with just one mapping.
        $transform = new stdClass();
        $transform->subpluginparams = array(
                "subplugin_param1" => "webservice_param1"
        );
        $transform->globalparams = array();
        $externalparameters = $datafetcher->transform_parameters($transform);
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.
        $expected = array(
                array("webservice_param1" => "value1"),
                array("webservice_param1" => ""),
                array("webservice_param1" => "value9"),
        );
        // With just the one mapping check the array of parameters are transformed and unique.
        $this->assertEquals($externalparameters, $expected);

        // Now add a second mapping.
        $transform = new stdClass();
        $transform->subpluginparams = array(
                "subplugin_param1" => "webservice_param1",
                "subplugin_param2" => "webservice_param2"
        );
        $transform->globalparams = array();
        $externalparameters = $datafetcher->transform_parameters($transform);
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.
        $expected = array(
                array("webservice_param1" => "value1", "webservice_param2" => "value2"),
                array("webservice_param1" => "", "webservice_param2" => "value7"),
                array("webservice_param1" => "value9", "webservice_param2" => "value3"),
        );
        // With two mappings check the array of parameters are transformed and unique.
        $this->assertEquals($externalparameters, $expected);

        // Now add a third mapping.
        $transform = new stdClass();
        $transform->subpluginparams = array(
                "subplugin_param1" => "webservice_param1",
                "subplugin_param2" => "webservice_param2",
                "subplugin_param3" => "webservice_param3"
        );
        $transform->globalparams = array();
        $externalparameters = $datafetcher->transform_parameters($transform);
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.
        $expected = array(
                array("webservice_param1" => "value1", "webservice_param2" => "value2", "webservice_param3" => "value9"),
                array("webservice_param1" => "value1", "webservice_param2" => "value2", "webservice_param3" => "value6"),
                array("webservice_param1" => "", "webservice_param2" => "value7", "webservice_param3" => "value8"),
                array("webservice_param1" => "value9", "webservice_param2" => "value3")
        );
        // With three mappings check the array of parameters are transformed and unique.
        $this->assertEquals($externalparameters, $expected);

        // Now add a global parameter.
        $transform = new stdClass();
        $transform->subpluginparams = array(
                "subplugin_param1" => "webservice_param1",
                "subplugin_param2" => "webservice_param2"
        );
        $transform->globalparams = array(
                array("webservice_param3" => "ABC"),
                array("webservice_param3" => "DEF")
        );
        $externalparameters = $datafetcher->transform_parameters($transform);
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.
        $expected = array(
                array("webservice_param1" => "value1", "webservice_param2" => "value2", "webservice_param3" => "ABC"),
                array("webservice_param1" => "value1", "webservice_param2" => "value2", "webservice_param3" => "DEF"),
                array("webservice_param1" => "", "webservice_param2" => "value7", "webservice_param3" => "ABC"),
                array("webservice_param1" => "", "webservice_param2" => "value7", "webservice_param3" => "DEF"),
                array("webservice_param1" => "value9", "webservice_param2" => "value3", "webservice_param3" => "ABC"),
                array("webservice_param1" => "value9", "webservice_param2" => "value3", "webservice_param3" => "DEF"),
        );
        $this->assertEquals($externalparameters, $expected);
    }

    /**
     * Test for method local_data_importer_data_fetcher->get_global_param_combinations().
     */
    public function test_get_global_param_combinations() {

        // Private method - get_global_param_combinations().
        $globalparams = array(
                "year" => array("A1", "A2", "A3"),
                "psl" => array("B1", "B2", "B3"),
                "other" => array("C1", "C2")
        );

        $reflector = new ReflectionClass("local_data_importer_data_fetcher");
        $method = $reflector->getMethod("get_global_param_combinations");
        $method->setAccessible(true);

        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);
        $combined = $method->invoke($datafetcher, $globalparams);

        $expected = array(
                array("year" => "A1", "psl" => "B1", "other" => "C1"),
                array("year" => "A1", "psl" => "B1", "other" => "C2"),
                array("year" => "A1", "psl" => "B2", "other" => "C1"),
                array("year" => "A1", "psl" => "B2", "other" => "C2"),
                array("year" => "A1", "psl" => "B3", "other" => "C1"),
                array("year" => "A1", "psl" => "B3", "other" => "C2"),
                array("year" => "A2", "psl" => "B1", "other" => "C1"),
                array("year" => "A2", "psl" => "B1", "other" => "C2"),
                array("year" => "A2", "psl" => "B2", "other" => "C1"),
                array("year" => "A2", "psl" => "B2", "other" => "C2"),
                array("year" => "A2", "psl" => "B3", "other" => "C1"),
                array("year" => "A2", "psl" => "B3", "other" => "C2"),
                array("year" => "A3", "psl" => "B1", "other" => "C1"),
                array("year" => "A3", "psl" => "B1", "other" => "C2"),
                array("year" => "A3", "psl" => "B2", "other" => "C1"),
                array("year" => "A3", "psl" => "B2", "other" => "C2"),
                array("year" => "A3", "psl" => "B3", "other" => "C1"),
                array("year" => "A3", "psl" => "B3", "other" => "C2"),
        );
        $this->assertEquals($combined, $expected);
    }

    /**
     * Test for method local_data_importer_data_fetcher->build_relativeuri().
     */
    public function test_build_relativeuri() {

        // Private method - build_relativeuri().
        $reflector = new ReflectionClass("local_data_importer_data_fetcher");
        $property = $reflector->getProperty("uritemplate");
        $property->setAccessible(true);
        $method = $reflector->getMethod("build_relativeuri");
        $method->setAccessible(true);

        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);
        $uritemplate = $property->getValue($datafetcher);

        $params = array(
                "value1" => "ABC",
                "value2" => "DEF",
                "value3" => "GHI"
        );
        $url = $method->invoke($datafetcher, $uritemplate, $params);
        $this->assertEquals($url, "/FUNCTION/PARAM1/ABC/PARAM2/DEF/PARAM3/GHI");

        // Test the exceptions.
        $params = array(
                "value1" => "ABC",
                "value2" => "DEF"
        );
        try {
            $url = $method->invoke($datafetcher, $uritemplate, $params);
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), "The relative URI has missing values.");
        }

        $params = array(
                "value1" => "ABC",
                "value2" => "",
                "value3" => "GHI"
        );
        try {
            $url = $method->invoke($datafetcher, $uritemplate, $params);
        } catch (Exception $e) {
            $this->assertEquals($e->getMessage(), "URL parameter (value2) is empty string.");
        }
    }

    /**
     * Test for method local_data_importer_data_fetcher->get_parameter_mappings().
     */
    public function test_get_parameter_mappings() {
        global $DB;

        // Private method - get_parameter_mappings().

        $reflector = new ReflectionClass("local_data_importer_data_fetcher");
        $method = $reflector->getMethod("get_parameter_mappings");
        $method->setAccessible(true);

        // Need settings for global parameter.
        $testfirstday = date('m/d', time() - 60 * 60 * 24); // Set first day of academic year to yesterday.
        set_config('academic_year_format', 'yyyy-yy', 'local_data_importer');
        set_config('academic_year_first_day', $testfirstday, 'local_data_importer');

        $yesterday = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
        $tomorrow = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
        $DB->insert_records("local_data_importer_dates", array(
                        ['period_code' => 'AY',
                                'acyear' => '2018/9',
                                'start_date' => $yesterday,
                                'end_date' => $tomorrow],
                        ['period_code' => 'S2',
                                'acyear' => '2018/9',
                                'start_date' => $yesterday,
                                'end_date' => $tomorrow],
                        ['period_code' => 'M08',
                                'acyear' => '2018/9',
                                'start_date' => $yesterday,
                                'end_date' => $tomorrow]
                )
        );

        // Configure settings to use sits_period table used by sits plugin.
        set_config('date_interval_table', 'local_data_importer_dates', 'local_data_importer');
        set_config('date_interval_code_field', 'period_code', 'local_data_importer');
        set_config('date_interval_start_date_field', 'start_date', 'local_data_importer');
        set_config('date_interval_end_date_field', 'end_date', 'local_data_importer');

        // Set up some mappings.
        $mapping1 = new stdClass();
        $mapping1->pathitemid = $this->pathitemid;
        $mapping1->pathitemparameter = 'webservice_param1';
        $mapping1->subpluginparameter = 'subplugin_param1';
        $mapping1->timecreated = 1234;
        $mapping1->timemodified = 1234;
        $mapping2 = new stdClass();
        $mapping2->pathitemid = $this->pathitemid;
        $mapping2->pathitemparameter = 'webservice_param2';
        $mapping2->subpluginparameter = 'subplugin_param2';
        $mapping2->timecreated = 1234;
        $mapping2->timemodified = 1234;
        $mapping3 = new stdClass();
        $mapping3->pathitemid = $this->pathitemid;
        $mapping3->pathitemparameter = 'webservice_param3';
        $mapping3->subpluginparameter = 'global_current_academic_year';
        $mapping3->timecreated = 1234;
        $mapping3->timemodified = 1234;
        $mapping4 = new stdClass();
        $mapping4->pathitemid = $this->pathitemid;
        $mapping4->pathitemparameter = 'webservice_param4';
        $mapping4->subpluginparameter = 'global_date_interval_codes';
        $mapping4->timecreated = 1234;
        $mapping4->timemodified = 1234;

        $DB->insert_records('local_data_importer_params', array($mapping1, $mapping2, $mapping3, $mapping4));

        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);
        $mappings = $method->invoke($datafetcher);

        $expected = new stdClass();
        $expected->subpluginparams = array(
                "subplugin_param1" => "webservice_param1",
                "subplugin_param2" => "webservice_param2"
        );
        $acadyear = date("Y") . "-" . (substr(date("Y"), -2) + 1);
        $expected->globalparams = array(
                array("webservice_param3" => $acadyear, "webservice_param4" => "AY"),
                array("webservice_param3" => $acadyear, "webservice_param4" => "S2"),
                array("webservice_param3" => $acadyear, "webservice_param4" => "M08"),
        );
        $this->assertEquals($mappings, $expected);
    }

    /**
     * Test for method local_data_importer_data_fetcher->check_uri().
     */
    public function test_check_uri() {

        // Private method - check_uri().
        // This is tested under test_build_relativeuri().
    }

    /**
     * Test for method local_data_importer_data_fetcher->transform_response().
     */
    public function test_transform_response() {
        global $CFG, $DB;

        // Private method - transform_response().

        $time = 1234;
        // Add some response mappings.
        $inserts = array();
        $inserts[] = array(
                "pathitemid" => $this->pathitemid,
                "pathitemresponse" => "COURSENAME",
                "pluginresponsetable" => "course",
                "pluginresponsefield" => "fullname",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $inserts[] = array(
                "pathitemid" => $this->pathitemid,
                "pathitemresponse" => "REFCODE",
                "pluginresponsetable" => "course",
                "pluginresponsefield" => "shortname",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $inserts[] = array(
                "pathitemid" => $this->pathitemid,
                "pathitemresponse" => "UNITCODE",
                "pluginresponsetable" => "course",
                "pluginresponsefield" => "idnumber",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $inserts[] = array(
                "pathitemid" => $this->pathitemid,
                "pathitemresponse" => "DEPARTMENT",
                "pluginresponsetable" => "course_categories",
                "pluginresponsefield" => "name",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $DB->insert_records("pathitem_response", $inserts);

        $externaldata = json_decode(file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/courses.json'));

        $expected = array(
                array(
                        'course' => array(
                                'fullname' => 'Course 101',
                                'shortname' => 'C101',
                                'idnumber' => '1234'
                        ),
                        'course_categories' => array(
                                'name' => 'Engineering'
                        )
                ),
                array(
                        'course' => array(
                                'fullname' => 'Health & Safety',
                                'shortname' => 'H&S',
                                'idnumber' => '5678'
                        ),
                        'course_categories' => array(
                                'name' => 'Staff development'
                        )
                ),
        );

        $reflector = new ReflectionClass("local_data_importer_data_fetcher");
        $method = $reflector->getMethod("transform_response");
        $method->setAccessible(true);

        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);
        $interanldata = $method->invoke($datafetcher, $externaldata);

        $this->assertEquals($interanldata, $expected);
    }

    /**
     * Test for method local_data_importer_data_fetcher->array_flatten().
     */
    public function test_array_flatten() {
        global $CFG;

        // Private method - array_flatten().

        $reflector = new ReflectionClass("local_data_importer_data_fetcher");
        $method = $reflector->getMethod("array_flatten");
        $method->setAccessible(true);

        $structure = json_decode(file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/courses.json'));
        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);
        $flattened = $method->invoke($datafetcher, $structure);

        $expected = array(
                array(
                        "DEPARTMENT" => "Engineering",
                        "DEPTCODE" => "Eng",
                        "ABC" => "XYZ",
                        "COURSENAME" => "Course 101",
                        "REFCODE" => "C101",
                        "INSTANCE" => "A",
                        "YEAR" => "2018/9",
                        "UNITCODE" => "1234"
                ),
                array(
                        "DEPARTMENT" => "Staff development",
                        "DEPTCODE" => "Staffdev",
                        "ABC" => "XYZ",
                        "COURSENAME" => "Health & Safety",
                        "REFCODE" => "H&S",
                        "INSTANCE" => "B",
                        "YEAR" => "2017/8",
                        "UNITCODE" => "5678"
                )
        );
        $this->assertEquals($flattened, $expected);
    }
}