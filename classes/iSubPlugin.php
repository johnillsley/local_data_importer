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

/**
 * Interface local_data_importer_iSubPlugin
 */
interface local_data_importer_iSubPlugin {
    /**
     * @return mixed
     */
    public function set_responses();

    /**
     * @return bool
     */
    public function is_available(): bool;

    /**
     * @return string
     */
    public function get_plugin_name(): string; // Used to identify the component name to be used in drop-down for example.

    /**
     * @return string
     */
    public function plugin_description(): string; // Method used to describe the functionality of the plugin.
}