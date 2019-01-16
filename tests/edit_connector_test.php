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
class local_data_importer_edit_connector_testcase extends advanced_testcase {
    /**
     *
     */
    protected $connectorinstanceid;
    /**
     * @var
     */
    public $connectorinstance;

    /**
     * Setup to add new connector first
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
    }


    /**
     * Test Connector instance update
     */
    public function test_update_connector_instance() {
        $this->resetAfterTest();
        $object = $this->connectorinstance->get_by_id($this->connectorinstanceid);
        $object->setname('Connector Name2');
        $object->setdescription('New Description');
        $object->set_timemodified(time());
        $object->save();
        $object2 = $this->connectorinstance->get_by_id($this->connectorinstanceid);
        $this->assertEquals("Connector Name2", $object2->get_name());
    }
}