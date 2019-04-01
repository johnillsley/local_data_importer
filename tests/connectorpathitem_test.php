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
 * Unit tests for the local/data_importer/classes/connectorpathitem.php.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_data_importer_connectorpathitem
 */
class local_data_importer_connectorpathitem_testcase extends advanced_testcase {

    const DB_SETTINGS = 'local_data_importer_setting';

    /**
     * @var object local_data_importer_connectorpathitem
     */
    private $pathiteminstance;

    /**
     * @var integer
     */
    private $pathitemid;

    /**
     * Create a pathitem and store the id.
     */
    protected function setUp() {

        $this->resetAfterTest(true);

        $this->pathiteminstance = new local_data_importer_connectorpathitem();
        $this->pathiteminstance->set_name('Get Assessments');
        $this->pathiteminstance->set_connector_id(22); // No need to create a new connector instance (?).
        $this->pathiteminstance->set_path_item('/MABS/MOD_CODE/{modcode}');
        $this->pathiteminstance->set_active(true);
        $this->pathiteminstance->set_http_method('GET');
        $this->pathiteminstance->set_plugin_component('local_create_course');
        $this->pathitemid = $this->pathiteminstance->save(true);
    }

    /**
     * Test for method local_data_importer_connectorpathitem->save().
     */
    public function test_save() {
        global $DB;

        $pathitemrecords = $DB->get_records($this->pathiteminstance->get_dbtable());
        $this->assertEquals(count($pathitemrecords), 1);

        $pathitemrecord = array_pop($pathitemrecords);
        $this->assertEquals($pathitemrecord->name, 'Get Assessments');
        $this->assertEquals($pathitemrecord->connectorid, 22);
        $this->assertEquals($pathitemrecord->pathitem, '/MABS/MOD_CODE/{modcode}');
        $this->assertEquals($pathitemrecord->httpmethod, 'GET');
        $this->assertEquals($pathitemrecord->plugincomponent, 'local_create_course');
        $this->assertEquals($pathitemrecord->active, 1);

        // Now do an update and test.
        $this->pathiteminstance->set_name('ABCD');
        $this->pathiteminstance->save(true);

        $connectorrecords = $DB->get_records($this->pathiteminstance->get_dbtable());
        $this->assertEquals(count($connectorrecords), 1);

        $connectorrecord = array_pop($connectorrecords);
        $this->assertEquals($connectorrecord->name, 'ABCD');
    }

    /**
     * Test for method local_data_importer_connectorpathitem->get_by_id().
     */
    public function test_get_by_id() {

        $pathitem = $this->pathiteminstance->get_by_id($this->pathitemid);
        $this->assertInstanceOf(\local_data_importer_connectorpathitem::class, $pathitem);
        $this->assertEquals($pathitem->get_name(), 'Get Assessments');
        $this->assertEquals($pathitem->get_connector_id(), 22);
        $this->assertEquals($pathitem->get_path_item(), '/MABS/MOD_CODE/{modcode}');
        $this->assertEquals($pathitem->get_active(), true);
        $this->assertEquals($pathitem->get_http_method(), 'GET');
        $this->assertEquals($pathitem->get_plugin_component(), 'local_create_course');
    }

    /**
     * Test for method local_data_importer_connectorpathitem->get_all().
     */
    public function test_get_all() {

        // Add another connector pathitem so there are two.
        $pathiteminstance = new local_data_importer_connectorpathitem();
        $pathiteminstance->set_name('Get Assessments2');
        $pathiteminstance->set_connector_id(23); // No need to create a new connector instance (?).
        $pathiteminstance->set_path_item('/MABS/MOD_CODE/{modcode2}');
        $pathiteminstance->set_active(false);
        $pathiteminstance->set_http_method('POST');
        $pathiteminstance->set_plugin_component('local_create_course2');
        $id2 = $pathiteminstance->save(true);

        $pathitems = $pathiteminstance->get_all();

        $this->assertEquals(count($pathitems), 2);
        foreach ($pathitems as $pathitem) {
            $this->assertInstanceOf(\local_data_importer_connectorpathitem::class, $pathitem);
        }
    }

    /**
     * Test for method local_data_importer_connectorpathitem->get_by_subplugin().
     */
    public function test_get_by_subplugin() {

        // Add another connector pathitem so there are two.
        $pathiteminstance = new local_data_importer_connectorpathitem();
        $pathiteminstance->set_name('Get Assessments2');
        $pathiteminstance->set_connector_id(23); // No need to create a new connector instance (?).
        $pathiteminstance->set_path_item('/MABS/MOD_CODE/{modcode2}');
        $pathiteminstance->set_active(false);
        $pathiteminstance->set_http_method('POST');
        $pathiteminstance->set_plugin_component('local_create_course');
        $id2 = $pathiteminstance->save(true);

        $pathitems = $pathiteminstance->get_by_subplugin('local_create_course');

        $this->assertEquals(count($pathitems), 2);
        foreach ($pathitems as $pathitem) {
            $this->assertInstanceOf(\local_data_importer_connectorpathitem::class, $pathitem);
        }
    }

    /**
     * Test for method local_data_importer_connectorpathitem->delete().
     */
    public function test_delete() {
        global $DB;

        // Create a parameter mapping for this pathitem.
        $parameter = new local_data_importer_pathitem_parameter();
        $parameter->set_pathitemid($this->pathitemid);
        $parameter->set_pathitem_parameter('pathitem_param');
        $parameter->set_subplugin_parameter('subplugin_param');
        $parameter->save();

        $parameters = $parameter->get_by_pathitem_id($this->pathitemid);
        $this->assertEquals(count($parameters), 1);

        // Create a response mapping for this pathitem.
        $response = new local_data_importer_pathitem_response();
        $response->set_pathitemid($this->pathitemid);
        $response->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $response->set_pluginresponse_table('user');
        $response->set_pluginresponse_field('username');
        $response->save();

        // Create additional settings for this pathitem.
        $setting1 = new stdClass();
        $setting1->pathitemid   = $this->pathitemid;
        $setting1->name         = 'name1';
        $setting1->value        = 'value1';
        $setting2 = new stdClass();
        $setting2->pathitemid   = $this->pathitemid;
        $setting2->name         = 'name2';
        $setting2->value        = 'value2';
        $DB->insert_records(self::DB_SETTINGS, array($setting1, $setting2));
        $records = $DB->get_records(self::DB_SETTINGS, array('pathitemid' => $this->pathitemid));
        $this->assertEquals(count($records), 2);

        $responses = $response->get_by_pathitem_id($this->pathitemid);
        $this->assertEquals(count($responses), 1);

        $pathitem = $this->pathiteminstance->get_by_id($this->pathitemid);
        $pathitem->delete();
        $pathitems = $this->pathiteminstance->get_all();
        $this->assertEquals(count($pathitems), 0);

        // Check parameter mapping have also been deleted.
        $parameters = $parameter->get_by_pathitem_id($this->pathitemid);
        $this->assertEquals(count($parameters), 0);

        // Check response mapping has been deleted.
        $responses = $response->get_by_pathitem_id($this->pathitemid);
        $this->assertEquals(count($responses), 0);

        // Check additional settings have been deleted.
        $records = $DB->get_records(self::DB_SETTINGS, array('pathitemid' => $this->pathitemid));
        $this->assertEquals(count($records), 0);
    }

    /**
     * Test for method local_data_importer_connectorpathitem->reorder_import().
     */
    public function test_reorder_import() {

        // Also tests private method "get_next_importorder" when creating new pathitems.

        $pathitemtest = array();
        // Create 3 records in local_data_importer_path table that are ordered.
        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 1');
        $connectorpathitem->set_connector_id(1);
        $connectorpathitem->set_path_item('/pathitem1');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test');
        $id = $connectorpathitem->save(true);
        $pathitemtest[] = $id;

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 2');
        $connectorpathitem->set_connector_id(1);
        $connectorpathitem->set_path_item('/pathitem2');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test');
        $id = $connectorpathitem->save(true);
        $pathitemtest[] = $id;

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 3');
        $connectorpathitem->set_connector_id(1);
        $connectorpathitem->set_path_item('/pathitem3');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test');
        $id = $connectorpathitem->save(true);
        $pathitemtest[] = $id;

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $pathitemconnector = $connectorpathitem->get_by_id($pathitemtest[1]);
        $this->assertSame($pathitemconnector->get_import_order(), 3);

        $return = $pathitemconnector->reorder_import('up');
        $pathitemconnector = $connectorpathitem->get_by_id($pathitemtest[1]);
        $this->assertSame($pathitemconnector->get_import_order(), 2);
        $this->assertSame($return, true);

        $pathitemconnector = $connectorpathitem->get_by_id($pathitemtest[0]); // Now move to position 2.
        $this->assertSame($pathitemconnector->get_import_order(), 3);

        $return = $pathitemconnector->reorder_import('down');
        $pathitemconnector = $connectorpathitem->get_by_id($pathitemtest[0]);
        $this->assertSame($pathitemconnector->get_import_order(), 4);
        $this->assertSame($return, true);

        $return = $pathitemconnector->reorder_import('down'); // Already last in list so can't move down.
        $pathitemconnector = $connectorpathitem->get_by_id($pathitemtest[0]);
        $this->assertSame($pathitemconnector->get_import_order(), 4);
        $this->assertSame($return, false); // No change made so checking for false.
    }

    /**
     * Test for hide_pathitem.
     */
    public function test_hide_pathitem() {

        $this->pathiteminstance->set_active(false);
        $this->pathiteminstance->save(true);

        $object = $this->pathiteminstance->get_by_id($this->pathitemid);
        $this->assertEquals(0, $object->get_active());
    }

    /**
     * Test for show_pathitem.
     */
    public function test_show_pathitem() {

        $this->pathiteminstance->set_active(false);
        $this->pathiteminstance->save(true);

        $this->pathiteminstance->set_active(true);
        $this->pathiteminstance->save(true);

        $object = $this->pathiteminstance->get_by_id($this->pathitemid);
        $this->assertEquals(1, $object->get_active());
    }
}