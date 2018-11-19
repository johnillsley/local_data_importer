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

function cron_task() {

    global $DB;
    $pathitem = new local_data_importer_connectorpathitem();
    $connector = new local_data_importer_connectorinstance();
    $pathitemparam = new local_data_importer_pathitem_parameter();

    // Do we have a 1-1 relation with importer and sub-plugin ? no ,
    // you could have multiple importers pointing to the same sub plugin.
    // An example would be creating enrolments ( subplugin) which has two different path items ( one for teachers and
    // one for students).

    // Get the importer details and run it.
    $pathitems = $pathitem->get_by_subplugin('importers_bath_create_course_subplugin');
    if (!empty($pathitems)) {
        foreach ($pathitems as $objpathitem) {
            $connectorid = $objpathitem->get_connector_id();
            $pathitemstring = $objpathitem->get_path_item();
            if (isset($connectorid)) {
                $objconnector = $connector->get_by_id($connectorid);
                $connectordata = new stdClass();
                $connectordata->server = $objconnector->getserver();
                $connectordata->serverapikey = $objconnector->get_server_apikey();
                try {

                    $httpconnection = new local_data_importer_http_connection($connectordata->server, $connectordata->serverapikey);

                    // Prepare the path item string with param replacements.
                    $pathitemid = $objpathitem->get_id();
                    $objpathitemparam = $pathitemparam->get_by_pathitem_id($pathitemid);
                    $pluginparam = $objpathitemparam->get_plugincomponent_param();
                    $pathparameter = $objpathitemparam->get_pathitem_parameter();
                    $samplemodcode = 'BB10000';
                    $search = "{" . $pluginparam . "}";
                    $pathitemstring = str_replace($search, $samplemodcode, $pathitemstring);
                    echo $pathitemstring;
                    $response = $httpconnection->get_response($pathitemstring);
                    var_dump($response);
                } catch (\Exception $e) {
                    // TODO Logging.
                    var_dump($e->getMessage());
                }
            }
        }
    }
}