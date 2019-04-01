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
 * Unit tests for the local/data_importer/importers/entity_importer.php.
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
require_once($CFG->dirroot.'/local/data_importer/tests/fixtures/importer_test.php'); // This is the test subplugin.

/**
 * Class local_data_importer_entity_importer_testcase
 */
class local_data_importer_entity_importer_testcase extends advanced_testcase {

    const DB_SETTINGS = 'local_data_importer_setting';

    /**
     * @var string
     */
    private $dbtablename = "local_data_importer_test";

    /**
     * @var object xmldb_table
     */
    private $xmldbtable;

    /**
     * @var integer
     */
    private $pathitemid;

    /**
     * Create a connector and a temporary db table for the test sub plugin local log.
     */
    public function setUp() {
        global $DB;

        $this->resetAfterTest(true);

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description');
        $connectorinstance->set_name('Connector Instance Name');
        $connectorinstance->set_server_apikey('serverapikey');
        $connectorinstance->set_openapi_key('openapikey');
        $connectorinstance->set_server('virtserver.swaggerhub.com');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $connectorid = $connectorinstance->save(true);

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 1');
        $connectorpathitem->set_connector_id($connectorid);
        $connectorpathitem->set_path_item('/pathitem1');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test');
        $this->pathitemid = $connectorpathitem->save(true);

        $this->xmldbtable = new xmldb_table($this->dbtablename);
        $this->xmldbtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $this->xmldbtable->add_field('pathitemid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('course_fullname', XMLDB_TYPE_CHAR, 254, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('course_shortname', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('course_idnumber', XMLDB_TYPE_CHAR, 100, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('course_categories_name', XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('timecreated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('timemodified', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, null);
        $this->xmldbtable->add_field('deleted', XMLDB_TYPE_INTEGER, 3, null, XMLDB_NOTNULL, null, '0');
        $this->xmldbtable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman = $DB->get_manager();
        $status = $dbman->create_table($this->xmldbtable);
    }

    /**
     * Drop the temporary db table for the test sub plugin local log.
     */
    public function tearDown() {
        global $DB;

        $dbman = $DB->get_manager();
        $dbman->drop_table($this->xmldbtable);
    }

    /**
     * Test for method local_data_importer_entity_importer->get_importer().
     */
    public function test_get_importer() {

        $importer = \data_importer_entity_importer::get_importer($this->pathitemid);
        $this->assertInstanceOf(\importers_test_importer::class, $importer);
    }

    /**
     * Test for method local_data_importer_entity_importer->get_plugin_name().
     */
    public function test_get_plugin_name() {

        // There is no language file for the test importer.
        // This should be tested at the subplugin level for each subplugin.
    }

    /**
     * Test for method local_data_importer_entity_importer->get_parameter_options().
     */
    public function test_get_parameter_options() {

        $importer = new \importers_test_importer($this->pathitemid);
        $subpluginparams = $importer->get_parameters();
        $expected = array(
                array("subplugin_param1" => "value1", "subplugin_param2" => "value2", "subplugin_param3" => "value9"),
                array("subplugin_param1" => "value1", "subplugin_param2" => "value2", "subplugin_param3" => "value6"),
                array("subplugin_param1" => "", "subplugin_param2" => "value7", "subplugin_param3" => "value8"),
                array("subplugin_param1" => "value9", "subplugin_param2" => "value3")
        );
        $this->assertSame($subpluginparams, $expected);
    }

    /**
     * Test for method local_data_importer_entity_importer->do_imports().
     */
    public function test_do_imports() {
        global $DB;

        $item1 = array(
                'course' => array('fullname' => 'Course1', 'shortname' => 'CS1', 'idnumber' => 'C1'),
                'course_categories' => array('name' => 'Dept1')
        );
        $item2 = array(
                'course' => array('fullname' => 'Course2', 'shortname' => 'CS2', 'idnumber' => 'C2'),
                'course_categories' => array('name' => 'Dept2')
        );
        $item3 = array(
                'course' => array('fullname' => 'Course3', 'shortname' => 'CS3', 'idnumber' => 'C3'),
                'course_categories' => array('name' => 'Dept2')
        );

        $importer = new \importers_test_importer($this->pathitemid);

        // All courses are to be added.
        $sorted = $importer->sort_items(array($item1, $item2, $item3));
        $expected = (object)array(
                'create' => array($item1, $item2, $item3),
                'update' => array(),
                'delete' => array()
        );
        $this->assertEquals($sorted, $expected);
        $importer->do_imports($sorted);
        $records = $DB->get_records($this->dbtablename, array("deleted" => 0));
        $this->assertEquals(count($records), 3);

        // One course to be updated.
        $item3 = array(
                'course' => array('fullname' => 'Course4', 'shortname' => 'CS4', 'idnumber' => 'C3'),
                'course_categories' => array('name' => 'Dept2')
        );
        $sorted = $importer->sort_items(array($item1, $item2, $item3));
        $expected = (object)array(
                'create' => array(),
                'update' => array($item3),
                'delete' => array()
        );
        $this->assertEquals($sorted, $expected);
        $importer->do_imports($sorted);
        $records = $DB->get_records($this->dbtablename, array("deleted" => 0));
        $this->assertEquals(count($records), 3);

        // Delete two items.
        $sorted = $importer->sort_items(array($item3));
        $expected = (object)array(
                'create' => array(),
                'update' => array(),
                'delete' => array($item1, $item2)
        );
        $importer->do_imports($sorted);
        $records = $DB->get_records($this->dbtablename, array("deleted" => 1));
        $this->assertEquals(count($records), 2);

        // Bring one of the deleted items back.
        $sorted = $importer->sort_items(array($item1, $item3));
        $expected = (object)array(
                'create' => array($item1),
                'update' => array(),
                'delete' => array()
        );
        $this->assertEquals($sorted, $expected);
        $importer->do_imports($sorted);
        $records = $DB->get_records($this->dbtablename, array("deleted" => 0));
        $this->assertEquals(count($records), 2);
    }

    /**
     * Test for method local_data_importer_entity_importer->sort_items().
     */
    public function test_sort_items() {

        // This is tested as part of test_do_imports().
    }

    /**
     * Test for method local_data_importer_entity_importer->get_unique_fields().
     */
    public function test_get_unique_fields() {

        // Private method - get_unique_fields().
        $reflector = new ReflectionClass("importers_test_importer");
        $method = $reflector->getMethod("get_unique_fields");
        $method->setAccessible(true);
        $importer = new \importers_test_importer($this->pathitemid);
        $uiquefields = $method->invoke($importer);
        $expected = array(
                (object)["table" => "course", "field" => "idnumber"],
                (object)["table" => "course_categories", "field" => "name"]
        );
        $this->assertEquals($uiquefields, $expected);
    }

    /**
     * Test for method local_data_importer_entity_importer->get_database_properties().
     */
    public function test_get_database_properties() {
        global $CFG;

        // Private method - get_database_properties().
        $reflector = new ReflectionClass("importers_test_importer");
        $method = $reflector->getMethod("get_database_properties");
        $method->setAccessible(true);
        // Private property - databaseproperties.
        $property = $reflector->getProperty("databaseproperties");
        $property->setAccessible(true);

        $importer = new \importers_test_importer($this->pathitemid);
        $method->invoke($importer);
        $dbproperties = $property->getValue($importer);

        $expected = array(
                "course" => array(
                        "fullname" => (object)array(
                                "data_type" => "varchar",
                                "column_type" => "varchar(254)",
                                "is_nullable" => "NO",
                                "column_default" => "",
                                "character_maximum_length" => "254",
                                "numeric_precision" => "",
                                "numeric_scale" => ""
                        ),
                        "shortname" => (object)array(
                                "data_type" => "varchar",
                                "column_type" => "varchar(255)",
                                "is_nullable" => "NO",
                                "column_default" => "",
                                "character_maximum_length" => "255",
                                "numeric_precision" => "",
                                "numeric_scale" => ""
                        ),
                        "idnumber" => (object)array(
                                "data_type" => "varchar",
                                "column_type" => "varchar(100)",
                                "is_nullable" => "NO",
                                "column_default" => "",
                                "character_maximum_length" => "100",
                                "numeric_precision" => "",
                                "numeric_scale" => ""
                        ),
                ),
                "course_categories" => array(
                        "name" => (object)array(
                                "data_type" => "varchar",
                                "column_type" => "varchar(255)",
                                "is_nullable" => "NO",
                                "column_default" => "",
                                "character_maximum_length" => "255",
                                "numeric_precision" => "",
                                "numeric_scale" => ""
                        )
                )
        );
        $this->assertEquals($dbproperties, $expected);
    }

    /**
     * Test for method local_data_importer_entity_importer->validate_item().
     */
    public function test_validate_item() {

        // Private method - get_database_properties().
        // Private method - validate_item().
        $reflector = new ReflectionClass("importers_test_importer");
        $method1 = $reflector->getMethod("get_database_properties");
        $method1->setAccessible(true);
        $method2 = $reflector->getMethod("validate_item");
        $method2->setAccessible(true);

        $importer = new \importers_test_importer($this->pathitemid);
        $method1->invoke($importer);

        $input = array(
                'course' => array('fullname' => 'Course1', 'shortname' => 'CS1', 'idnumber' => 'C1'),
                'course_categories' => array('name' => 'Dept1')
        );
        $output = $method2->invoke($importer, $input);
        $this->assertEquals($input, $output);
    }

    /**
     * Test for method local_data_importer_entity_importer->validate_field().
     */
    public function test_validate_field() {

        // See test_validate_errors_field & test_validate_success_field below.
        // These test for many success and failure scenarios .
    }

    /**
     * Test for method local_data_importer_entity_importer->validate_field().
     * @dataProvider validate_field_errors_provider
     */
    public function test_validate_errors_field($fieldmetadata, $value, $required, $truncatestrings, $errormessage) {

        // Private method - validate_field.
        $reflector = new ReflectionClass("importers_test_importer");
        $method = $reflector->getMethod("validate_field");
        $method->setAccessible(true);

        try {
            $importer = new \importers_test_importer($this->pathitemid);
            $response = $method->invoke($importer, $fieldmetadata, $value, $required, $truncatestrings);

        } catch (Exception $e) {
            // Put the assertion under 'finally' so that it will force an error if no exception is thrown.
        } finally {
            $this->assertEquals($errormessage, $e->getMessage());
        }
    }

    /**
     * Test for method local_data_importer_entity_importer->validate_field().
     * @dataProvider validate_field_success_provider
     */
    public function test_validate_success_field($fieldmetadata, $value, $required, $truncatestrings, $expected) {

        // Private method - validate_field.
        $reflector = new ReflectionClass("importers_test_importer");
        $method = $reflector->getMethod("validate_field");
        $method->setAccessible(true);

        $importer = new \importers_test_importer($this->pathitemid);
        $response = $method->invoke($importer, $fieldmetadata, $value, $required, $truncatestrings);

        $this->assertSame($response, $expected);
    }

    /**
     * This is a data provider.
     */
    public function validate_field_errors_provider() {

        $fieldmetadata = $this->get_fieldmetadata();

        return [
                [$fieldmetadata['madeuptype'], 1, true, false, "DATA VALIDATION ERROR: field of type 'madeuptype' cannot be validated."],
                [$fieldmetadata['tinyint'], null, false, false, 'DATA VALIDATION ERROR: value is null but db field does not allow null values.'],
                [$fieldmetadata['tinyint'], null, true, false, 'DATA VALIDATION ERROR: subplugin defines that this field cannot be null.'],
                [$fieldmetadata['tinyint'], "128", true, false, 'DATA VALIDATION ERROR: value is outside allowable range for tinyint'],
                [$fieldmetadata['tinyint'], -129, true, false, 'DATA VALIDATION ERROR: value is outside allowable range for tinyint'],
                [$fieldmetadata['tinyint'], "ABC123", true, false, 'DATA VALIDATION ERROR: value is not integer for field tinyint'],

                [$fieldmetadata['smallint'], 65536, true, false, 'DATA VALIDATION ERROR: value is outside allowable range for smallint'],
                [$fieldmetadata['smallint'], "-1", true, false, 'DATA VALIDATION ERROR: value is outside allowable range for smallint'],

                [$fieldmetadata['mediumint'], "8388608", true, false, 'DATA VALIDATION ERROR: value is outside allowable range for mediumint'],
                [$fieldmetadata['mediumint'], -8388609, true, false, 'DATA VALIDATION ERROR: value is outside allowable range for mediumint'],

                [$fieldmetadata['int'], 2147483648, true, false, 'DATA VALIDATION ERROR: value is outside allowable range for int'],
                [$fieldmetadata['int'], -2147483649, true, false, 'DATA VALIDATION ERROR: value is outside allowable range for int'],

                [$fieldmetadata['varchar'], 'ABCDEFGHIJKLMNOPQRSTU', true, false, 'DATA VALIDATION ERROR: string has too many characters for database field varchar(20).'],
                [$fieldmetadata['varchar'], '', true, true, 'DATA VALIDATION ERROR: empty string for a field that the subplugin specifies as required.'],
                [$fieldmetadata['char'], null, false, true, 'DATA VALIDATION ERROR: value is null but db field does not allow null values.'],
                [$fieldmetadata['tinytext'], null, true, true, 'DATA VALIDATION ERROR: subplugin defines that this field cannot be null.'],
                [$fieldmetadata['tinytext'], null, false, true, 'DATA VALIDATION ERROR: value is null but db field does not allow null values.'],
                [$fieldmetadata['tinytext'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ
                        ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ'
                    , false, false, 'DATA VALIDATION ERROR: string has too many characters for database field tinytext(255).'],
                [$fieldmetadata['text'], null, true, true, 'DATA VALIDATION ERROR: subplugin defines that this field cannot be null.'],
                [$fieldmetadata['text'], null, false, true, 'DATA VALIDATION ERROR: value is null but db field does not allow null values.'],
                [$fieldmetadata['mediumtext'], null, true, true, 'DATA VALIDATION ERROR: subplugin defines that this field cannot be null.'],
                [$fieldmetadata['mediumtext'], null, false, true, 'DATA VALIDATION ERROR: value is null but db field does not allow null values.'],
                [$fieldmetadata['longtext'], null, true, true, 'DATA VALIDATION ERROR: subplugin defines that this field cannot be null.'],

                [$fieldmetadata['float'], null, true, true, 'DATA VALIDATION ERROR: subplugin defines that this field cannot be null.'],
                [$fieldmetadata['float'], 3.001, true, false, "DATA VALIDATION ERROR: floating point value out of range."],
                [$fieldmetadata['float'], 'ABC', true, false, "DATA VALIDATION ERROR: value is not a floating point number but db field is type float"],
                [$fieldmetadata['double'], 123.45, false, false, "DATA VALIDATION ERROR: floating point value out of range."],
                [$fieldmetadata['decimal'], "1234.567", false, false, "DATA VALIDATION ERROR: floating point value out of range."],
                [$fieldmetadata['decimal'], 1234.567, false, false, "DATA VALIDATION ERROR: floating point value out of range."],
                [$fieldmetadata['decimal'], '123ABC', false, false, "DATA VALIDATION ERROR: value is not a floating point number but db field is type decimal"],
        ];
    }

    /**
     * This is a data provider.
     */
    public function validate_field_success_provider() {

        $fieldmetadata = $this->get_fieldmetadata();

        return [
                [$fieldmetadata['tinyint'], 127, false, false, 127],
                [$fieldmetadata['tinyint'], "-128", false, false, -128],
                [$fieldmetadata['tinyint'], 0, true, false, 0],

                [$fieldmetadata['smallint'], 65535, false, false, 65535],
                [$fieldmetadata['smallint'], "0", true, false, 0],
                [$fieldmetadata['smallint'], null, false, false, null],

                [$fieldmetadata['mediumint'], 8388607, false, false, 8388607],
                [$fieldmetadata['mediumint'], "-8388608", false, false, -8388608],

                [$fieldmetadata['int'], 2147483647, false, false, 2147483647],
                [$fieldmetadata['int'], -2147483648, false, false, -2147483648],

                [$fieldmetadata['bigint'], 1, false, false, 1],
                [$fieldmetadata['bigint'], "-2", true, false, -2],
                [$fieldmetadata['bigint'], null, false, false, null],

                [$fieldmetadata['varchar'], 'ABCDEFGHIJKLMNOPQRSTU', true, true, 'ABCDEFGHIJKLMNOPQRST'],
                [$fieldmetadata['char'], 'ABCDEFGHIJKLMNOPQRST', true, false, 'ABCDEFGHIJKLMNOPQRST'],
                [$fieldmetadata['tinytext'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ'
                    , false, true, 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTU'],
                [$fieldmetadata['text'], 'ABCDEFGHIJKLMNOPQRST', true, false, 'ABCDEFGHIJKLMNOPQRST'],
                [$fieldmetadata['mediumtext'], 'ABCDEFGHIJKLMNOPQRST', true, false, 'ABCDEFGHIJKLMNOPQRST'],
                [$fieldmetadata['longtext'], 'ABCDEFGHIJKLMNOPQRST', true, false, 'ABCDEFGHIJKLMNOPQRST'],
                [$fieldmetadata['longtext'], null, false, false, null],

                [$fieldmetadata['float'], null, false, false, null],
                [$fieldmetadata['float'], 3, true, false, 3.00],
                [$fieldmetadata['float'], 3.999, true, true, 4.00],
                [$fieldmetadata['double'], null, false, false, null],
                [$fieldmetadata['double'], 123.4, false, false, 123.4],
                [$fieldmetadata['double'], -123.4, false, false, -123.4],
                [$fieldmetadata['double'], 123.49, false, true, 123.5],
                [$fieldmetadata['decimal'], "1234.56", false, false, 1234.56],
                [$fieldmetadata['decimal'], 1234.56, false, false, 1234.56],
                [$fieldmetadata['decimal'], 1234.567, false, true, 1234.57],
        ];
    }

    /**
     * Test for method local_data_importer_entity_importer->local_log().
     */
    public function test_local_log() {

        // This is tested as part of test_do_imports().
    }

    /**
     * Test for method local_data_importer_entity_importer->save_setting().
     */
    public function test_save_setting() {
        global $DB;

        $importer = new \importers_test_importer($this->pathitemid);
        $settingname = "my setting";
        $settingvalue = "do this";
        $importer->save_setting($settingname, $settingvalue);

        $records = $DB->get_records(self::DB_SETTINGS);
        $this->assertEquals(count($records), 1);
        $saved = array_pop($records);
        $this->assertEquals($saved->pathitemid, $this->pathitemid);
        $this->assertEquals($saved->name, $settingname);
        $this->assertEquals($saved->value, $settingvalue);
    }

    /**
     * Test for method local_data_importer_entity_importer->get_setting().
     */
    public function test_get_setting() {

        $importer = new \importers_test_importer($this->pathitemid);
        $settingname = "my setting";
        $settingvalue = "do this";
        $importer->save_setting($settingname, $settingvalue);

        $returnvalue = $importer->get_setting($settingname);
        $this->assertEquals($settingvalue, $returnvalue);
    }

    /**
     * Test for method local_data_importer_entity_importer->get_log_field().
     */
    public function test_get_log_field() {

        // Private method - get_log_field().
        $reflector = new ReflectionClass("importers_test_importer");
        $method = $reflector->getMethod("get_log_field");
        $method->setAccessible(true);
        $importer = new \importers_test_importer($this->pathitemid);

        $table = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $field = "1234567890";
        $logfield = $method->invoke($importer, $table, $field);
        $this->assertEquals($logfield, "ABCDEFGHIJKLMNOPQRSTUVWXYZ_123");
    }

    public function test_exception_log() {

        // TODO - this function should be in a different class?
    }

    /**
     * Example db field properties. For use with validation test.
     */
    private function get_fieldmetadata() {

        $fieldmetadata = array();

        $fieldmetadata['madeuptype'] = new stdClass();
        $fieldmetadata['madeuptype']->data_type = "madeuptype";
        $fieldmetadata['madeuptype']->character_maximum_length = "";
        $fieldmetadata['madeuptype']->numeric_precision = "";
        $fieldmetadata['madeuptype']->numeric_scale = "";
        $fieldmetadata['madeuptype']->is_nullable = "NO";
        $fieldmetadata['madeuptype']->column_type = "madeuptype";
        $fieldmetadata['madeuptype']->column_default = "";

        $fieldmetadata['tinyint'] = new stdClass();
        $fieldmetadata['tinyint']->data_type = "tinyint";
        $fieldmetadata['tinyint']->character_maximum_length = "";
        $fieldmetadata['tinyint']->numeric_precision = "";
        $fieldmetadata['tinyint']->numeric_scale = "";
        $fieldmetadata['tinyint']->is_nullable = "NO";
        $fieldmetadata['tinyint']->column_type = "tinyint(1)";
        $fieldmetadata['tinyint']->column_default = "";

        $fieldmetadata['smallint'] = new stdClass();
        $fieldmetadata['smallint']->data_type = "smallint";
        $fieldmetadata['smallint']->character_maximum_length = "";
        $fieldmetadata['smallint']->numeric_precision = "";
        $fieldmetadata['smallint']->numeric_scale = "";
        $fieldmetadata['smallint']->is_nullable = "YES";
        $fieldmetadata['smallint']->column_type = "smallint(4) unsigned";
        $fieldmetadata['smallint']->column_default = "0";

        $fieldmetadata['mediumint'] = new stdClass();
        $fieldmetadata['mediumint']->data_type = "mediumint";
        $fieldmetadata['mediumint']->character_maximum_length = "";
        $fieldmetadata['mediumint']->numeric_precision = "";
        $fieldmetadata['mediumint']->numeric_scale = "";
        $fieldmetadata['mediumint']->is_nullable = "NO";
        $fieldmetadata['mediumint']->column_type = "mediumint(5)";
        $fieldmetadata['mediumint']->column_default = "";

        $fieldmetadata['int'] = new stdClass();
        $fieldmetadata['int']->data_type = "int";
        $fieldmetadata['int']->character_maximum_length = "";
        $fieldmetadata['int']->numeric_precision = "";
        $fieldmetadata['int']->numeric_scale = "";
        $fieldmetadata['int']->is_nullable = "NO";
        $fieldmetadata['int']->column_type = "int(9)";
        $fieldmetadata['int']->column_default = "999";

        $fieldmetadata['bigint'] = new stdClass();
        $fieldmetadata['bigint']->data_type = "bigint";
        $fieldmetadata['bigint']->character_maximum_length = "";
        $fieldmetadata['bigint']->numeric_precision = "";
        $fieldmetadata['bigint']->numeric_scale = "";
        $fieldmetadata['bigint']->is_nullable = "YES";
        $fieldmetadata['bigint']->column_type = "bigint(19)";
        $fieldmetadata['bigint']->column_default = "999";

        $fieldmetadata['varchar'] = new stdClass();
        $fieldmetadata['varchar']->data_type = "varchar";
        $fieldmetadata['varchar']->character_maximum_length = "20";
        $fieldmetadata['varchar']->numeric_precision = "";
        $fieldmetadata['varchar']->numeric_scale = "";
        $fieldmetadata['varchar']->is_nullable = "YES";
        $fieldmetadata['varchar']->column_type = "varchar(20)";
        $fieldmetadata['varchar']->column_default = "";

        $fieldmetadata['char'] = new stdClass();
        $fieldmetadata['char']->data_type = "char";
        $fieldmetadata['char']->character_maximum_length = "20";
        $fieldmetadata['char']->numeric_precision = "";
        $fieldmetadata['char']->numeric_scale = "";
        $fieldmetadata['char']->is_nullable = "NO";
        $fieldmetadata['char']->column_type = "varchar(20)";
        $fieldmetadata['char']->column_default = "";

        $fieldmetadata['tinytext'] = new stdClass();
        $fieldmetadata['tinytext']->data_type = "tinytext";
        $fieldmetadata['tinytext']->character_maximum_length = "255";
        $fieldmetadata['tinytext']->numeric_precision = "";
        $fieldmetadata['tinytext']->numeric_scale = "";
        $fieldmetadata['tinytext']->is_nullable = "NO";
        $fieldmetadata['tinytext']->column_type = "tinytext";
        $fieldmetadata['tinytext']->column_default = "ABC";

        $fieldmetadata['text'] = new stdClass();
        $fieldmetadata['text']->data_type = "text";
        $fieldmetadata['text']->character_maximum_length = "65535";
        $fieldmetadata['text']->numeric_precision = "";
        $fieldmetadata['text']->numeric_scale = "";
        $fieldmetadata['text']->is_nullable = "NO";
        $fieldmetadata['text']->column_type = "text";
        $fieldmetadata['text']->column_default = "";

        $fieldmetadata['mediumtext'] = new stdClass();
        $fieldmetadata['mediumtext']->data_type = "mediumtext";
        $fieldmetadata['mediumtext']->character_maximum_length = "16777215";
        $fieldmetadata['mediumtext']->numeric_precision = "";
        $fieldmetadata['mediumtext']->numeric_scale = "";
        $fieldmetadata['mediumtext']->is_nullable = "NO";
        $fieldmetadata['mediumtext']->column_type = "mediumtext";
        $fieldmetadata['mediumtext']->column_default = "";

        $fieldmetadata['longtext'] = new stdClass();
        $fieldmetadata['longtext']->data_type = "longtext";
        $fieldmetadata['longtext']->character_maximum_length = "4294967295";
        $fieldmetadata['longtext']->numeric_precision = "";
        $fieldmetadata['longtext']->numeric_scale = "";
        $fieldmetadata['longtext']->is_nullable = "YES";
        $fieldmetadata['longtext']->column_type = "longtext";
        $fieldmetadata['longtext']->column_default = "ABC";

        $fieldmetadata['float'] = new stdClass();
        $fieldmetadata['float']->data_type = "float";
        $fieldmetadata['float']->character_maximum_length = "";
        $fieldmetadata['float']->numeric_precision = "3";
        $fieldmetadata['float']->numeric_scale = "2";
        $fieldmetadata['float']->is_nullable = "YES";
        $fieldmetadata['float']->column_type = "float";
        $fieldmetadata['float']->column_default = "";

        $fieldmetadata['double'] = new stdClass();
        $fieldmetadata['double']->data_type = "double";
        $fieldmetadata['double']->character_maximum_length = "";
        $fieldmetadata['double']->numeric_precision = 4;
        $fieldmetadata['double']->numeric_scale = 1;
        $fieldmetadata['double']->is_nullable = "YES";
        $fieldmetadata['double']->column_type = "double";
        $fieldmetadata['double']->column_default = 12.3;

        $fieldmetadata['decimal'] = new stdClass();
        $fieldmetadata['decimal']->data_type = "decimal";
        $fieldmetadata['decimal']->character_maximum_length = "";
        $fieldmetadata['decimal']->numeric_precision = 6;
        $fieldmetadata['decimal']->numeric_scale = 2;
        $fieldmetadata['decimal']->is_nullable = "NO";
        $fieldmetadata['decimal']->column_type = "decimal";
        $fieldmetadata['decimal']->column_default = "";

        /*
            Data types used in Moodle DB.
            =============================
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'varchar':
            case 'char':
            case 'tinytext': // 255 characters.
            case 'text': // 65535 characters.
            case 'mediumtext': // 16777215 characters.
            case 'longtext': // 4294967295 characters.
            case 'float':
            case 'double':
            case 'decimal':
        */
        return $fieldmetadata;
    }
}