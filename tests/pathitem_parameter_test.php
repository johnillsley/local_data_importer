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

/**
 * Unit tests for the local/data_importer/classes/pathitem_parameter.php.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_data_importer_pathitem_response_testcase
 */
class local_data_importer_pathitem_parameter_testcase extends advanced_testcase {

    /**
     * @var integer
     */
    private $pathitemid;

    /**
     * Create a pathitem and store the id.
     */
    public function setUp() {

        $this->resetAfterTest();

        // Create a pathitem instance.
        $pathitem = new local_data_importer_connectorpathitem();
        $pathitem->set_name("Get Assessments");
        $pathitem->set_connector_id(1); // No need to create a new connector instance (?).
        $pathitem->set_path_item("/MABS/MOD_CODE/{modcode}");
        $pathitem->set_active(true);
        $pathitem->set_http_method('GET');
        $pathitem->set_plugin_component('test');
        $this->pathitemid = $pathitem->save(true);
    }

    /**
     * Test for method local_data_importer_pathitem_parameter->save().
     */
    public function test_save() {
        global $DB;

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemparameter->set_pathitemid($this->pathitemid);
        $pathitemparameter->set_pathitem_parameter('PRS_EMAD.PRS.CAMS');
        $pathitemparameter->set_subplugin_parameter('user');
        $id = $pathitemparameter->save(true);

        $parameterrecords = $DB->get_records($pathitemparameter->get_dbtable());
        $this->assertEquals(1, count($parameterrecords));

        $parameterrecord = array_pop($parameterrecords);
        $this->assertEquals('PRS_EMAD.PRS.CAMS', $parameterrecord->pathitemparameter);
        $this->assertEquals('user', $parameterrecord->subpluginparameter);
        $this->assertEquals($id, $parameterrecord->id);

        // Now try an update.
        $pathitemparameter->set_pathitem_parameter('coursecode');
        $pathitemparameter->set_subplugin_parameter('course');
        $id = $pathitemparameter->save(true);

        $parameterrecords = $DB->get_records($pathitemparameter->get_dbtable());
        $this->assertEquals(1, count($parameterrecords));

        $parameterrecord = array_pop($parameterrecords);
        $this->assertEquals('coursecode', $parameterrecord->pathitemparameter);
        $this->assertEquals('course', $parameterrecord->subpluginparameter);
    }

    /**
     * Test for method local_data_importer_pathitem_parameter->delete().
     */
    public function test_delete() {
        global $DB;

        // Create two parameter mappings.
        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemparameter->set_pathitemid($this->pathitemid);
        $pathitemparameter->set_pathitem_parameter('PRS_EMAD.PRS.CAMS');
        $pathitemparameter->set_subplugin_parameter('user');
        $id1 = $pathitemparameter->save(true);

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemparameter->set_pathitemid($this->pathitemid);
        $pathitemparameter->set_pathitem_parameter('COURSE');
        $pathitemparameter->set_subplugin_parameter('crs');
        $id2 = $pathitemparameter->save(true);

        // Delete first parameter mapping.
        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $parameter = $pathitemparameter->get_by_id($id1);
        $parameter->delete();

        $parameterrecords = $DB->get_records($pathitemparameter->get_dbtable());
        $this->assertEquals(1, count($parameterrecords));

        // Delete second parameter mapping.
        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $parameter = $pathitemparameter->get_by_id($id2);
        $parameter->delete();

        $parameterrecords = $DB->get_records($pathitemparameter->get_dbtable());
        $this->assertEquals(0, count($parameterrecords));
    }

    /**
     * Test for method local_data_importer_pathitem_parameter->get_by_id().
     */
    public function test_get_by_id() {

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemparameter->set_pathitemid($this->pathitemid);
        $pathitemparameter->set_pathitem_parameter('PRS_EMAD.PRS.CAMS');
        $pathitemparameter->set_subplugin_parameter('user');
        $id = $pathitemparameter->save(true);

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $parameter = $pathitemparameter->get_by_id($id);
        $this->assertInstanceOf(\local_data_importer_pathitem_parameter::class, $parameter);
        $this->assertEquals('PRS_EMAD.PRS.CAMS', $parameter->get_pathitem_parameter());
        $this->assertEquals('user', $parameter->get_subplugin_parameter());
        $this->assertEquals($this->pathitemid, $parameter->get_pathitemid());
    }

    /**
     * Test for method local_data_importer_pathitem_parameter->get_by_pathitem_id().
     */
    public function test_get_by_pathitem_id() {

        // Create two parameter mappings.
        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemparameter->set_pathitemid($this->pathitemid);
        $pathitemparameter->set_pathitem_parameter('PRS_EMAD.PRS.CAMS');
        $pathitemparameter->set_subplugin_parameter('user');
        $id1 = $pathitemparameter->save(true);

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $pathitemparameter->set_pathitemid($this->pathitemid);
        $pathitemparameter->set_pathitem_parameter('COURSE');
        $pathitemparameter->set_subplugin_parameter('crs');
        $id2 = $pathitemparameter->save(true);

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $parameters = $pathitemparameter->get_by_pathitem_id($this->pathitemid);
        $this->assertEquals(2, count($parameters));

        foreach ($parameters as $parameter) {
            $this->assertInstanceOf(\local_data_importer_pathitem_parameter::class, $parameter);
        }
    }
}