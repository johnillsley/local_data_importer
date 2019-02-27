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

/**
 * Class local_data_importer_pathitem_testcase
 * @group local_data_importer
 */
class local_data_importer_pathitem_testcase extends advanced_testcase {
    /**
     * @var
     */
    public $connectorinstance;
    /**
     * @var
     */
    public $pathiteminstance;
    /**
     * @var
     */
    public $connectorinstanceid;
    /**
     * @var
     */
    public $pathitemid;
    /**
     * @var
     */
    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        // Add new connector.
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

        // Add path item.
        $this->pathiteminstance = new local_data_importer_connectorpathitem();
        $this->pathiteminstance->set_name("Get Assessments");
        $this->pathiteminstance->set_connector_id($this->connectorinstanceid); // No need to create a new connector instance (?).
        $this->pathiteminstance->set_path_item("/MABS/MOD_CODE/{modcode}");
        $this->pathiteminstance->set_active(true);
        $this->pathiteminstance->set_http_method('GET');
        $this->pathiteminstance->set_plugin_component('local_create_course');
        $this->pathitemid = $this->pathiteminstance->save(true);
        $pathitem = $this->pathiteminstance->get_by_id($this->pathitemid);
        $this->assertInstanceOf(\local_data_importer_connectorpathitem::class, $pathitem);
    }
    /**
     * Test Delete Path Item
     * This will delete the path item and the relevant pathitem_parameter and pathitem_response
     */
    public function test_delete_pathitem() {
        global $DB;
        $pathitem = $this->pathiteminstance->get_by_id($this->pathitemid);
        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemresponse = new local_data_importer_pathitem_response();

        try {
            if ($DB->record_exists($responseparam->get_dbtable(), ['pathitemid' => $object->get_id()])) {
                // It is already used by a connnector , cannot delete.
            } else {
                // Ok to delete responseparam.
                $object->delete();
                $deletedcount = $DB->count_records($this->pathitem->get_dbtable());
                $this->assertEquals(0, $deletedcount);
            }
        } catch (\dml_exception $e) {
            echo $e->getMessage();
        }

        // Another assertion with data in "connectorresponseparams".
        $responseparam->set_componentparam('fullname');
        $responseparam->set_pathitemid($this->pathitemid);
        $responseparam->set_pathparam('pathparam1');
        $responseparam->set_componentparam('cparam1');
        $responseparam->save(true);

        try {
            if ($DB->record_exists($responseparam->get_dbtable(), ['pathitemid' => $this->pathitemid])) {
                // It is already used by a connnector , cannot delete.
                $deletedcount = $DB->count_records($this->pathitem->get_dbtable());
                $this->assertEquals(1, $deletedcount);
            }
        } catch (\dml_exception $e) {
            echo $e->getMessage();
        }
    }
}
