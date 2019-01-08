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

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');
global $CFG;
require_once($CFG->dirroot . '/local/data_importer/vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * Class local_data_importer_testcase
 * @group local_data_importer
 */
class local_data_importer_add_connector_testcase extends advanced_testcase {
    /**
     *
     */
    protected $connectorinstanceid;
    /**
     * @var $connectorinstance
     */
    public $connectorinstance;
    /**
     * @var $pathitem
     */
    public $pathitem;
    /**
     * @var $pathitemid
     */
    public $pathitemid;


    /**
     * Setup to add new connector and pathitem first
     */
    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        $json = file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/swaggerresponse.json');
        $data = json_decode($json);
        $this->connectorinstance = new local_data_importer_connectorinstance();
        $this->connectorinstance->setdescription("Connector Instance Description");
        $this->connectorinstance->setname("Connector Instance Name");
        $this->connectorinstance->set_server_apikey('serverapikey');
        $this->connectorinstance->setopenapikey('openapikey');
        $host = $data->host;
        $this->connectorinstance->setserver($host);
        $openapidefinitionurl = "https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0";
        $this->connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $this->connectorinstanceid = $this->connectorinstance->save(true);
        // Add path item.
        $this->pathitem = new local_data_importer_connectorpathitem();
        $this->pathitem->set_name("Get Assessments");
        $this->pathitem->set_connector_id($this->connectorinstanceid); // No need to create a new connector instance (?).
        $this->pathitem->set_path_item("/MABS/MOD_CODE/{modcode}");
        $this->pathitem->set_active(true);
        $this->pathitem->set_http_method('GET');
        $this->pathitem->set_plugin_component('local_create_course');
        $this->pathitemid = $this->pathitem->save(true);
    }

    /**
     * Test Connector Instance Deletion
     */
    public function test_delete_connector_instance() {
        global $DB;
        // Get added connector.
        $connector = $this->connectorinstance->get_by_id($this->connectorinstanceid);
        $pathitem = $this->pathitem->get_by_id($this->pathitemid);
        // Test 1 : path item already exists and is connected to connector.
        // Should not be able to delete a connector.
        try {
            $connector->delete();
        } catch (\Exception $e) {
            $this->assertEquals('Cannot delete connector as it has Pathitems using it', $e->getMessage());

        }
        // Test 2 : No path item attached to connector
        // Should be able to delete a connector.

        // Delete pathitem.
        $pathitem->delete();
        // Delete connector.
        $connector->delete();
        $deletedcount = $DB->count_records($this->connectorinstance->getdbtable());
        $this->assertEquals(0, $deletedcount);

    }
}