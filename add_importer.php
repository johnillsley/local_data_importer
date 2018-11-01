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
$renderer = $PAGE->get_renderer('local_data_importer');
$connectorinstance = new local_data_importer_connectorinstance();
$params = [
    'connectorid' => $connectorid,
    'subplugin' => $subplugin,
    'pathitem' => $pathitem,
    'pathitemname' => $pathitemname];
$selectconnectorform = new local_data_importer_add_importer_form(null, $params);
if ($selectconnectorform->is_cancelled()) {
    redirect($returnurl);
}
$importerformdata[] = $params;
if (!$selectconnectorform->is_cancelled() && $selectconnectorform->is_submitted() && confirm_sesskey()) {
    if (!$fromdata = $selectconnectorform->get_data()) {
        var_dump($fromdata);
    }
    switch ($action) {
        case 'fetch_path_items':
            $PAGE->set_heading("Select Path Items");
            echo $OUTPUT->header();
            if (isset($connectorid)) {
                if ($connectorid !== 0) {
                    try {
                        $connector = $connectorinstance->get_by_id($connectorid);
                        if ($connector instanceof \local_data_importer_connectorinstance) {
                            $connectordata = new stdClass();
                            $connectordata->openapidefinitionurl = $connector->get_openapidefinitionurl();
                            $connectordata->openapikey = $connector->get_openapi_key();
                            $connectordata->id = $connector->getid();
                            $connectordata->name = $connector->get_name();
                            /*$client_connection = new local_data_importer_http_connection();
                            $content = $client_connection->get_response();
                            $spec = new local_data_importer_openapi_inspector($content);
                            $pathitems = $spec->get_pathitems($methodfilter = array("get"));*/


                            // Get sub-plugins .
                            $plugins = core_plugin_manager::instance()->get_subplugins_of_plugin('local_data_importer');
                            foreach ($plugins as $component => $info) {
                                $pluginlist[$component] = $component;
                            }
                            // Display form.
                            $params2 = [
                                'selectedconnector' => $connectordata->name,
                                'connectorid' => $connectordata->id,
                                'subplugin' => $pluginlist,
                                // TODO : These are fetched dynamically.
                                'pathitems' => array('/MABS/MOD_CODE/{modcode}' => '/MABS/MOD_CODE/{modcode}')
                            ];
                            $params['subplugin'] = $pluginlist;
                            $params['selectedconnector'] = $connectordata;
                            $params['pathitem'] = array('/MABS/MOD_CODE/{modcode}' => '/MABS/MOD_CODE/{modcode}');
                            $selectconnectorform = new local_data_importer_add_importer_form(null, $params);
                            echo $selectconnectorform->display();
                            //$renderer = $PAGE->get_renderer('local_data_importer');
                            //echo $renderer->select_path_item_subplugin($items, $connectordata, $pluginlist);
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
            echo $OUTPUT->header();

            if (isset($connectorid) && isset($pathitem) && isset($subplugin) && isset($pathitemname)) {
                $connector = $connectorinstance->get_by_id($connectorid);
                try {
                    if ($connector instanceof \local_data_importer_connectorinstance) {
                        $connectordata = new stdClass();
                        $connectordata->name = $connector->get_name();
                        $connectordata->id = $connector->getid();

                        // For the selected subplugin , get the params available.
                        $class = $subplugin . "_subplugin";
                        $object = new $class();
                        $subpluginparams = $object->responses;
                        // TODO : These are fetched dynamically.
                        $pathitemparams = array('STU_CODE' => 'STU_CODE',
                            'SCJ_CODE' => 'SCJ_CODE');
                        $params['subpluginparams'] = $subpluginparams;
                        $params['pathitemparams'] = $pathitemparams;
                        $params['selectedconnector'] = $connectordata;
                        $params['pathitem'] = $pathitem;
                        $params['pathitemname'] = $pathitemname;
                        $selectconnectorform = new local_data_importer_add_importer_form(null, $params);
                        echo $selectconnectorform->display();
                        // For the selected path item , get the params available.
                        //echo $renderer->select_response_params($connectordata, $subplugin, $subpluginparams, $pathitem);
                    }
                } catch (\Exception $e) {
                    var_dump($e);
                }
            }
            break;
        case 'save':
            $PAGE->set_heading("Add Response Params");
            echo $OUTPUT->header();
            var_dump($connectorid);
            var_dump($pathitem);
            var_dump($subplugin);
            var_dump($pathitemname);
            if (isset($connectorid) && isset($pathitem) && isset($subplugin) && isset($pathitemname)) {
                echo "yes ";
                $class = $subplugin . "_subplugin";
                $object = new $class();
                $subpluginparams = $object->responses;
                foreach ($subpluginparams as $paramkey => $arrayparam) {
                    $paramname = $arrayparam['name'];
                    // Look for post values with these names.
                    $paramoptions[$paramname] = optional_param($paramname, null, PARAM_RAW);
                }
                var_dump($paramoptions);

                // Add them to the database.

                $objpathitem = new local_data_importer_connectorpathitem();
                $objpathitem->set_name($pathitemname);
                $objpathitem->set_connector_id($connectorid); // No need to create a new connector instance (?).
                $objpathitem->set_path_item($pathitem);
                $objpathitem->set_active(true);
                $objpathitem->set_http_method('GET');
                $objpathitem->set_plugin_component($subplugin);
                try {
                    $pathitemid = $objpathitem->save(true);

                    $responseparams = new local_data_importer_connectorresponseparams();
                    $responseparams->set_pathitemid($pathitemid);
                    if (is_array($paramoptions)) {
                        foreach ($paramoptions as $subpluginparamname => $responseparamname) {
                            $responseparams->set_pathparam($responseparamname);
                            $responseparams->set_componentparam($subpluginparamname);
                            $responseparams->save(true);

                        }
                        // All ok, go back to index.
                        redirect($returnurl);
                    }
                } catch (\Exception $e) {
                    var_dump($e->getMessage());
                }

            }
            break;
    }
} else {
    $PAGE->set_heading("Add Importer");
    echo $OUTPUT->header();
    echo $selectconnectorform->display();
}
echo $OUTPUT->footer();