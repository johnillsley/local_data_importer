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
require_once("../../config.php");
global $OUTPUT;
require_once($CFG->dirroot . "/local/data_importer/forms/importer/add_importer.php");
$url = new moodle_url('/local/data_importer/add_importer.php');
$returnurl = new moodle_url('/local/data_importer/index.php');
$PAGE->set_url($url);
require_login();
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add('Data Importer');
$PAGE->set_title("Data Importer");
$PAGE->set_heading("Data Importer");
$action = optional_param('action', 'list_connectors', PARAM_RAW);
$currentaction = optional_param('action', 'list_connectors', PARAM_RAW);
$previousaction = optional_param('action', 'list_connectors', PARAM_RAW);
$connectorid = optional_param('connectorid', null, PARAM_INT);
$subplugin = optional_param('subplugin', null, PARAM_RAW);
$pathitem = optional_param('pathitem', null, PARAM_RAW);
$pathitemname = optional_param('pathitemname', null, PARAM_RAW);
$pathitemparams = optional_param_array('pathitemparams', null, PARAM_RAW);
$pathitemresponse = optional_param_array('pathitemresponseparams', null, PARAM_RAW);


$renderer = $PAGE->get_renderer('local_data_importer');
$connectorinstance = new local_data_importer_connectorinstance();
$openapiinspector = null;
echo $OUTPUT->header();
if (isset($connectorid)) {
    $connector = $connectorinstance->get_by_id($connectorid);
    $connectordata = new stdClass();
    $connectordata->openapidefinitionurl = $connector->get_openapidefinitionurl();
    $connectordata->openapikey = $connector->get_openapi_key();
    $connectordata->id = $connector->getid();
    $connectordata->name = $connector->get_name();
    try {
        $httpconnection = new local_data_importer_http_connection($connectordata->openapidefinitionurl
            , $connectordata->openapikey);
        $httpresponse = $httpconnection->get_response();
        $openapiinspector = new local_data_importer_openapi_inspector($httpresponse);
    } catch (\Exception $e) {
        // TODO Logging.
        echo $OUTPUT->notification("Tried to fetch details from the connector and got the following error: " . $e->getMessage());
    }
}
$params = [
    'connectorid' => $connectorid,
    'subplugin' => $subplugin,
    'pathitem' => $pathitem,
    'pathitemname' => $pathitemname];
$selectconnectorform = new \local_data_importer_add_importer_form(null, $params);
if ($selectconnectorform->is_cancelled()) {
    redirect($returnurl);
}
$importerformdata[] = $params;
if (!$selectconnectorform->is_cancelled() && $selectconnectorform->is_submitted() && confirm_sesskey()) {
    if (!is_null($openapiinspector)) {
        switch ($action) {
            case 'fetch_path_items':
                $PAGE->set_heading("Select Path Items");
                if (isset($connectorid)) {
                    if ($connectorid !== 0) {
                        try {
                            if ($connector instanceof \local_data_importer_connectorinstance) {
                                if (is_array($pathitems = $openapiinspector->get_pathitems())) {
                                    foreach ($pathitems as $pathitemlabel => $arraypathitem) {
                                        $params['pathitem'][$pathitemlabel] = $pathitemlabel;
                                    }
                                }

                                $pluginlist = array();
                                // Get sub-plugins .
                                $plugins = core_plugin_manager::instance()->get_subplugins_of_plugin('local_data_importer');
                                 foreach ($plugins as $component => $info) {
                                    $pluginlist[$component] = $component;
                                }
                                 $params['subplugin'] = $pluginlist;
                                $params['selectedconnector'] = $connectordata;
                                $selectconnectorform = new local_data_importer_add_importer_form(null, $params);
                                echo $selectconnectorform->display();
                            }
                        } catch (\Exception $e) {
                            var_dump($e);
                        }
                    } else {
                        echo $OUTPUT->notification("Please select a connector");
                        echo $renderer->importer_form_builder();
                    }
                }
                break;
            case 'fetch_response_params':
                $PAGE->set_heading("Add Response Params");
                if (isset($connectorid) && isset($pathitem) && isset($subplugin) && isset($pathitemname)) {
                    try {
                        if ($connector instanceof \local_data_importer_connectorinstance) {
                            // For the selected subplugin , get the params available.
                            $class = $subplugin."_importer";
                             $object = new $class($pathitem);
                            $subpluginresponses = $object->responses;
                            $subpluginparams = $object->parameters;
                            $pathitemparams = $openapiinspector->get_pathitem_parameters($pathitem);
                            $responseparams = $openapiinspector->get_pathitem_responses_selectable($pathitem);

                            // Prepare parameters to pass to the form.
                            $params['subpluginparams'] = $subpluginparams;
                            $params['subpluginresponses'] = $subpluginresponses;
                            $params['pathitemparams'] = $pathitemparams;
                            $params['selectedconnector'] = $connectordata;
                            $params['pathitem'] = $pathitem;
                            $params['pathitemname'] = $pathitemname;
                            $params['pathitemresponseparams'] = $responseparams;
                            $selectconnectorform = new local_data_importer_add_importer_form(null, $params);
                            echo $selectconnectorform->display();
                        }
                    } catch (\Exception $e) {
                        var_dump($e);
                    }
                }
                break;
            case 'save':
                // Add them to the database.
                // 1. PATH ITEM.
                $objpathitem = new local_data_importer_connectorpathitem();
                $objpathitem->set_name($pathitemname);
                $objpathitem->set_connector_id($connectorid); // No need to create a new connector instance (?).
                $objpathitem->set_path_item($pathitem);
                $objpathitem->set_active(true);
                $objpathitem->set_http_method('GET');
                $objpathitem->set_plugin_component($subplugin . "_subplugin");

                try {
                    $pathitemid = $objpathitem->save(true);

                    // 2. PATH ITEM PARAMETER.

                    $objpathitemparameter = new local_data_importer_pathitem_parameter();
                    $objpathitemparameter->set_pathitemid($pathitemid);

                    if (isset($pathitemparams) && is_array($pathitemparams)) {
                        foreach ($pathitemparams as $pip => $pcp) {
                            $pip = explode("-", $pip);
                            $objpathitemparameter->set_pluginparam_table($pip[0]);
                            $objpathitemparameter->set_pluginparam_field($pip[1]);
                            $objpathitemparameter->set_pathitem_parameter($pcp);
                            $objpathitemparameter->save();
                        }
                    }


                    // 3. PATH ITEM RESPONSE.
                    $objpathitemresponse = new local_data_importer_pathitem_response();
                    $objpathitemresponse->set_pathitemid($pathitemid);

                    if (isset($pathitemresponse) && is_array($pathitemresponse)) {
                        foreach ($pathitemresponse as $pir => $pcr) {
                            $pir = explode("-", $pir);
                            $objpathitemresponse->set_pathitem_response($pcr);
                            $objpathitemresponse->set_pluginresponse_table($pir[0]);
                            $objpathitemresponse->set_pluginresponse_field($pir[1]);
                            $objpathitemresponse->save();
                        }
                        // All Saved OK.
                        redirect($returnurl);
                    }


                } catch (\Exception $e) {
                    var_dump($e->getMessage());
                }

                break;
        }
    }

} else {
    $PAGE->set_heading("Add Importer");
     echo $selectconnectorform->display();
}
echo $OUTPUT->footer();