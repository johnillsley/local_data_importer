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

global $CFG;
require_once($CFG->dirroot.'/local/data_importer/vendor/autoload.php');

class local_data_importer_connectorpathitem_test extends advanced_testcase {

    protected $pathitemstest = array();

    protected function setUp() {
        // Create 3 records in connector_pathitem table that are ordered.
        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 1');
        $connectorpathitem->set_connector_id(1);
        $connectorpathitem->set_path_item('/pathitem1');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test_subplugin');
        $id = $connectorpathitem->save(true);
        $this->pathitemstest[] = $id;

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 2');
        $connectorpathitem->set_connector_id(1);
        $connectorpathitem->set_path_item('/pathitem2');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test_subplugin');
        $id = $connectorpathitem->save(true);
        $this->pathitemstest[] = $id;

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $connectorpathitem->set_name('Path Item 3');
        $connectorpathitem->set_connector_id(1);
        $connectorpathitem->set_path_item('/pathitem3');
        $connectorpathitem->set_active(true);
        $connectorpathitem->set_http_method('GET');
        $connectorpathitem->set_plugin_component('importers_test_subplugin');
        $id = $connectorpathitem->save(true);
        $this->pathitemstest[] = $id;
    }

    public function test_reorder_import() {
        $this->resetAfterTest(true);

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $pathitemconnector = $connectorpathitem->get_by_id($this->pathitemstest[1]);
        $this->assertSame($pathitemconnector->get_import_order(), 2);

        $return = $pathitemconnector->reorder_import('up');
        $pathitemconnector = $connectorpathitem->get_by_id($this->pathitemstest[1]);
        $this->assertSame($pathitemconnector->get_import_order(), 1);
        $this->assertSame($return, true);

        $pathitemconnector = $connectorpathitem->get_by_id($this->pathitemstest[0]); // Now move to position 2.
        $this->assertSame($pathitemconnector->get_import_order(), 2);

        $return = $pathitemconnector->reorder_import('down');
        $pathitemconnector = $connectorpathitem->get_by_id($this->pathitemstest[0]);
        $this->assertSame($pathitemconnector->get_import_order(), 3);
        $this->assertSame($return, true);

        $return = $pathitemconnector->reorder_import('down'); // Already last in list so can't move down.
        $pathitemconnector = $connectorpathitem->get_by_id($this->pathitemstest[0]);
        $this->assertSame($pathitemconnector->get_import_order(), 3);
        $this->assertSame($return, false); // No change made so checking for false.
    }
}