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
 * This file defines the tests for adding and updating subplugin additional settings for an importer.
 *
 * @package    local/data_importer/importers/course
 * @author     Hittesh Ahuja <ha386@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');
global $CFG;
require_once($CFG->dirroot . '/local/data_importer/vendor/autoload.php');

/**
 * Class local_data_importer_add_plugin_additional_params_test
 * @group local_data_importer
 */
class local_data_importer_add_plugin_additional_params_test extends advanced_testcase {
    /**
     * @var
     */
    public $connectorinstance;
    /**
     * @var
     */
    public $pathiteminstance;
    /**
     * @var
     */
    public $connectorinstanceid;
    /**
     * @var
     */
    public $pathitemid;
    /**
     * @var
     */
    public $pathitem;

    /**
     * Set-up new connector and pathitem
     * @throws Exception
     */
    public function setUp() {
        global $DB, $CFG;
        $this->resetAfterTest(false);
        // Add new connector.
        $json = file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/swaggerresponse.json');
        $data = json_decode($json);
        $this->connectorinstance = new local_data_importer_connectorinstance();
        $this->connectorinstance->setdescription("Connector Instance Description");
        $this->connectorinstance->setname("Connector Instance Name");
        $this->connectorinstance->set_server_apikey('serverapikey');
        $this->connectorinstance->setopenapikey('openapikey');
        $host = $data->host;
        $this->connectorinstance->setserver($host);
        $openapidefinitionurl = "https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0";
        $this->connectorinstance->set_openapidefinitionurl($openapidefinitionurl);
        $this->connectorinstanceid = $this->connectorinstance->save(true);

        // Path-item instance.
        $this->pathitem = new local_data_importer_connectorpathitem();
        $this->pathitem->set_name("Get Assessments");
        $this->pathitem->set_connector_id(1); // No need to create a new connector instance (?).
        $this->pathitem->set_path_item("/MABS/MOD_CODE/{modcode}");
        $this->pathitem->set_active(true);
        $this->pathitem->set_http_method('GET');
        $this->pathitem->set_plugin_component('importers_course');
        $this->pathitemid = $this->pathitem->save(true);
    }

    /**
     * Add plugin settings for importer
     */
    public function test_add_plugin_additional_settings() {

        // Get the sub-plugin
        $subplugin = $this->pathitem->get_plugin_component() . "_importer";
        $object = new $subplugin($this->pathitemid);
        //For the selected subplugin , get the additional form elements (if available).
        $subpluginadditionalfields['course_visible'] = '0';
        foreach ($subpluginadditionalfields as $settingname => $value) {
            $object->save_setting($settingname, $value);
        }
    }

    /**
     * Update plugin settings for importer
     */
    public function test_update_plugin_additional_settings() {
        // Get the sub-plugin
        $subplugin = $this->pathitem->get_plugin_component() . "_importer";
        $object = new $subplugin($this->pathitemid);
        $subpluginadditionalfields['course_visible'] = '1';
        foreach ($subpluginadditionalfields as $settingname => $settingvalue) {
            $object->save_setting($settingname, $settingvalue);
        }
    }

}