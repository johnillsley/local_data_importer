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
     * @const integer
     */
    const DB_MAX_COLUMN_LENGTH = 30;

    /**
     * @var string
     */
    public $logtable;

    /**
     * @var string
     */
    public $languagepack;

    /**
     * @var integer
     */
    public $pathitemid;

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

    protected function __construct() {

    }

    /**
     * Function to create an entity in Moodle
     *
     * @param array $item contains all the data required to create the entity in Moodle
     * @throws Exception if creating the entity fails.
     * @return void
     */
    abstract protected function create_entity($item = array());

    /**
     * Function to update an entity in Moodle
     *
     * @param array $item contains all the data required to update the entity in Moodle
     * @throws Exception if updating the entity fails.
     * @return void
     */
    abstract protected function update_entity($item = array());

    /**
     * Function to delete an entity in Moodle
     *
     * @param array $item contains the unique identifier(s) needed to delete the entity
     * @throws Exception if deleting the entity fails.
     * @return void
     */
    abstract protected function delete_entity($item = array());

    /**
     * Provides the parameters that can be used to map to web service parameters. These should have keys determined by the
     * subplugin parameters defined in the constructor.
     *
     * @return array|null list of web service parameters that can be used to populate web service urls, null if no parameters
     */
    abstract public function get_parameters();

    /**
     * Provides information about additional form elements associated with the sub plugin.
     *
     * @return array of details about addition form elements required for each instance of the sub plugin
     */
    abstract public function get_additional_form_elements();

    /**
     * Get importer sub plugin object from just pathitemid.
     *
     * @param integer $pathitemid
     * @throws Exception the pathitem could not be found
     * @return object of class associated with the sub plugin
     */
    public static function get_importer($pathitemid) {
        $importer = new local_data_importer_importerinstance($pathitemid); // TODO - IS this class needed - only called from here?
        $plugincomponent = $importer->pathiteminstance->get_plugin_component();
        $pluginobject = $plugincomponent . "_importer";
        return new $pluginobject($pathitemid);
    }

    /**
     * Get plugin name using the sub plugin language pack.
     *
     * @return string
     */
    public function get_plugin_name(): string {
        return get_string('pluginname', $this->languagepack);
    }

    /**
     * This is the top level function which accepts raw data used to create and update entities in Moodle
     * Firstly the raw data is sorted into create, update and delete lists.
     * Each item in the lists is then actioned. create and update items are validated first.
     * If an item fails to validate or the Moodle action fails the exception is logged.
     *
     * @param array $data contains all the raw data
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
     * Sorts all the course items extracted from the external web service into three groups.
     * Using the sub plugin log table for previously imported items these are sorted into
     * either create, update or delete arrays.
     *
     * @param array of $items that have been extracted from the external web service
     * @return object holding separate arrays for create, update and delete items
     */
    private function sort_items($items = array()) {
        global $DB;

        $action = new stdClass();
        $action->create = array();
        $action->update = array();
        $action->delete = array();

        // Get unique key(s) for sub plugin.
        $uniquefields = $this->get_unique_fields();
        $currentlog = $DB->get_records($this->logtable, array("deleted" => 0, "pathitemid" => $this->pathitemid));

        foreach ($items as $item) {

            // Check if unique key values from $item already exist in log table.
            $conditions = array("deleted" => 0, "pathitemid" => $this->pathitemid);
            foreach ($uniquefields as $uniquefield) {
                $logtablefield = $this->get_log_field($uniquefield->table, $uniquefield->field);
                $conditions[$logtablefield] = $item[$uniquefield->table][$uniquefield->field];
            }
            $record = $DB->get_record($this->logtable, $conditions);

            if ($record) {
                // Item has already been imported so check all fields for updates.
                foreach ($this->responses as $table => $field) {
                    foreach ($field as $fieldname => $fieldoptions) {
                        $logtablefield = $this->get_log_field($table, $fieldname);
                        if ($item[$table][$fieldname] != $record->{$logtablefield}) {
                            // A field has been updated so add to update list and get out of loop.
                            $action->update[] = $item;
                            break 2;
                        }
                    }
                }
                unset($currentlog[$record->id]); // Remove item from check list.
            } else {
                // Item needs adding.
                $action->create[] = $item;
            }

        }
            /*
            $key = $item[$uniquekeytable][$uniquekeyfield];
            // TODO - get actual course properties NOT LOGGED as someone could have updated locally!!!!
            if (array_key_exists($key, $current)) {
                // Item already exists so check if it needs updating.
                if ($current[$key]->course_fullname != $item['course']['fullname'] ||
                        $current[$key]->course_shortname != $item['course']['shortname'] ||
                        $current[$key]->course_categories_name != $item['course_categories']['name']) {

                    $action->update[] = $item;
                }
            } else {

            }
            unset($current[$key]); // Take off the list to delete.
        }
            */
        // What is left in $current need to be deleted. Note format of $current is different to $item.
        $action->delete = $currentlog;

        return $action;
    }

    /**
     * From the sub plugin responses definition identify the unique key fields and create references to the plugin log table fields.
     * @return $array of unique fields in the log table.
     */
    protected function get_unique_fields() {
        $uniquefields = array();
        foreach ($this->responses as $table => $field) {
            foreach ($field as $fieldname => $fieldproperties) {
                if (in_array('unique', $fieldproperties)) {
                    $newunique = new stdClass();
                    $newunique->table = $table;
                    $newunique->field = $fieldname;
                    $uniquefields[] = $newunique;
                }
            }
        }
        return $uniquefields;
    }

    /**
     * Interigates database properties so that data validation can be done.
     * Adds database info to databaseproperties class property
     * @return void
     */
    protected function get_database_properties() {
        global $CFG, $DB;

        $params = array();
        $params[0] = $CFG->dbname;

        foreach ($this->responses as $table => $fields) {
            $params[1] = $CFG->prefix . $table;
            foreach ($fields as $field => $options) {
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

    /**
     * Takes a single item for entity creation/updating in Moodle and validates all fields
     * @param array $item
     * @throws Exception if the $item fails to validate and cannot be imported into Moodle.
     * @return array $item with any fields truncated/rounded if allowed
     */
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

    /**
     * Takes a single field
     * @param array $item
     * @throws Exception if the $item fails to validate and cannot be imported into Moodle.
     * @return array $item with any fields truncated/rounded if allowed
     */
    protected function validate_field($table, $field, $value, $required=false, $truncatestrings=false) {

        if (!$fieldmetadata = $this->databaseproperties[$table][$field]) {
            // The table/field combination defined in the subplugin responses parameter does not exist in Moodle.
            throw new \Exception("SUBPLUGIN ERROR: a table/fireld combination defined in the subplugin does not exist in Moodle");
        };

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
                $storagebytes['tinyint']    = 1;
                $storagebytes['smallint']   = 2;
                $storagebytes['mediumint']  = 3;
                $storagebytes['int']        = 4;
                $storagebytes['integer']    = 4;
                $storagebytes['bigint']     = 8;

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
                            "' but value is not a string. Actual data type is ".gettype($value));
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

    /**
     * Logs all the external data that has been used to create, update and delete entities in Moodle
     * Each sub plugin has its our database table to keep track of what has been dealt with automatically
     * This prevents the data_importer affecting entities that have been created manually or other means.
     *
     * @param object $item is a summary of the data from the external source after it has been transformed for import
     * @param integer $time the exact time that has been set for the entity timecreated and/or timemodified
     * @param boolean $delete indicate that $item is in a different format and that deleted flag needs setting in the local log
     * @return void
     */
    public function local_log($item, $time, $delete = false) {
        global $DB;

        if ($delete == true) {
            // Parameter $item is already in logitem format.
            $logitem = $item;
            $logitem->deleted = 1;
        } else {
            $logitem = new stdClass();
            $logitem->deleted = 0;
            // Convert $item information to field names in the log to create $logitem.
            foreach ($item as $table => $field) {
                foreach ($field as $fieldname => $value) {
                    $logitemfield = $this->get_log_field($table, $fieldname);
                    $logitem->$logitemfield = $item[$table][$fieldname];
                }
            }
        }
        $logitem->pathitemid = $this->pathitemid;
        $logitem->timemodified = $time;

        $logtable = $this->logtable;
        $uniquefields = $this->get_unique_fields();

        $conditions = array();
        foreach ($uniquefields as $uniquefield) {
            $logitemfield = $this->get_log_field($uniquefield->table, $uniquefield->field);
            $conditions[$logitemfield] = $logitem->{$logitemfield};
        }

        if ($exists = $DB->get_record($logtable, $conditions)) {
            $logitem->id = $exists->id;
            $DB->update_record($logtable, $logitem);
        } else {
            $logitem->timecreated = $logitem->timemodified;
            $DB->insert_record($logtable, $logitem);
        }
    }

    /**
     * Saves a custom setting for the pathitem instance
     *
     * @param string $name the name of the setting
     * @param string $value the value for the setting
     * @return integer $id the database table id for the setting record
     */
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
            $setting->pathitemid    = $this->pathitemid;
            $setting->name          = $name;
            $setting->value         = $value;
            $id = $DB->insert_record($this->dbsettingstable, $setting);
        }
        return $id;
    }

    /**
     * Gets a custom setting for the pathitem instance
     *
     * @param string $name the name of the setting
     * @return string|null the value for the setting or null if the setting can't be retrieved
     */
    public function get_setting($name) {
        global $DB;

        if ($value = $DB->get_field($this->dbsettingstable, 'value', array('pathitemid' => $this->pathitemid, 'name' => $name))) {
            return $value;
        } else {
            return null; // So that calling function knows that no setting was found.
        }
    }

    /**
     * Ensures the valid field name is used the local log.
     *
     * @param string $table
     * @param string $field
     * @return string local log field name
     */
    private function get_log_field($table, $field) {

        $logfield = substr($table . "_" . $field, 0, self::DB_MAX_COLUMN_LENGTH);

        return $logfield;
    }

    /**
     * Writes a single entry to the exception log
     *
     * @param string $action the type of activity that caused the exception
     * @param Exception object $e
     * @param object $data with additional information retrieved from the exception
     * @return boolean indication if logging the exception was successful
     */
    protected function exception_log($action, $e, $data) {
        global $DB;

        $exceptionlog = new stdClass();
        $exceptionlog->pathitemid   = $this->pathitemid;
        $exceptionlog->action       = $action;
        $exceptionlog->data         = serialize($data);
        $exceptionlog->exception    = $e->getMessage();
        $exceptionlog->time         = time();

        if ($DB->insert_record("local_data_importer_errors", $exceptionlog)) {
            return true;
        } else {
            return false;
        }
    }
}