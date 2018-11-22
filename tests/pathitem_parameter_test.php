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

class local_data_importer_pathitem_parameter_testcase extends advanced_testcase {
    public $pathitemparameter;
    public $pathitemparameterid;

    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        $this->pathitemparameter = new local_data_importer_pathitem_parameter();
        $this->pathitemparameter->set_pathitemid(1);
        $this->pathitemparameter->set_pathitem_parameter("mod_code");
        $this->pathitemparameter->set_pluginparam_table("course");
        $this->pathitemparameter->set_pluginparam_field("idnumber");
        $this->pathitemparameterid = $this->pathitemparameter->save(true);
    }

    public function test_update_pathitem_parameter() {
        $this->resetAfterTest();
        $object = $this->pathitemparameter->get_by_id($this->pathitemparameterid);
        $object->set_pathitem_parameter("mod_code2");
        $object->set_pluginparam_table("course2");
        $object->set_pluginparam_field("idnumber2");
        $object->save(true);
        $object2 = $this->pathitemparameter->get_by_id($this->pathitemparameterid);
        $this->assertEquals("mod_code2", $object2->get_pathitem_parameter());

    }
}