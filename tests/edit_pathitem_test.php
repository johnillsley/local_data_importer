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
class local_data_importer_edit_pathitem_testcase extends advanced_testcase {
    /**
     * @var
     */
    public $pathitemid;
    /**
     * @var
     */
    public $pathitem;

    /**
     * Add new Pathitem
     */
    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
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
     * Test Update Path Item
     */
    public function test_update_pathitem() {
        $this->resetAfterTest();
        $object = $this->pathitem->get_by_id($this->pathitemid);
        $object->set_name("NewPathItemName");
        $object->set_active(false);
        $object->save(true);
        $object2 = $this->pathitem->get_by_id($this->pathitemid);
        $this->assertEquals("NewPathItemName", $object2->get_name());
    }
}
