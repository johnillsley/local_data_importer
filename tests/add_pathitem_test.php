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
     * Add new Pathitem
     */
    public function test_add_pathitem() {
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
}
