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
 * Class importers_bath_add_user_subplugin
 */
class importers_bath_add_user_subplugin extends local_data_importer_subpluginabstract {
    /**
     * importers_bath_add_user_subplugin constructor.
     */
    public function __construct() {
        $this->responses = array(
            'user' => array('idnumber')
        );

        $this->params = array('user' => array('username'));
    }

    /**
     * See if the plugin is available
     * @return bool
     */
    public function is_available(): bool {
        // TODO make this a db setting for the sub-plugin.
        return true;
    }

    public function consume_data($data) {
        echo "yay I've got something from the fetcher";
        // I should get an identifiable response from the web service, otherwise I reject it.


        var_dump($data);
    }

    /**
     * Get plugin name
     * @return string
     */
    public function get_plugin_name(): string {
        $this->pluginname = get_string('pluginname', 'importers_bath_add_user');
    }

    /**
     * Get plugin description
     * @return string
     */
    public function plugin_description(): string {
        $this->plugindescription = get_string('plugindescription', 'local_create_course');
    }

    public function get_parameters_for_ws() {
        // list of usernames.
        global $DB;
        $DB->get_records($table, $structure);

    }

}