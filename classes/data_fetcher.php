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
require_once("../../../config.php");
//defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/local/data_importer/classes/importerinstance.php");

class local_data_importer_data_fetcher {
    private $httpclient;
    public $importer;
    public $pathitemid;
    public $subplugin;

    public function __construct($pathitemid) {
        $this->pathitemid = $pathitemid;
    }

    public function get_web_service_data() {

        // Get the importer.
        try {
            $this->importer = new local_data_importer_importerinstance($this->pathitemid);
            if ($this->importer instanceof local_data_importer_importerinstance) {
                // TODO - What is supposed to happen on else? What is this checking for? Better for constructor to throw an exception
                // https://softwareengineering.stackexchange.com/questions/137581/should-i-throw-exception-from-constructor
                
                // Get the base uri and api-key to connect to the web service.
                $baseuri = $this->importer->connectorinstance->getserver();
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


}


// Run sample.
$fetcher = new local_data_importer_data_fetcher(32);
$fetcher->get_web_service_data();
