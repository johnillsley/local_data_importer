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
     * @var
     */
    public $connectorinstance;

    /**
     *
     */
    public function test_swaggerhub_api() {
        global $CFG;
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/swaggerresponse.json')),
            new Response(202, ['Content-Length' => 0]),
            new RequestException("Error Communicating with Server", new Request('GET', 'test'))
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        // The first request is intercepted with the first response.
        $response = $client->request('GET', '/');
        if ($response->getStatusCode() == 200) {
            // We have a response.
            $contents = (string)$response->getBody()->getContents();
            $contents = json_decode($contents);
            if ($contents && property_exists($contents, 'swagger')) {
                // Verify the swagger version.
                $this->assertEquals('2.0', $contents->swagger);
            }
        }
    }

    /**
     * Test Adding of a new connector to the database.
     * @throws Exception
     */
    public function test_add_connector_instance() {
        global  $CFG;
        $this->resetAfterTest(false);
        $json = file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/swaggerresponse.json');
        $data = json_decode($json);
        $this->connectorinstance = new local_data_importer_connectorinstance();
        $this->connectorinstance->set_description("Connector Instance Description");
        $this->connectorinstance->set_name("Connector Instance Name");
        $this->connectorinstance->set_server_apikey('serverapikey');
        $this->connectorinstance->set_openapi_key('openapikey');
        $host = $data->host;
        $this->connectorinstance->set_server($host);
        $openapidefinitionurl = "https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0";
        $this->connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $this->connectorinstanceid = $this->connectorinstance->save(true);
        $connector = $this->connectorinstance->get_by_id($this->connectorinstanceid);
        $this->assertInstanceOf(\local_data_importer_connectorinstance::class, $connector);

    }
}