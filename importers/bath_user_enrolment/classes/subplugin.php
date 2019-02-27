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
 * Class importers_bath_user_enrolment_subplugin
 */
class importers_bath_user_enrolment_subplugin extends local_data_importer_subpluginabstract {
    /**
     * importers_bath_user_enrolment_subplugin constructor.
     */
    public function __construct() {
        $this->responses = array(
            'user' => array('username')
        );

        $this->params = array('sits_mappings' => array('sits_code'));
    }

    /**
     * See if the plugin is available
     * @return bool
     */
    public function is_available(): bool {
        // TODO make this a db setting for the sub-plugin.
        return true;
    }

    /**
     * Get plugin name
     * @return string
     */
    public function get_plugin_name(): string {
        $this->pluginname = get_string('pluginname', 'importers_bath_user_enrolment');
    }

    /**
     * Get plugin description
     * @return string
     */
    public function plugin_description(): string {
        $this->plugindescription = get_string('plugindescription', 'importers_bath_user_enrolment');
    }

    public function consume_data($data) {
        echo "yay I've got something from the fetcher";
        var_dump($data);
    }

    public function sync_enrolment_cron_task() {

        global $DB;
        $pathitem = new local_data_importer_connectorpathitem();
        $connector = new local_data_importer_connectorinstance();
        $pathitemparam = new local_data_importer_pathitem_parameter();
        $responseparam = new local_data_importer_pathitem_response();

        // Do we have a 1-1 relation with importer and sub-plugin ? no ,
        // you could have multiple importers pointing to the same sub plugin.
        // An example would be creating enrolments ( subplugin) which has two different path items ( one for teachers and
        // one for students).

        // Get the importer details and run it.
        $pathitems = $pathitem->get_by_subplugin(get_class());
        if (!empty($pathitems)) {
            foreach ($pathitems as $objpathitem) {
                $connectorid = $objpathitem->get_connector_id();
                $pathitemstring = $objpathitem->get_path_item();
                if (isset($connectorid)) {
                    $objconnector = $connector->get_by_id($connectorid);
                    $connectordata = new stdClass();
                    $connectordata->server = $objconnector->get_server();
                    $connectordata->serverapikey = $objconnector->get_server_apikey();
                    try {

                        $httpconnection = new local_data_importer_http_connection(
                            'https://' . $connectordata->server, // TODO : Come up with a better solution.
                            $connectordata->serverapikey);

                        // Prepare the path item string with param replacements.
                        $pathitemid = $objpathitem->get_id();
                        $objpathitemparam = $pathitemparam->get_by_pathitem_id($pathitemid);
                        $arrayresponseparams = $responseparam->get_by_pathitem_id($pathitemid);

                        $pluginparam = $objpathitemparam->get_plugincomponent_param();
                        $pathparameter = $objpathitemparam->get_pathitem_parameter();

                        $samplemodcode = 'CM30229';
                        $search = "{" . $pluginparam . "}";
                        $pathitemstring = str_replace($search, $samplemodcode, $pathitemstring);
                        $response = $httpconnection->get_response($pathitemstring);
                        var_dump($response);
                        if (is_array($arrayresponseparams)) {
                            foreach ($arrayresponseparams as $objresponseparam) {
                                $responseparameter = $objresponseparam->get_pathitem_response();
                                $plugincomponentresponse = $objresponseparam->get_plugincomponent_response();
                                echo "Res param is:" . $responseparameter;
                                echo $plugincomponentresponse;

                                $wsresponse = $this->recursiveFind($response, $plugincomponentresponse);

                                echo "We need to save " . $wsresponse . " against $responseparameter in the db...";

                                // I need to get the userid
                                // I need to get the enrol instance.

                            }
                        }
                        die;
                        // Now, this is the most important bit <<<<<<<<<<<---.

                        // Looking at the response we got from the web service, cherry pick the value we want to be
                        // used by the sub-plugin.

                        // For that we need to look at the params and responses we have saved in the respective tables.
                        //
                    } catch (\Exception $e) {
                        // TODO Logging.
                        var_dump($e->getMessage());
                    }

                }
            }
        }


    }

    /**
     * Traverse through the params property and return actual data to be passed to the WS call
     */
    public function get_parameters_for_ws() {
        // List of sits mapping mod_codes to pass back.
        global $DB;
        foreach ($this->params as $table => $fields) {
            foreach ($fields as $field) {
                $params = $DB->get_records($table, [], $field);
            }
        }
        return $params;
    }

}