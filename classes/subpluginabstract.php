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
     * Plugin Name
     * @var
     */
    public $pluginname;
    /**
     * Plugin Description
     * @var
     */
    public $plugindescription;

    /**
     * @var string
     */
    public $tablefieldseperator = ":";

    /**
     * Set responses
     */
    public function set_responses() {
        global $DB;
        foreach ($this->responses as $tablename => $arrayparam) {

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

    /** Find a response parameter from a web service response iteratively
     * @param array $haystack
     * @param $needle
     * @return mixed|null
     */
    public function find_response_parameter(array $haystack, $needle) {
        $value = null;
        $iterator = new RecursiveArrayIterator($haystack);
        $recursive = new RecursiveIteratorIterator(
            $iterator,
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursive as $key => $value) {
            if ($key === $needle) {
                return $value;
            }
        }
        return $value;
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

    /**
     * @return array
     */
    public function get_importers() {
        global $DB;
        $pathitems = array();
        // For this particular plugin, get all the importers it is attached to.
        $pathitem = new local_data_importer_connectorpathitem();
        $pathitems = $pathitem->get_by_subplugin($this->pluginname);
        return $pathitems;
    }

    public function validate_field($fieldmetadata, $value, $required=false, $truncatestrings=false) {

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

        switch($fieldmetadata->data_type) {

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
                            "DATA VALIDATION ERROR: empty string for a field that the subplugin specifies as required."
                            );
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
}