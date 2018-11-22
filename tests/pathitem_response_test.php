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

class local_data_importer_pathitem_response_testcase extends advanced_testcase {
    public $pathitemresponse;
    public $pathitemresponseid;

    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        $this->pathitemresponse = new local_data_importer_pathitem_response();
        $this->pathitemresponse->set_pathitemid(1);
        $this->pathitemresponse->set_pathitem_response("mod_code");
        $this->pathitemresponse->set_pluginresponse_table("course");
        $this->pathitemresponse->set_pluginresponse_field("idnumber");
        $this->pathitemresponseid = $this->pathitemresponse->save(true);
    }

    public function test_update_pathitem_parameter() {
        $this->resetAfterTest();
        $object = $this->pathitemresponse->get_by_id($this->pathitemresponseid);
        $object->set_pathitem_response("mod_code2");
        $object->set_pluginresponse_table("course2");
        $object->set_pluginresponse_field("idnumber2");
        $object->save(true);
        $object2 = $this->pathitemresponse->get_by_id($this->pathitemresponseid);
        $this->assertEquals("mod_code2", $object2->get_pathitem_response());

    }
}