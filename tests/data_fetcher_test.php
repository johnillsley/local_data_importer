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

require_once($CFG->dirroot . '/local/data_importer/importers/entity_importer.php');
require_once($CFG->dirroot . '/local/data_importer/tests/fixtures/importer_test.php');

class local_data_importer_data_fetcher_test extends advanced_testcase {

    private $pathitemid;

    public function setUp() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        // Connector and pathitem required so that data_fetcher can be instantiated.
        // Their methods/properties are not required for testing.
        // Also need the tests/fixtures/importer_test.php but also just to instantiate.

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

    public function test_map_to_web_service_parameters() {
        global $DB;

        $time = 1234;
        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);

        // Start with just one mapping.
        $insert = array(
                "pathitemid" => $this->pathitemid,
                "pathitemparameter" => "webservice_param1",
                "subpluginparameter" => "subplugin_param1",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $DB->insert_record("local_data_importer_params", $insert);

        $externalparameters = $datafetcher->map_to_web_service_parameters();
        $expected = array(
                array("webservice_param1" => "value1"),
                array("webservice_param1" => ""),
                array("webservice_param1" => "value9"),
        );
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.

        // With just the one mapping check the array of parameters are transformed and unique.
        $this->assertEquals($externalparameters, $expected);

        // Now add a second mapping.
        $insert = array(
                "pathitemid" => $this->pathitemid,
                "pathitemparameter" => "webservice_param2",
                "subpluginparameter" => "subplugin_param2",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $DB->insert_record("local_data_importer_params", $insert);

        $externalparameters = $datafetcher->map_to_web_service_parameters();
        $expected = array(
                array("webservice_param1" => "value1", "webservice_param2" => "value2"),
                array("webservice_param1" => "", "webservice_param2" => "value7"),
                array("webservice_param1" => "value9", "webservice_param2" => "value3"),
        );
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.

        // With two mappings check the array of parameters are transformed and unique.
        $this->assertEquals($externalparameters, $expected);

        // Now add a second mapping.
        $insert = array(
                "pathitemid" => $this->pathitemid,
                "pathitemparameter" => "webservice_param3",
                "subpluginparameter" => "subplugin_param3",
                "timecreated" => $time,
                "timemodified" => $time
        );
        $DB->insert_record("local_data_importer_params", $insert);

        $externalparameters = $datafetcher->map_to_web_service_parameters();
        $expected = array(
                array("webservice_param1" => "value1", "webservice_param2" => "value2", "webservice_param3" => "value9"),
                array("webservice_param1" => "value1", "webservice_param2" => "value2", "webservice_param3" => "value6"),
                array("webservice_param1" => "", "webservice_param2" => "value7", "webservice_param3" => "value8"),
                array("webservice_param1" => "value9", "webservice_param2" => "value3"),
        );
        $externalparameters = array_values($externalparameters); // Ignore the array keys for assertion.

        // With three mappings check the array of parameters are transformed and unique.
        $this->assertEquals($externalparameters, $expected);
    }

    public function test_build_relativeuri() {

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

    public function test_transform_data() {
        global $CFG, $DB;

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
        $method = $reflector->getMethod("transform_data");
        $method->setAccessible(true);

        $datafetcher = new local_data_importer_data_fetcher($this->pathitemid);
        $interanldata = $method->invoke($datafetcher, $externaldata);

        $this->assertEquals($interanldata, $expected);
    }
}