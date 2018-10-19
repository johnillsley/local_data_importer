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
 * Class local_data_importer_responseparams_testcase
 */
class local_data_importer_responseparams_testcase extends advanced_testcase {
    /**
     * @var
     */
    public $responseparams;
    /**
     * @var
     */
    public $responseparamid;

    /**
     *
     */
    public function setUp() {
        $this->resetAfterTest(false);
        $this->responseparams = new local_data_importer_connectorresponseparams();
        $this->responseparams->set_pathitemid(1);
        $this->responseparams->set_pathparam('fullname');
        $this->responseparams->set_componentparam('c.fullname');
        $this->responseparamid = $this->responseparams->save(true);

    }

    /**
     * Test Update Responseparam Mapping
     */
    public function test_update_responseparam_mapping() {
        $this->resetAfterTest();
        $object = $this->responseparams->get_by_id($this->responseparamid);
        if ($object instanceof local_data_importer_connectorresponseparams) {
            $object->set_componentparam('c1.fullname');
            $object->save();
            $object2 = $this->responseparams->get_by_id($this->responseparamid);
            $this->assertEquals("c1.fullname", $object2->get_componentparam());
        }
    }

    /**
     *
     */
    public function test_delete_responseparam() {

    }
}