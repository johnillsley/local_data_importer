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
            'course' => array('fullname', 'shortname  ', 'idnumber'),
            'course_categories' => array('name')
        );

        $this->params = array('sits_mappings' => array('sits_code', 'acyear', 'period_code'));
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

    /**
     * Traverse through the params property and return actual data to be passed to the WS call
     */
    public function get_parameters_for_ws() {
        // List of sits mapping mod_codes to pass back.
        global $DB;
        foreach ($this->params as $table => $fields) {
            foreach ($fields as $field) {
                $params = $DB->get_records($table, [], $field);
            }
        }
        return $params;
    }
}