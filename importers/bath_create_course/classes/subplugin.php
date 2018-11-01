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
defined('MOODLE_INTERNAL') || die();

/**
 * Class importers_bath_create_course_subplugin
 */
class importers_bath_create_course_subplugin extends local_data_importer_subpluginabstract {
    /**
     * importers_bath_create_course_subplugin constructor.
     */
    public function __construct() {
        $this->responses = array(
            array('name' => 'fullname', 'table' => 'course'),
            array('name' => 'shortname', 'table' => 'course'),
            array('name' => 'name', 'table' => 'course_categories'),
            array('name' => 'idnumber', 'table' => 'course')
        );
        $this->params = array('name' => 'sits_code', 'table' => 'sits_mapping');
        $this->set_responses();
    }

    /**
     * See if the plugin is available
     * @return bool
     */
    public function is_available(): bool {
        // TODO make this a db setting for the sub-plugin.
        return true;
    }

    /**
     * Get plugin name
     * @return string
     */
    public function get_plugin_name(): string {
        $this->pluginname = get_string('pluginname', 'local_create_course');
    }

    /**
     * Get plugin description
     * @return string
     */
    public function plugin_description(): string {
        $this->plugindescription = get_string('plugindescription', 'local_create_course');
    }


    //TODO New function called "available_params_for_url" , "actual_params_for_url"
    // TODO additional form elements
}