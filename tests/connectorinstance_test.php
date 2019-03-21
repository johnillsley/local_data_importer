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
 * Unit tests for the local/data_importer/classes/connectorinstance.php.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_data_importer_connectorinstance_testcase
 */
class local_data_importer_connectorinstance_testcase extends advanced_testcase {

    public function setUp() {

        $this->resetAfterTest(true);
    }

    /**
     * Test for method local_data_importer_connectorinstance->save().
     */
    public function test_save() {
        global $DB;

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description');
        $connectorinstance->set_name('Connector Instance Name');
        $connectorinstance->set_server_apikey('serverapikey');
        $connectorinstance->set_openapi_key('openapikey');
        $connectorinstance->set_server('virtserver.swaggerhub.com');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $id = $connectorinstance->save(true);

        $connectorrecords = $DB->get_records($connectorinstance->get_dbtable());
        $this->assertEquals(count($connectorrecords), 1);

        $connectorrecord = array_pop($connectorrecords);
        $this->assertEquals($connectorrecord->name, 'Connector Instance Name');
        $this->assertEquals($connectorrecord->description, 'Connector Instance Description');
        $this->assertEquals($connectorrecord->openapikey, 'openapikey');
        $this->assertEquals($connectorrecord->server, 'virtserver.swaggerhub.com');
        $this->assertEquals($connectorrecord->serverapikey, 'serverapikey');
        $this->assertEquals($connectorrecord->openapidefinitionurl, $openapidefinitionurl);

        // Now do an update and test.
        $connectorinstance->set_description('ABCD');
        $connectorinstance->save(true);

        $connectorrecords = $DB->get_records($connectorinstance->get_dbtable());
        $this->assertEquals(count($connectorrecords), 1);

        $connectorrecord = array_pop($connectorrecords);
        $this->assertEquals($connectorrecord->description, 'ABCD');
    }

    /**
     * Test for method local_data_importer_connectorinstance->get_by_id().
     */
    public function test_get_by_id() {

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description');
        $connectorinstance->set_name('Connector Instance Name');
        $connectorinstance->set_server_apikey('serverapikey');
        $connectorinstance->set_openapi_key('openapikey');
        $connectorinstance->set_server('virtserver.swaggerhub.com');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $id = $connectorinstance->save(true);

        $connector = $connectorinstance->get_by_id($id);
        $this->assertInstanceOf(\local_data_importer_connectorinstance::class, $connector);
        $this->assertEquals($connector->get_description(), 'Connector Instance Description');
        $this->assertEquals($connector->get_name(), 'Connector Instance Name');
        $this->assertEquals($connector->get_server_apikey(), 'serverapikey');
        $this->assertEquals($connector->get_openapi_key(), 'openapikey');
        $this->assertEquals($connector->get_server(), 'virtserver.swaggerhub.com');
        $this->assertEquals($connector->get_openapidefinitionurl(), $openapidefinitionurl);
    }

    /**
     * Test for method local_data_importer_connectorinstance->get_all().
     */
    public function test_get_all() {

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description');
        $connectorinstance->set_name('Connector Instance Name');
        $connectorinstance->set_server_apikey('serverapikey');
        $connectorinstance->set_openapi_key('openapikey');
        $connectorinstance->set_server('virtserver.swaggerhub.com');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $id1 = $connectorinstance->save(true);

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description2');
        $connectorinstance->set_name('Connector Instance Name2');
        $connectorinstance->set_server_apikey('serverapikey2');
        $connectorinstance->set_openapi_key('openapikey2');
        $connectorinstance->set_server('virtserver.swaggerhub.com2');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.02';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $id2 = $connectorinstance->save(true);

        $connectors = $connectorinstance->get_all();

        $this->assertEquals(count($connectors), 2);
        foreach ($connectors as $connector) {
            $this->assertInstanceOf(\local_data_importer_connectorinstance::class, $connector);
        }
    }

    /**
     * Test for method local_data_importer_connectorinstance->delete().
     */
    public function test_delete() {

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description');
        $connectorinstance->set_name('Connector Instance Name');
        $connectorinstance->set_server_apikey('serverapikey');
        $connectorinstance->set_openapi_key('openapikey');
        $connectorinstance->set_server('virtserver.swaggerhub.com');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $id1 = $connectorinstance->save(true);

        $connectorinstance = new local_data_importer_connectorinstance();
        $connectorinstance->set_description('Connector Instance Description2');
        $connectorinstance->set_name('Connector Instance Name2');
        $connectorinstance->set_server_apikey('serverapikey2');
        $connectorinstance->set_openapi_key('openapikey2');
        $connectorinstance->set_server('virtserver.swaggerhub.com2');
        $openapidefinitionurl = 'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.02';
        $connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $id2 = $connectorinstance->save(true);

        // Now add a pathitem to one of the two connectors.
        $pathitem = new local_data_importer_connectorpathitem();
        $pathitem->set_name("Get Assessments");
        $pathitem->set_connector_id($id2); // Using the second connector.
        $pathitem->set_path_item("/MABS/MOD_CODE/{modcode}");
        $pathitem->set_active(true);
        $pathitem->set_http_method('GET');
        $pathitem->set_plugin_component('local_create_course');
        $pathitemid = $pathitem->save(true);

        // First delete should work fine.
        $connector = $connectorinstance->get_by_id($id1);
        $connector->delete();
        $connectors = $connectorinstance->get_all();
        $this->assertEquals(count($connectors), 1);

        // Second delete should throw an exception as has a pathitem attached.
        $connector = $connectorinstance->get_by_id($id2);
        try {
            $connector->delete();
        } catch (\Exception $e) {
            $this->assertEquals('Cannot delete connector as it has Pathitems using it', $e->getMessage());
        }
        $connectors = $connectorinstance->get_all();
        $this->assertEquals(count($connectors), 1);

        // Now delete the pathitem and try again.
        $pathitem->delete();
        $connector->delete();
        $connectors = $connectorinstance->get_all();
        $this->assertEquals(count($connectors), 0);
    }
}