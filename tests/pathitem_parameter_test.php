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
 * Class local_data_importer_pathitem_parameter_testcase
 * @group local_data_importer
 */
class local_data_importer_pathitem_parameter_testcase extends advanced_testcase {
    /**
     * @var
     */
    public $pathitemparameter;
    /**
     * @var
     */
    public $pathitemparameterid;
    public $pathitem;
    public $pathitemid;

    /**
     *
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
     * Add new path item parameter
     */
    public function test_add_pathitem_parameter() {
        $this->resetAfterTest();
        $pathitemobject = $this->pathitem->get_by_id($this->pathitemid);
        $this->pathitemparameter = new local_data_importer_pathitem_parameter();
        $this->pathitemparameter->set_pathitemid($pathitemobject->get_id());
        $this->pathitemparameter->set_pathitem_parameter("mod_code");
        $this->pathitemparameter->set_pluginparam_table("course");
        $this->pathitemparameter->set_pluginparam_field("idnumber");
        $this->pathitemparameterid = $this->pathitemparameter->save(true);
        $pathitemparameter = $this->pathitemparameter->get_by_id($this->pathitemparameterid);
        $this->assertInstanceOf(\local_data_importer_pathitem_parameter::class, $pathitemparameter);
    }

    /**
     * Add new path item parameter
     */
    public function test_update_pathitem_parameter() {
        $this->resetAfterTest();
        $pathitemobject = $this->pathitem->get_by_id($this->pathitemid);

        // Add.
        $this->pathitemparameter = new local_data_importer_pathitem_parameter();
        $this->pathitemparameter->set_pathitemid($pathitemobject->get_id());
        $this->pathitemparameter->set_pathitem_parameter("mod_code");
        $this->pathitemparameter->set_pluginparam_table("course");
        $this->pathitemparameter->set_pluginparam_field("idnumber");
        $this->pathitemparameterid = $this->pathitemparameter->save(true);

        // Update.
        $this->pathitemparameter = new local_data_importer_pathitem_parameter();
        $this->pathitemparameter->set_pathitemid($pathitemobject->get_id());
        $this->pathitemparameter->set_pathitem_parameter("mod_code");
        $this->pathitemparameter->set_pluginparam_table("course");
        $this->pathitemparameter->set_pluginparam_field("idnumber");
        $this->pathitemparameterid = $this->pathitemparameter->save(true);
        $pathitemparameter = $this->pathitemparameter->get_by_id($this->pathitemparameterid);
        $this->assertInstanceOf(\local_data_importer_pathitem_parameter::class, $pathitemparameter);
    }
}