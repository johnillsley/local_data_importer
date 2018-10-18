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
require_once($CFG->dirroot.'/local/data_importer/vendor/autoload.php');
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

/**
 * Class local_data_importer_testcase
 */
class local_data_importer_connector_testcase extends advanced_testcase {
    /**
     *
     */
    protected $connectorinstanceid;
    public $connectorInstance;
    public function test_swaggerhub_api() {
        global $CFG;
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [],file_get_contents($CFG->dirroot.'/local/data_importer/tests/fixtures/swaggerresponse.json')),
            new Response(202, ['Content-Length' => 0]),
            new RequestException("Error Communicating with Server", new Request('GET', 'test'))
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        // The first request is intercepted with the first response.
        $response =  $client->request('GET', '/');
        if($response->getStatusCode() == 200){
            //we have a response
            $contents = (string)$response->getBody()->getContents();
            $contents = json_decode($contents);
             if($contents && property_exists($contents,'swagger')){
                 // verify the swagger version
                $this->assertEquals('2.0', $contents->swagger);
            }
        }
    }
    public function setUp(){
        global $DB, $CFG;
        $this->resetAfterTest(false);
        $json = file_get_contents($CFG->dirroot.'/local/data_importer/tests/fixtures/swaggerresponse.json');
        $data = json_decode($json);
        $this->connectorInstance = new local_data_importer_connectorinstance();
        $this->connectorInstance->setdescription("Connector Instance Description");
        $this->connectorInstance->setname("Connector Instance Name");
        $this->connectorInstance->set_server_apikey('serverapikey');
        $this->connectorInstance->setopenapikey('openapikey');
        $host = $data->host;
        $basepath = $data->basePath;
        $this->connectorInstance->sethost($host);
        $this->connectorInstance->setbasepath($basepath);
        $openapidefinitionurl = "https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0";
        $this->connectorInstance->setopenapidefinitionurl($openapidefinitionurl);
        $this->connectorinstanceid = $this->connectorInstance->save(true);
     }


    public function test_update_connector_instance(){
        $this->resetAfterTest();
        $object = $this->connectorInstance->getbyid($this->connectorinstanceid);
        $object->setname('Connector Name2');
        $object->setdescription('New Description');
        $object->settimemodified(time());
        $object->save();
        $object2 = $this->connectorInstance->getbyid($this->connectorinstanceid);
        $this->assertEquals("Connector Name2",$object2->getname());
    }
    /**
     *
     */
    public function test_delete_connector() {
        global $DB;
        //Path item active

        $instancescount = $DB->count_records($this->connectorInstance->getdbtable());
        $object = $this->connectorInstance->getbyid
        (
            $this->connectorinstanceid
        );
        $pathitem = new local_data_importer_connectorpathitem();
        try{
            if($DB->record_exists($pathitem->getdbtable(),['connectorid'=>$object->getid()])){
                // it is already used by a connnector , cannot delete
            }
            else{
                // ok to delete connector
                $object->delete();
                $deletedcount = $DB->count_records($this->connectorInstance->getdbtable());
                $this->assertEquals(0,$deletedcount);
            }
        }
        catch (\dml_exception $e){
            echo $e->getMessage();
        }
    }
    /**
     *
     */
    public function validate_connector_test() {

    }
}