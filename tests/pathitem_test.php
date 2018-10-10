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

class local_data_importer_pathitem_testcase extends advanced_testcase {
    public $pathitemid;
    public $pathitem;

    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        $this->pathitem = new local_data_importer_connectorpathitem();
        $this->pathitem->setName("Get Assessments");
        $this->pathitem->setConnectorid(1); // no need to create a new connector instance (?)
        $this->pathitem->setPathitem("/MABS/MOD_CODE/{modcode}");
        $this->pathitem->setActive(true);
        $this->pathitem->setHttpmethod('GET');
        $this->pathitem->setPlugincomponent('local_create_course');
        $this->pathitemid = $this->pathitem->save(true);
    }

    public function test_update_pathitem() {
        $this->resetAfterTest();
        $object = $this->pathitem->getbyid($this->pathitemid);
        $object->setName("NewPathItemName");
        $object->setActive(false);
        $object->save(true);
        $object2 = $this->pathitem->getbyid($this->pathitemid);
        $this->assertEquals("NewPathItemName", $object2->getname());
    }

    public function test_delete_pathitem() {
        global $DB;
        $object = $this->pathitem->getbyid($this->pathitemid);
        $responseparam = new local_data_importer_connectorresponseparams();
        try {
            if ($DB->record_exists($responseparam->getdbtable(), ['pathitemid' => $object->getid()])) {
                // it is already used by a connnector , cannot delete
            } else {
                // ok to delete connector
                $object->delete();
                $deletedcount = $DB->count_records($this->pathitem->getdbtable());
                $this->assertEquals(0, $deletedcount);
            }
        } catch (\dml_exception $e) {
            echo $e->getMessage();
        }

        // Another assertion with data in "connectorresponseparams"
        $this->setUp();
        $responseparam->setComponentparam('fullname');
        $responseparam->setPathitemid($this->pathitemid);
        $responseparam->setPathparam('pathparam1');
        $responseparam->setComponentparam('cparam1');
        $responseparam->save(true);

        try {
            if ($DB->record_exists($responseparam->getdbtable(), ['pathitemid' => $this->pathitemid])) {
                // it is already used by a connnector , cannot delete
                $deletedcount = $DB->count_records($this->pathitem->getdbtable());
                $this->assertEquals(1, $deletedcount);
            }
        } catch (\dml_exception $e) {
            echo $e->getMessage();
        }

    }
}
