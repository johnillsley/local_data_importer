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
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/local/data_importer/importers/entity_importer.php");

class local_data_importer_data_fetcher {

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
     * Method to process a single pathitem web service call and update Moodle accordingly.
     * Catches any exceptions thrown by other classes and writes to the central exception log
     *
     * @return void
     */
    public function update_from_pathitem() {

        $parameterslist = $this->map_to_web_service_parameters();

        foreach ($parameterslist as $parameters) {
            try {
                $relativeuri = $this->build_relativeuri($this->uritemplate, $parameters);
                $externaldata = $this->httpclient->get_response($relativeuri);
                $internaldata = $this->transform_data($externaldata);
                $this->importer->do_imports($internaldata);
            } catch (Exception $e) {
                // TODO - Log failure for web service level.
                // pathitemid, time, url, error message.
            }
        }
    }

    /**
     * Takes the parameters from the subplugin and transforms them using parameter mappings so that they can be used in
     * web service requests.
     *
     * @return array of parameter record to be used to populate web service URL requests.
     */
    public function map_to_web_service_parameters() {

        $internalparameters = $this->importer->get_parameters();

        if ($internalparameters == null) {
            return array(null);
        }
        $parammapper = new local_data_importer_pathitem_parameter();
        $mappings = $parammapper->get_by_pathitem_id($this->pathitem->id);
        foreach ($mappings as $mapping) {
            $transform[$mapping->get_subplugin_parameter()] = $mapping->get_pathitem_parameter();
        }

        foreach ($internalparameters as $internalitem) {
            $externalitem = array();
            foreach ($internalitem as $key => $value) {
                // Check if the internal parameter has been mapped.
                if (array_key_exists($key, $transform)) {
                    $externalitem[$transform[$key]] = $value;
                }
            }
            $externalparameters[] = $externalitem;
        }
        // Make final array values unique.
        $externalparameters = array_map("unserialize", array_unique(array_map("serialize", $externalparameters)));

        return $externalparameters;
    }

    /**
     * Produces a relative uri from the parameters that originated in the sub plugin.
     *
     * @throws Exception if the relative uri has not had all values substituted
     * @return string relative url ready for the web service request
     */
    private function build_relativeuri($relativeuri, $parameters) {

        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $parammappings = $pathitemparameter->get_by_pathitem_id($this->pathitem->id);

        if (is_array($parameters)) {
            foreach ($parameters as $name => $value) {
                if ($value == "") {
                    throw new Exception('URL parameter (' . $name . ') is empty string.');
                }
                $relativeuri = str_replace( '{' . $name . '}', urlencode($value), $relativeuri);
            }
        }
        if (!$this->check_uri($relativeuri)) {
            throw new Exception('The relative URI has missing values.');
        }
        return $relativeuri;
    }

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
     * Takes data extracted from the web service request and transforms it using mappings created when the pathitem was configured
     * The transformed data is then in a format that can be excepted by the sub plugin.
     *
     * @param array $externaldata
     * @return array $internaldata ready to be consumed by the sub plugin
     */
    private function transform_data($externaldata) {

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
    /*
    public function get_web_service_data() {

        // Get the importer.
        try {
            $this->importer = new local_data_importer_importerinstance($this->pathitemid);
            if ($this->importer instanceof local_data_importer_importerinstance) {
                // TODO - What is supposed to happen on else? What is this checking for?
                // TODO - Better for constructor to throw an exception
                // https://softwareengineering.stackexchange.com/questions/137581/should-i-throw-exception-from-constructor

                // Get the base uri and api-key to connect to the web service.
                $baseuri = $this->importer->connectorinstance->get_server();
                $serverapikey = $this->importer->connectorinstance->get_server_apikey();
                $this->httpclient = new local_data_importer_http_connection("https://" . $baseuri, $serverapikey);

                // Use the importer to call the relevant web service functions.
                $beforepathitemstring = $this->importer->pathiteminstance->get_path_item();
                $subpluginclass = $this->importer->pathiteminstance->get_plugin_component();
                $this->initialise_subplugin($subpluginclass);
                $subpluginparameterdata = $this->subplugin->get_parameters_for_ws();

                // Get path item parameters for a given path item.
                foreach ($this->importer->pathitemparameterinstance as $pathitemparameter) {
                    foreach ($subpluginparameterdata as $key => $objparam) {
                        $field = $pathitemparameter->get_pluginparam_field();
                        $pathitemvalue = $objparam->$field;
                        // Replace pathitem_parameter with an actual value.
                        $afterpathitemstring = str_replace(
                            "{" . $pathitemparameter->get_pathitem_parameter() . "}",
                            $pathitemvalue, $beforepathitemstring);
                        // Do one by one on the pathitem call , give it back to subplugin and then do the next.
                        try {
                            $response[] = $this->httpclient->get_response($afterpathitemstring);
                            // TODO - why is this called multiple times before the URL is completely formed? This is wrong.
                            // If got a positive response, pass the data back to the subplugin
                            // for consumption.
                            $wsresponse = $this->extract_response($response);
                            $this->subplugin->consume_data($wsresponse);
                        }
                        catch (\Exception $e) {
                            // TODO Log it.
                        }
                    }
                }
            }
        }
        catch (\Exception $e) {
        }


        // Now I have everything I want,
        // I call the sub-plugin's method to consume that data any which
        // way it wants.


        //$this->subplugin->consume_data($wsresponse);

    }

    protected function extract_response($response) {
        foreach ($this->importer->pathitemresponseinstance as $pathitemresponse) {
            //var_dump($pathitemresponse->get_pluginresponse_table());
            //var_dump($pathitemresponse->get_pluginresponse_field());
            //var_dump($pathitemresponse->get_pathitem_response());

            $wsresponse[] = $this->recursive_find($response, $pathitemresponse->get_pathitem_response());
            return $wsresponse;
        }
    }

    protected function initialise_subplugin($subpluginclass) {

        if (!empty($subpluginclass)) {
            $this->subplugin = new $subpluginclass();
        }
    }

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

    protected function transform_external_to_internal($external) {

        $internal = array();
        // Get response mappings.
        foreach ($this->responses as $table) {
            foreach ($table as $field) {

            }
        }
        return $internal;
    }
    */
}
