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
 * A class that gets all external data from a web service defined by a pathitem
 * and then transforms the data ready to be processed by the importer class.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/local/data_importer/importers/entity_importer.php");

class local_data_importer_data_fetcher {

    /**
     * @const string prefix used for parameter mappings at global level
     */
    const GLOBALPREFIX = 'global_';

    /**
     * @var object of class extended from data_importer_entity_importer
     */
    private $importer;

    /**
     * @var string
     */
    private $uritemplate;

    /**
     * @var object of class local_data_importer_connectorpathitem
     */
    private $pathitem;

    /**
     * @var object of class local_data_importer_connectorinstance
     */
    private $connector;

    /**
     * @var object of class local_data_importer_http_connection
     */
    private $httpclient;

    /**
     * Constructor for local_data_importer_data_fetcher class
     * Instantiates all other required objects
     *
     * @param integer $pathitemid the unique id for the pathitem
     * @throws Exception if caught from any of the associated objects
     * @return null
     */
    public function __construct($pathitemid) {

        $this->importer = data_importer_entity_importer::get_importer($pathitemid);

        $connectorpathitem = new local_data_importer_connectorpathitem();
        $this->pathitem = $connectorpathitem->get_by_id($pathitemid);
        $this->uritemplate = $this->pathitem->get_path_item();

        $connectorinstance = new local_data_importer_connectorinstance();
        $this->connector = $connectorinstance->get_by_id($this->pathitem->get_connector_id());

        $this->httpclient = new local_data_importer_http_connection();
        $this->httpclient->create_client($this->connector->get_server(), $this->connector->get_server_apikey());
    }

    /**
     * Method to process a single importer update Moodle accordingly. This may consist of more than one web service calls
     * depending on what parameters need to be sent.
     * Catches any exceptions thrown by other classes and writes to the central exception log
     *
     * @throws Exception if any update fails.
     * @return object summary of sorted items
     */
    public function update_from_pathitem() {

        $starttime = time();
        $this->pathitem->set_start_time($starttime);

        // TODO - what happens if $intparamslist is empty? - ONE ELEMENT EQUAL TO NULL!!!
        $intparamslist = $this->importer->get_parameters();
        $intparamslist = $this->clean_parameters($intparamslist);
        foreach ($intparamslist as $internalparams) {
            try {
                $externalparams = $this->transform_parameters2($internalparams);
                $relativeuri = $this->build_relativeuri($this->uritemplate, $externalparams);
                $externalresponse = $this->httpclient->get_response($relativeuri);
                $internalresponse = $this->transform_response($externalresponse);
                $sortedinternalresponse = $this->importer->sort_items($internalresponse, $internalparams);
                $this->importer->do_imports($sortedinternalresponse);
            } catch (Exception $e) {
                    print $e->getMessage();
                    local_data_importer_error_handler::log($e, $this->pathitem->id);
            }
        }

        /* START OF OLD CODE
        $transform = $this->get_parameter_mappings();
        $parameterslist = $this->transform_parameters($transform);

        foreach ($parameterslist as $parameters) {
            try {
                $relativeuri = $this->build_relativeuri($this->uritemplate, $parameters);
                $externaldata = $this->httpclient->get_response($relativeuri);
                $internaldata = $this->transform_response($externaldata);
                $sortedinternaldata = $this->importer->sort_items($internaldata);
                $this->importer->do_imports($sortedinternaldata);
            } catch (Exception $e) {
                print $e->getMessage();
                local_data_importer_error_handler::log($e, $this->pathitem->id);
            }
        }
        */
        $duration = time() - $starttime;
        $this->pathitem->set_duration_time($duration);

        return $this->importer->summary;
    }

    /**
     * Takes the parameters from the subplugin and transforms them using parameter mappings so that they can be used in
     * web service requests.
     *
     * @return array of parameter records to be used to populate web service URL requests.
     */
    /*
    public function transform_parameters($transform) {

        $internalparameters = $this->importer->get_parameters();
        $externalparameters = array();
        $externalsubplugin = array();

        if (count($internalparameters) > 0) {
            foreach ($internalparameters as $internalitem) {
                $externalitem = array();
                foreach ($internalitem as $key => $value) {
                    // Check if the internal parameter has been mapped.
                    if (array_key_exists($key, $transform->subpluginparams)) {
                        $externalitem[$transform->subpluginparams[$key]] = $value;
                    }
                }
                $externalsubplugin[] = $externalitem;
            }
            if (count($transform->globalparams) > 0) {
                // Now add on the global parameters.
                foreach ($externalsubplugin as $extsubplugin) {
                    foreach ($transform->globalparams as $globalparams) {
                        $externalparameters[] = array_merge($extsubplugin, $globalparams);
                    }
                }
            } else {
                // No global parameters to add.
                $externalparameters = $externalsubplugin;
            }
        } else if (count($transform->globalparams) > 0) {
            // No sub-plugin parameters but there are globals.
            $externalparameters = $transform->globalparams;
        }
        // Make final array elements unique.
        $externalparameters = array_map("unserialize", array_unique(array_map("serialize", $externalparameters)));

        return $externalparameters;
    }
    */

    /**
     * Takes the raw parameters data received from a sub-plugin and removes the id field from each record and
     * any other fields that haven't been mapped for the importer. It then removes any duplicate records before
     * returning the cleaned parameter list.
     *
     * @param array $parameters
     * @return array $cleanparams
     */
    private function clean_parameters($parameterslist) {

        $pathitemparam = new local_data_importer_pathitem_parameter();
        $parametermappings = $pathitemparam->get_by_pathitem_id($this->pathitem->id);
        $cleanparams = array();

        foreach ($parameterslist as $parameters) {
            $cleaned = array();
            foreach ($parametermappings as $parametermapping) {
                $mappedsubpluginparam = $parametermapping->get_subplugin_parameter();
                $cleaned[$mappedsubpluginparam] = $parameters->$mappedsubpluginparam;
            }
            $cleanparams[] = $cleaned;
        }
        // Make final array elements unique.
        $cleanparams = array_map("unserialize", array_unique(array_map("serialize", $cleanparams)));

        return $cleanparams;
    }

    public function transform_parameters2($intparams) {

        $pathitemparam = new local_data_importer_pathitem_parameter();
        $parametermappings = $pathitemparam->get_by_pathitem_id($this->pathitem->id);

        $extparams = array();
        foreach ($parametermappings as $parametermapping) {
            $subpluginparam = $parametermapping->get_subplugin_parameter();
            $pathitemparam = $parametermapping->get_pathitem_parameter();
            $extparams[$pathitemparam] = $intparams[$subpluginparam];
        }
        return $extparams;
    }

    /**
     * Takes one or more global parameter option arrays and combines all the combinations from each array.
     * This allows for multiple global parameters to be mapped to pathitems and all combinations of these will be used.
     * https://stackoverflow.com/questions/8567082/how-to-generate-in-php-all-combinations-of-items-in-multiple-arrays
     *
     * @param array of global parameter option arrays
     * @return array of combinations
     */
    /*
    private function get_global_param_combinations($arrays) {

        // TODO - Need to remove any second level empty arrays - causes divided by zero error.
        $counts = array_map("count", $arrays);
        $total = array_product($counts); // Total number of combinations.
        $result = array();

        $combinations = array();
        $currentcombinations = $total;

        foreach ($arrays as $field => $vals) {
            $currentcombinations = $currentcombinations / $counts[$field];
            $combinations[$field] = $currentcombinations;
        }

        for ($i = 0; $i < $total; $i++) {
            foreach ($arrays as $field => $vals) {
                $result[$i][$field] = $vals[($i / $combinations[$field]) % $counts[$field]];
            }
        }
        return $result;
    }
    */

    /**
     * Produces a relative uri from the parameters that originated in the sub plugin.
     *
     * @throws Exception if the relative uri has not had all values substituted
     * @return string relative url ready for the web service request
     */
    private function build_relativeuri($relativeuri, $parameters) {

        if (is_array($parameters)) {
            foreach ($parameters as $name => $value) {
                if ($value == "") {
                    throw new Exception('URL parameter (' . $name . ') is empty string.');
                }
                // TODO - Following line better as urlencode but will this work in stutalk
                $value = str_replace('/', '-', $value); // Better without this line.
                $relativeuri = str_replace( '{' . $name . '}', urlencode($value), $relativeuri);
            }
        }
        if (!$this->check_uri($relativeuri)) {
            throw new Exception('The relative URI has missing values.');
        }
        return $relativeuri;
    }

    /**
     * Produces a transform that is used to translate internal parameters to web service parameters.
     *
     * @throws Exception if the relative uri has not had all values substituted
     * @return object $transform containing two arrays one for sub plugin parameters the other for global parameters
     */
    /*
    private function get_parameter_mappings() {

        $parammapper = new local_data_importer_pathitem_parameter();
        $mappings = $parammapper->get_by_pathitem_id($this->pathitem->id);

        $transform = new stdClass();
        $transform->subpluginparams = array();
        $transform->globalparams = array();
        $globalparams = array();

        foreach ($mappings as $mapping) {
            $subpluginparam = $mapping->get_subplugin_parameter();
            $pathitemparam = $mapping->get_pathitem_parameter();

            // Check if the parameter is global.
            if (strpos($subpluginparam, self::GLOBALPREFIX) === 0) {
                $globalmethod = substr($subpluginparam, strlen(self::GLOBALPREFIX));
                $globalparams[$pathitemparam] = local_data_importer_global_parameters::$globalmethod();
            } else {
                $transform->subpluginparams[$subpluginparam] = $pathitemparam;
            }
        }
        if (count($globalparams) > 0) {
            $transform->globalparams = $this->get_global_param_combinations($globalparams);
        }
        return $transform;
    }
    */

    /**
     * Checks a relative url to make sure it has no un-substituted values
     *
     * @param string $uri the relative uri to check
     * @return boolean true if uri ok to be used
     */
    private function check_uri($uri) {
        // Look for { or } to indicate uri value substitution is not complete.
        if (strpos($uri, '{')) {
            return false;
        }
        if (strpos($uri, '}')) {
            return false;
        }
        return true;
    }

    /**
     * Takes data extracted from the web service response and transforms it using mappings created when the pathitem was configured
     * The transformed data is then in a format that can be excepted by the sub plugin.
     *
     * @param array $externaldata
     * @throws Exception from get_lookups_for_pathitem
     * @return array $internaldata ready to be consumed by the sub plugin
     */
    private function transform_response($externaldata) {

        $internaldata = array();
        $externaldata = $this->array_flatten($externaldata);

        $responsemapper = new local_data_importer_pathitem_response();
        $lookups = $responsemapper->get_lookups_for_pathitem($this->pathitem->id);

        foreach ($externaldata as $externalitem) {
            foreach ($lookups as $table => $field) {
                foreach ($field as $fieldname => $externalparam) {
                    $internalitem[$table][$fieldname] = $externalitem[$externalparam];
                }
            }
            $internaldata[] = $internalitem;
        }
        // Make final array elements unique.
        $internaldata = array_map("unserialize", array_unique(array_map("serialize", $internaldata)));

        return $internaldata;
    }

    /**
     * Recursive function that takes an array of any structure and depth and returns a two dimensional array
     * containing the same data as the original array in parameter $structure.
     *
     * @param array $structure
     * @param string $level
     * @return array minimised version of $structure
     */
    private function array_flatten($structure, $level = "") {

        $records = array();
        $properties = array();

        // Get any new properties from the object.
        foreach ($structure as $k => $substructure) {
            if (!is_array($substructure) && !is_object($substructure)) {
                // $properties[$level."/".$k] = $substructure; // Option to show all the keys separated by slashes.
                $properties[$k] = $substructure;
            }
        }

        // Add new properties to existing sub properties.
        foreach ($structure as $k => $substructure) {
            if (is_array($substructure) || is_object($substructure)) {
                $key = ( is_numeric($k) ) ? $level : $level."/".$k; // This is the compromise - keys must be none numeric.
                $subrecords = $this->array_flatten($substructure, $key);
                foreach ($subrecords as $subrecord) {
                    $records[] = array_merge($subrecord, $properties);
                }
            }
        }

        // No new sub structure so apply properties to new record.
        if (count($records) == 0) {
            $records[] = $properties;
        }
        return $records;
    }

    // TODO - Currently not used but this function could replace array_flatten() with some further work.
    private function recursive_find(array $haystack, $needle) {
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
    }
}
