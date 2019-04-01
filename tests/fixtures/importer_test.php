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
 * This file defines a test entity importer.
 *
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/data_importer/importers/entity_importer.php'); // Parent class definition.

/**
 * Class representing a test entity importer.
 */
class importers_test_importer extends data_importer_entity_importer {

    public function __construct($pathitemid) {

        parent::__construct($pathitemid);

        $this->logtable = 'local_data_importer_test';
        $this->languagepack = 'importers_test';

        $this->responses = array(
                'course' => array(
                        'fullname' => array(),
                        'shortname' => array(),
                        'idnumber' => array('unique')
                ),
                'course_categories' => array(
                        'name' => array('unique')
                )
        );
        
        $this->parameters = array('subplugin_param1', 'subplugin_param2', 'subplugin_param3');
    }

    protected function create_entity($item = array()) {

        $this->local_log($item, time(), 'created');
    }

    protected function update_entity($item = array()) {

        $this->local_log($item, time(), 'updated');
    }

    protected function delete_entity($item = array()) {
        
        $this->local_log($item, time(), 'deleted');
    }

    public function get_parameters() {

        $testparams = array(
                array(
                        "subplugin_param1" => "value1",
                        "subplugin_param2" => "value2",
                        "subplugin_param3" => "value9",
                ),
                array(
                        "subplugin_param1" => "value1",
                        "subplugin_param2" => "value2",
                        "subplugin_param3" => "value6",
                ),
                array(
                        "subplugin_param1" => "",
                        "subplugin_param2" => "value7",
                        "subplugin_param3" => "value8",
                ),

                array(
                        "subplugin_param1" => "value9",
                        "subplugin_param2" => "value3",
                ),
        );
        return $testparams;
    }

    public function get_additional_form_elements() {

        $additionalsettings = array();

        $settingname = 'delete_courses';
        $options = array();
        $options[0] = get_string('keepcourses', 'importers_course');
        $options[1] = get_string('deletecourse', 'importers_course');

        $additionalsettings[] = $this->get_html_additional_setting($settingname, $options);
        // TODO - What about the label?

        // TODO - how about a setting to prevent update of course fullname and/or shortname?
        // TODO - Don't do updates - or only update categories.
        return $additionalsettings;
    }
}