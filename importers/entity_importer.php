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
 * This file defines a base class for all entity importers.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

abstract class data_importer_entity_importer {

    /**
     * @var string
     */
    public $importerplugin; // TODO - can this be replaced by $plugin->component in the version.php file?

    /**
     * @var string
     */
    public $languagepack;

    /**
     * @var integer
     */
    protected $pathitemid;

    /**
     * @var string
     */
    protected $tablefieldseperator = ":";

    /**
     * @var string
     */
    protected $dbsettingstable = "local_data_importer_settings";

    /**
     * @var array
     */
    public $responses = array();

    /**
     * @var array
     */
    public $parameters = array();

    /**
     * @var array
     */
    protected $databaseproperties = array();

    /**
     * @var string
     */
    protected $uniquekey;

    abstract protected function create_entity($item = array());

    abstract protected function update_entity($item = array());

    abstract protected function delete_entity($item = array());

    abstract protected function sort_items($items = array());

    abstract public function provide_web_service_parameters();

    abstract public function get_additional_form_elements();

    /**
     * Get importer object from just pathitemid.
     * @return string
     */
    public static function get_importer($pathitemid) {
        $importer = new local_data_importer_importerinstance($pathitemid);
        $plugincomponent = $importer->pathiteminstance->get_plugin_component();
        $pluginobject = "data_importer_" . $plugincomponent . "_importer";
        return new $pluginobject($pathitemid);
    }

    /**
     * Get plugin name.
     * @return string
     */
    public function get_plugin_name(): string {
        return get_string('pluginname', $this->languagepack);
    }

    /**
     * This is the top level function which accepts
     * Creates a record of the course creation locally so that it will not be created again.
     *
     * @param array $item contains all the data required to create a course
     * @throws Exception if the course could not be created
     * @return void
     */
    public function do_imports($data) {

        $this->get_database_properties();

        // Sort items into create, update and delete.
        $actions = $this->sort_items($data);

        foreach ($actions->create as $create) {
            try {
                $create = $this->validate_item($create);
                $this->create_entity($create);
            } catch (\Exception $e) {
                $this->exception_log('create', $e, $create);
            }
        }

        foreach ($actions->update as $update) {
            try {
                $update = $this->validate_item($update);
                $this->update_entity($update);
            } catch (\Exception $e) {
                $this->exception_log('update', $e, $update);
            }
        }

        foreach ($actions->delete as $delete) {
            try {
                $this->delete_entity($delete);
            } catch (\Exception $e) {
                $this->exception_log('delete', $e, $delete);
            }
        }
    }

    /**
     * Interigates database properties so that data validation.
     * @return string
     */
    protected function get_database_properties() {
        global $CFG, $DB;

        $params = array();
        $params[0] = $CFG->dbname;

        foreach ($this->responses as $table => $fields) {
            $params[1] = $CFG->prefix . $table;
            foreach ($fields as $field) {
                $params[2] = $field;
                $fielddetails = $DB->get_record_sql("
                        SELECT
                          data_type
                        , column_type
                        , is_nullable
                        , column_default
                        , character_maximum_length
                        , numeric_precision
                        , numeric_scale
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = ?
                        AND TABLE_NAME = ?
                        AND COLUMN_NAME = ?
                        ", $params);

                $this->databaseproperties[$table][$field] = $fielddetails;
            }
        }
    }

    protected function validate_item($item = array()) {

        // TODO - check if primary key is not null.
        foreach ($item as $table => $fields) {
            foreach ($fields as $field => $value) {
                try {
                    $item[$table][$field] = $this->validate_field($table, $field, $value);
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }
        return $item;
    }

    protected function validate_field($table, $field, $value, $required = false, $truncatestrings = false) {

        $fieldmetadata = $this->databaseproperties[$table][$field];

        // Check if value is null.
        if (is_null($value)) {
            if ($required == true) {
                throw new \Exception("DATA VALIDATION ERROR: subplugin defines that this field cannot be null.");
            } else if ($fieldmetadata->is_nullable == 'YES' && isset($fieldmetadata->column_default)) {
                // Will use field default value. Return null.
                return $value;
            } else {
                // DB field defines value can't be null.
                throw new \Exception("DATA VALIDATION ERROR: value is null but db field does not allow null values and has no default value to use instead.");
            }
        }

        switch ($fieldmetadata->data_type) {

            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':

                // Check if value is an integer or string that can be used as an integer for DB write.
                if (!ctype_digit(ltrim((string)$value, '-'))) {
                    throw new \Exception("DATA VALIDATION ERROR: value is not integer for field " . $fieldmetadata->data_type);
                }
                $signed = (strpos($fieldmetadata->column_type, 'unsigned') !== false) ? false : true;
                $storagebytes = array();
                $storagebytes['tinyint'] = 1;
                $storagebytes['smallint'] = 2;
                $storagebytes['mediumint'] = 3;
                $storagebytes['int'] = 4;
                $storagebytes['integer'] = 4;
                $storagebytes['bigint'] = 8;

                if ($signed) {
                    $minvalue = -1 * pow(2, ($storagebytes[$fieldmetadata->data_type] * 8) - 1);
                    $maxvalue = pow(2, ($storagebytes[$fieldmetadata->data_type] * 8) - 1) - 1;
                } else {
                    $minvalue = 0;
                    $maxvalue = pow(2, $storagebytes[$fieldmetadata->data_type] * 8) - 1;
                }
                if ($value > $maxvalue || $value < $minvalue) {
                    throw new \Exception("DATA VALIDATION ERROR: value is outside allowable range for " .
                        $fieldmetadata->data_type);
                }
                return (integer)$value;
                break;

            case 'varchar':
            case 'char':
            case 'tinytext': // 255 characters.
            case 'text': // 65535 characters.
            case 'mediumtext': // 16777215 characters.
            case 'longtext': // 4294967295 characters.

                if (!is_string($value)) {
                    // Not a string.
                    throw new \Exception("DATA VALIDATION ERROR: db field is type '" . $fieldmetadata->data_type .
                        "' but value is not a string. Actual data type is " . gettype($value));
                } else if (strlen($value) > $fieldmetadata->character_maximum_length) {
                    // String too long.
                    if ($truncatestrings == true) {
                        $value = substr($value, 0, $fieldmetadata->character_maximum_length);
                        return (string)$value; // Return truncated version of string.
                    } else {
                        throw new \Exception("DATA VALIDATION ERROR: string has too many characters for database field " .
                            $fieldmetadata->data_type . "(" . $fieldmetadata->character_maximum_length . ").");
                    }
                } else if (strlen($value) == 0 && $required == true) {
                    // String empty.
                    throw new \Exception(
                        "DATA VALIDATION ERROR: empty string for a field that the subplugin specifies as required.");
                } else {
                    return (string)$value;
                }
                break;

            case 'float':
            case 'double':
            case 'decimal':

                if (!preg_match('/^-?(?:\d+|\d*\.\d+)$/', (string)$value)) {
                    throw new \Exception("DATA VALIDATION ERROR: value is not a floating point number but db field is type " .
                        $fieldmetadata->data_type);
                } else {
                    $value = (float)$value;
                }
                $digits = explode(".", $value);
                $integerlength = strlen(ltrim($digits[0], '-'));
                $fractionallength = (count($digits) == 1) ? 0 : strlen($digits[1]); // Check for no decimal point.

                if ($integerlength > ($fieldmetadata->numeric_precision - $fieldmetadata->numeric_scale)) {
                    throw new \Exception("DATA VALIDATION ERROR: floating point value out of range.");
                } else if ($fractionallength > $fieldmetadata->numeric_scale && $truncatestrings == false) {
                    throw new \Exception("DATA VALIDATION ERROR: floating point value out of range.");
                }
                return round($value, $fieldmetadata->numeric_scale);
                break;

            default:
                throw new \Exception("DATA VALIDATION ERROR: field of type '" . $fieldmetadata->data_type .
                    "' cannot be validated.");
                break;
        }
    }

    public function local_log($logitem) {
        global $DB;

        $logitem->pathitemid = $this->pathitemid;
        if (is_null($logitem->deleted)) {
            $logitem->deleted = 0;
        }
        $logtable = $this->importerplugin;
        $uniquefield = str_replace(":", "_", $this->uniquekey);
        // TODO - what happens if there is more than one field as unique key?
        if ($exists = $DB->get_record($logtable, array($uniquefield => $logitem->{$uniquefield}))) {
            $logitem->id = $exists->id;
            $DB->update_record($logtable, $logitem);
        } else {
            $logitem->timecreated = $logitem->timemodified;
            $DB->insert_record($logtable, $logitem);
        }
    }

    public function save_setting($name, $value) {
        global $DB;
        // Check if an existing record needs updating.
        if ($setting = $DB->get_record($this->dbsettingstable, array('pathitemid' => $this->pathitemid, 'name' => $name))) {
            // Already exists.
            $setting->value = $value;
            $DB->update_record($this->dbsettingstable, $setting);
            $id = $setting->id;
        } else {
            // No existing record.
            $setting = new stdClass();
            $setting->pathitemid = $this->pathitemid;
            $setting->name = $name;
            $setting->value = $value;
            $id = $DB->insert_record($this->dbsettingstable, $setting);
        }
        return $id;
    }

    public function get_setting($name) {
        global $DB;

        if ($value = $DB->get_field($this->dbsettingstable, 'value', array('pathitemid' => $this->pathitemid, 'name' => $name))) {
            return $value;
        } else {
            return null; // So that calling function knows that no setting was found.
        }
    }

    protected function exception_log($action, $e, $data) {
        global $DB;

        $exceptionlog = new stdClass();
        $exceptionlog->pathitemid = $this->pathitemid;
        $exceptionlog->action = $action;
        $exceptionlog->data = serialize($data);
        $exceptionlog->exception = $e->getMessage();
        $exceptionlog->time = time();

        if ($DB->insert_record("local_data_importer_errors", $exceptionlog)) {
            return true;
        }
    }
}