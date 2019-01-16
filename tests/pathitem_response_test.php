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
 * Class local_data_importer_pathitem_response_testcase
 * @group local_data_importer
 */
class local_data_importer_pathitem_response_testcase extends advanced_testcase {
    /**
     * @var
     */
    public $pathitemresponse;
    /**
     * @var
     */
    public $pathitemresponseid;
    /**
     * @var
     */
    public $pathitem;
    /**
     * @var
     */
    public $pathitemid;

    /**
     * @throws Exception
     */
    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        // Path-item instance.
        $this->pathitem = new local_data_importer_connectorpathitem();
        $this->pathitem->set_name("Get Assessments");
        $this->pathitem->set_connector_id(1); // No need to create a new connector instance (?).
        $this->pathitem->set_path_item("/MABS/MOD_CODE/{modcode}");
        $this->pathitem->set_active(true);
        $this->pathitem->set_http_method('GET');
        $this->pathitem->set_plugin_component('local_create_course');
        $this->pathitemid = $this->pathitem->save(true);
    }

    /**
     * Add a new path item response to the database
     */
    public function test_add_pathitem_response() {
        $this->resetAfterTest();
        $pathitemobject = $this->pathitem->get_by_id($this->pathitemid);
        $this->pathitemresponse = new local_data_importer_pathitem_response();
        $this->pathitemresponse->set_pathitemid($pathitemobject->get_id());
        $this->pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $this->pathitemresponse->set_pluginresponse_table('user');
        $this->pathitemresponse->set_pluginresponse_field('username');
        $this->pathitemresponseid = $this->pathitemresponse->save(true);
        $pathitemresponse = $this->pathitemresponse->get_by_id($this->pathitemresponseid);
        $this->assertInstanceOf(\local_data_importer_pathitem_response::class, $pathitemresponse);

    }

    /**
     * Test update of path item response entities
     */
    public function test_update_pathitem_response() {
        $this->resetAfterTest();
        // Add again.
        $pathitemobject = $this->pathitem->get_by_id($this->pathitemid);
        $this->pathitemresponse = new local_data_importer_pathitem_response();
        $this->pathitemresponse->set_pathitemid($pathitemobject->get_id());
        $this->pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $this->pathitemresponse->set_pluginresponse_table('user');
        $this->pathitemresponse->set_pluginresponse_field('username');
        $this->pathitemresponseid = $this->pathitemresponse->save(true);
        // Update.
        $pathitemresponse = $this->pathitemresponse->get_by_id($this->pathitemresponseid);
        $pathitemresponse->set_pluginresponse_table('user2');
        $pathitemresponse->set_pluginresponse_field('username2');
        $pathitemresponse->save(true);
        $pathitemresponse = $this->pathitemresponse->get_by_id($this->pathitemresponseid);
        // Confirm.
        $this->assertEquals("username2", $pathitemresponse->get_pluginresponse_field());
        $this->assertEquals("user2", $pathitemresponse->get_pluginresponse_table());
    }
}