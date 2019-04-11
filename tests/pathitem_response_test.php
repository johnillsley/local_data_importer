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
 * Unit tests for the local/data_importer/classes/pathitem_response.php.
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
class local_data_importer_pathitem_response_testcase extends advanced_testcase {

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
     * Test for method local_data_importer_pathitem_response->save().
     */
    public function test_save() {
        global $DB;

        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $pathitemresponse->set_pluginresponse_table('user');
        $pathitemresponse->set_pluginresponse_field('username');
        $id = $pathitemresponse->save(true);

        $responserecords = $DB->get_records($pathitemresponse->get_dbtable());
        $this->assertEquals(1, count($responserecords));

        $responserecord = array_pop($responserecords);
        $this->assertEquals('PRS_EMAD.PRS.CAMS', $responserecord->pathitemresponse);
        $this->assertEquals('user', $responserecord->pluginresponsetable);
        $this->assertEquals('username', $responserecord->pluginresponsefield);
        $this->assertEquals($id, $responserecord->id);

        // Now try an update.
        $pathitemresponse->set_pathitem_response('coursecode');
        $pathitemresponse->set_pluginresponse_table('course');
        $pathitemresponse->set_pluginresponse_field('idnumber');
        $id = $pathitemresponse->save(true);

        $responserecords = $DB->get_records($pathitemresponse->get_dbtable());
        $this->assertEquals(1, count($responserecords));

        $responserecord = array_pop($responserecords);
        $this->assertEquals('coursecode', $responserecord->pathitemresponse);
        $this->assertEquals('course', $responserecord->pluginresponsetable);
        $this->assertEquals('idnumber', $responserecord->pluginresponsefield);
    }

    /**
     * Test for method local_data_importer_pathitem_response->get_by_id().
     */
    public function test_get_by_id() {

        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $pathitemresponse->set_pluginresponse_table('user');
        $pathitemresponse->set_pluginresponse_field('username');
        $id = $pathitemresponse->save(true);

        $pathitemresponse = new local_data_importer_pathitem_response();
        $response = $pathitemresponse->get_by_id($id);
        $this->assertInstanceOf(\local_data_importer_pathitem_response::class, $response);
        $this->assertEquals('PRS_EMAD.PRS.CAMS', $response->get_pathitem_response());
        $this->assertEquals('user', $response->get_pluginresponse_table());
        $this->assertEquals('username', $response->get_pluginresponse_field());
        $this->assertEquals($this->pathitemid, $response->get_pathitemid());
    }

    /**
     * Test for method local_data_importer_pathitem_response->get_by_pathitem_id().
     */
    public function test_get_by_pathitem_id() {

        // Create two response mappings.
        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $pathitemresponse->set_pluginresponse_table('user');
        $pathitemresponse->set_pluginresponse_field('username');
        $id = $pathitemresponse->save(true);

        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('coursecode');
        $pathitemresponse->set_pluginresponse_table('course');
        $pathitemresponse->set_pluginresponse_field('idnumber');
        $id = $pathitemresponse->save(true);

        $pathitemresponse = new local_data_importer_pathitem_response();
        $responses = $pathitemresponse->get_by_pathitem_id($this->pathitemid);
        $this->assertEquals(2, count($responses));

        foreach ($responses as $response) {
            $this->assertInstanceOf(\local_data_importer_pathitem_response::class, $response);
        }
    }

    /**
     * Test for method local_data_importer_pathitem_response->get_lookups_for_pathitem().
     */
    public function test_get_lookups_for_pathitem() {

        // Create two response mappings.
        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $pathitemresponse->set_pluginresponse_table('user');
        $pathitemresponse->set_pluginresponse_field('username');
        $id = $pathitemresponse->save(true);

        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('coursecode');
        $pathitemresponse->set_pluginresponse_table('course');
        $pathitemresponse->set_pluginresponse_field('idnumber');
        $id = $pathitemresponse->save(true);

        $pathitemresponse = new local_data_importer_pathitem_response();
        $lookups = $pathitemresponse->get_lookups_for_pathitem($this->pathitemid);

        $expected = array(
                'user' =>
                        array(
                                'username' => 'PRS_EMAD.PRS.CAMS'
                        ),
                'course' =>
                        array(
                                'idnumber' => 'coursecode'
                        ),
        );

        $this->assertEquals($expected, $lookups);
    }

    /**
     * Test for method local_data_importer_pathitem_response->delete().
     */
    public function test_delete() {
        global $DB;

        // Create two response mappings.
        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('PRS_EMAD.PRS.CAMS');
        $pathitemresponse->set_pluginresponse_table('user');
        $pathitemresponse->set_pluginresponse_field('username');
        $id1 = $pathitemresponse->save(true);

        $pathitemresponse = new local_data_importer_pathitem_response();
        $pathitemresponse->set_pathitemid($this->pathitemid);
        $pathitemresponse->set_pathitem_response('coursecode');
        $pathitemresponse->set_pluginresponse_table('course');
        $pathitemresponse->set_pluginresponse_field('idnumber');
        $id2 = $pathitemresponse->save(true);

        // Delete first response mapping.
        $pathitemresponse = new local_data_importer_pathitem_response();
        $response = $pathitemresponse->get_by_id($id1);
        $response->delete();

        $responserecords = $DB->get_records($pathitemresponse->get_dbtable());
        $this->assertEquals(1, count($responserecords));

        // Delete second response mapping.
        $pathitemresponse = new local_data_importer_pathitem_response();
        $response = $pathitemresponse->get_by_id($id2);
        $response->delete();

        $responserecords = $DB->get_records($pathitemresponse->get_dbtable());
        $this->assertEquals(0, count($responserecords));
    }
}