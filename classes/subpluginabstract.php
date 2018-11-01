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
 * Class local_data_importer_subpluginabstract
 */
abstract class local_data_importer_subpluginabstract implements local_data_importer_iSubPlugin {
    /**
     * @var
     */
    public $responses;
    /**
     * @var
     */
    public $params;
    /**
     * @var
     */
    public $pluginname;
    /**
     * @var
     */
    public $plugindescription;


    /**
     * Set responses
     */
    public function set_responses() {
        global $DB;
        foreach ($this->responses as $key => $arrayparam) {
            $table = $arrayparam['table'];
            $fieldname = $arrayparam['name'];
            try {
                if (!empty($arrayparam['table'])) {
                    $columndetails = $DB->get_record_sql('SHOW COLUMNS FROM {' . $table . '} LIKE ? ', [$fieldname]);
                    if ($columndetails) {
                        $arrayparam['type'] = $columndetails->type;
                        $arrayparam['field'] = $columndetails->field;
                        // Add it back to the params.
                         $this->responses[$key] = $arrayparam;
                    }

                }
            } catch (\dml_exception $e) {
                var_dump($e->getMessage());
            }

        }
    }

    /**
     * @return bool
     */
    abstract public function is_available(): bool;

    // TODO: Implement is_available() method.


    /**
     * @return string
     */
    abstract public function get_plugin_name(): string;

    /**
     * @return string
     */
    abstract public function plugin_description(): string;


}