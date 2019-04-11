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
 * @copyright  2019 University of Bath
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
    private $dbsettingstable = "local_data_importer_setting";

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
    private $parameterfilter = array();

    /**
     * @var string
     */
    protected $parentimporter;
    
    /**
     * @var object
     */
    public $summary;

    /**
     * @var array
     */
    protected $databaseproperties = array();

    /**
     * @var array
     */
    protected $mapped_unique_fields = array();
    
    protected function __construct($pathitemid) {

        $this->pathitemid   = $pathitemid;
        $this->mapped_unique_fields = $this->get_mapped_unique_fields(); // TODO - this not working??

        $this->summary = new stdClass();
        $this->summary->new         = 0;
        $this->summary->changed     = 0;
        $this->summary->unchanged   = 0;
        $this->summary->removed     = 0;
        $this->summary->created     = 0;
        $this->summary->updated     = 0;
        $this->summary->deleted     = 0;
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
     * @param object $item contains the unique identifier(s) needed to delete the entity
     * @throws Exception if deleting the entity fails.
     * @return void
     */
    abstract protected function delete_entity($item);

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
     * Gets sub-plugin parameter mapping options and then adds global parameter mapping options
     *
     * @return array of parameter names that can be mapped
     */
    public function get_parameter_options() {

        $parameters = $this->parameters;
        // Now add global parameter options.
        $globalparametermethods = get_class_methods("local_data_importer_global_parameters");
        foreach ($globalparametermethods as $method) {
            $parameters[] = 'global_' . $method;
        }

        return $parameters;
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
    public function do_imports($sorteddata) {

        $this->get_database_properties(); // For validation of data.

        foreach ($sorteddata->create as $create) {
            try {
                $create = $this->validate_item($create);
                $this->create_entity($create);
            } catch (\Throwable $e) {
                local_data_importer_error_handler::log($e, $this->pathitemid);
            }
        }

        foreach ($sorteddata->update as $update) {
            try {
                $update = $this->validate_item($update);
                $this->update_entity($update);
            } catch (\Throwable $e) {
                local_data_importer_error_handler::log($e, $this->pathitemid);
            }
        }

        foreach ($sorteddata->delete as $delete) {
            try {
                $this->delete_entity($delete);
            } catch (\Throwable $e) {
                local_data_importer_error_handler::log($e, $this->pathitemid);
            }
        }
    }

    /**
     * Sorts all the course items extracted from the external web service into three groups.
     * Using the sub plugin log table for previously imported items these are sorted into
     * either create, update or delete arrays.
     *
     * @param array of $items that have been extracted from the external web service
     * @throws exception if a unique field is empty.
     * @return object holding separate arrays for create, update and delete items
     */
    public function sort_items($items = array()) {
        global $DB;

        $action = new stdClass();
        $action->create = array();
        $action->update = array();
        $action->delete = array();
        $uniquefields = $this->get_mapped_unique_fields(); // Combination of all unique fields that were mapped.
        
        $conditions = array();
        $conditions["deleted"] = 0;
        $conditions["pathitemid"] = $this->pathitemid;
        $currentlog = $DB->get_records($this->logtable, $conditions);

        foreach ($items as $item) {

            try {
                // Check if unique key values from $item already exist in log table.
                $conditions = array("deleted" => 0, "pathitemid" => $this->pathitemid);
                foreach ($uniquefields as $uniquefield) {
                    if (empty($item[$uniquefield->table][$uniquefield->field])) {
                        // TODO - CHECK IF THIS WORKS - NOT SEEN WORKING YET.
                        throw new Exception("A unique field is empty - " .
                                serialize($item[$uniquefield->table][$uniquefield->field]));
                    }
                    $logtablefield = $this->get_log_field($uniquefield->table, $uniquefield->field);
                    $conditions[$logtablefield] = $item[$uniquefield->table][$uniquefield->field];
                }
                $record = $DB->get_record($this->logtable, $conditions);

                if ($record) {
                    // Item has already been imported so check all fields for updates.
                    foreach ($item as $table => $field) {
                        foreach ($field as $fieldname => $value) {
                            $logtablefield = $this->get_log_field($table, $fieldname);
                            if ($value != $record->{$logtablefield}) {
                                // A field has been updated so add to update list and get out of loop.
                                $action->update[] = $item;
                                $this->summary->changed++;
                                unset($currentlog[$record->id]); // Remove item from check list.
                                break 2; // Move onto next item.
                            }
                        }
                    }
                    $this->summary->unchanged++;
                    unset($currentlog[$record->id]); // Remove item from check list.

                } else {
                    // Item needs adding.
                    $action->create[] = $item;
                    $this->summary->new++;
                }
            } catch (\Throwable $e) {
                print($e->getMessage()); exit;
                local_data_importer_error_handler::log($e, $this->pathitemid);
            }
        }
        // What is left in $current need to be deleted. Note format of $currentlog is different to $item.
        $action->delete = $currentlog;
        $this->summary->removed = count($currentlog);

        return $action;
    }
    
    /**
     * From the sub plugin responses definition identify the unique key fields and create references to the plugin log table fields.
     * @return $array of unique fields in the log table.
     */
    private function get_unique_fields() {
        // TODO - Call this function in the mapping admin process and indicate unique fields on the form. Throw exception if there aren't any unique fields.
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
     * From the unique fields list remove the itemms that weren't mapped when the importer was configured.
     * @return $array of mapped unique fields in the log table.
     */
    private function get_mapped_unique_fields() {

        $pathitemresponse = new local_data_importer_pathitem_response();
        $responsemappings = $pathitemresponse->get_by_pathitem_id($this->pathitemid);

        $uniquefields = $this->get_unique_fields();
        $mappeduniquefields = array();
        foreach ($responsemappings as $responsemapping) {
            $mappedtable = $responsemapping->get_pluginresponse_table();
            $mappedfield = $responsemapping->get_pluginresponse_field();
            foreach ($uniquefields as $uniquefield) {
                if ($uniquefield->table == $mappedtable && $uniquefield->field == $mappedfield) {
                    $mappeduniquefields[] = $uniquefield;
                    break;
                }
            }
        }
        return $mappeduniquefields;
    }

    /**
     * Interigates database properties so that data validation can be done.
     * Adds database info to databaseproperties class property
     * @return void
     */
    private function get_database_properties() {
        global $CFG, $DB;

        $params = array();
        $params[0] = $CFG->dbname;

        foreach ($this->responses as $table => $fields) {
            if ($table != 'other') {
                // Other is not a table - just used to store extra responses in the local log.
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
    }

    /**
     * Takes a single item for entity creation/updating in Moodle and validates all fields
     * @param array $item
     * @throws Exception if the $item fails to validate and cannot be imported into Moodle.
     * @return array $item with any fields truncated/rounded if allowed
     */
    private function validate_item($item = array()) {

        // TODO - check if primary key is not null.
        foreach ($item as $table => $fields) {
            if ($table != 'other') {
                // Other is not a table - just used to store extra responses in the local log.
                foreach ($fields as $field => $value) {
                    if (!isset($this->databaseproperties[$table][$field])) {
                        throw new \Exception("SUBPLUGIN ERROR: a table/field combination ($table/$field) defined in the subplugin does not exist in Moodle");
                    } else {
                        $fieldmetadata = $this->databaseproperties[$table][$field];
                    }
                    $item[$table][$field] = $this->validate_field($fieldmetadata, $value);
                }
            }
        }
        return $item;
    }

    /**
     * Takes a single value and checks if it can be written to the Moodle database.
     * @param object $fieldmetadata contains the database properties for the field where $value is to be written.
     * @param mixed $value to be written to Moodle database.
     * @param boolean $required enforces that the value must be set (for future development).
     * @param boolean $truncatestrings allows the value to be truncated (for future development).
     * @throws Exception if the $item fails to validate and cannot be imported into Moodle.
     * @return mixed value truncated/rounded if allowed.
     */
    private function validate_field($fieldmetadata, $value, $required=false, $truncatestrings=false) {

        // Check if value is null.
        if (is_null($value)) {
            if ($required == true) {
                throw new \Exception("DATA VALIDATION ERROR: subplugin defines that this field cannot be null.");
            } else if ($fieldmetadata->is_nullable == 'YES' && isset($fieldmetadata->column_default)) {
                // Will use field default value. Return null.
                return $value;
            } else {
                // DB field defines value can't be null.
                throw new \Exception("DATA VALIDATION ERROR: value is null but db field does not allow null values.");
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
     * @param array $item is a summary of the data from the external source after it has been transformed for import
     * @param integer $time the exact time that has been set for the entity timecreated and/or timemodified
     * @param boolean $delete indicate that $item is in a different format and that deleted flag needs setting in the local log
     * @return void
     */
    protected function local_log($item, $time, $action) {
        global $DB;

        $this->summary->{$action}++;

        if ($action == 'deleted') {
            // Parameter $item is already in logitem format.
            $logitem = (object)$item;
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
        $uniquefields = $this->get_mapped_unique_fields();
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
     * Ensures the valid field name is used the local log. Moodle maximum field name length is 30 characters.
     *
     * @param string $table
     * @param string $field
     * @return string local log field name
     */
    private function get_log_field($table, $field) {

        // Can't have field names longer than 30 characters in Moodle.
        $logfield = substr($table . "_" . $field, 0, self::DB_MAX_COLUMN_LENGTH);

        return $logfield;
    }

    /**
     * Stores parameter filters after checking if they are valid.
     *
     * @param array $filters
     * @throws Exception if the key of a filter does not exist as defined sub-plugin parameter.
     * @return void
     */
    public function set_parameter_filter($filters = array()) {

        unset($this->parameterfilter);
        foreach ($filters as $key => $value) {
            // Check if the filter key is one of the defined parameters.
            $check = false;
            foreach($this->parameters as $parameter) {
                if ($key == $parameter) {
                    $check = true;
                    break;
                }
            }
            if (!$check) {
                throw new \Exception("Parameter filter is not valid for this importer instance.");
            }
            $this->parameterfilter[$key] = $value;
        }
    }

    /**
     * Creates the additional SQL for the parameter filter(s).
     *
     * @return string $filtersql
     */
    protected function get_parameter_filter_sql() {

        $filtersql = "";
        if (count($this->parameterfilter) > 0) {
            foreach ($this->parameterfilter as $field => $value)
                $filtersql .= " AND " . $field . " = '" . $value . "'";
        }
        return $filtersql;
    }
}