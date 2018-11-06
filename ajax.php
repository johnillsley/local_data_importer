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
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
require_login();
$action = optional_param('action', 'logentries', PARAM_RAW);
$openapikey = optional_param('openapikey', '', PARAM_INT);
$openapidefinitionurl = optional_param('openapidefinitionurl', '', PARAM_INT);
$componentname = optional_param('componentname', '', PARAM_RAW);
$connectorid = optional_param('connectorid', '', PARAM_INT);
global $PAGE;
$PAGE->set_context(\context_system::instance());
$renderer = $PAGE->get_renderer('local_data_importer');
$connector = new \stdClass();
if ($openapikey) {
    $connector->openapikey = $openapikey;
}
if ($openapidefinitionurl) {
    $connector->openapidefinitionurl = $openapidefinitionurl;
}
if ($componentname) {
    $class = $componentname . "_subplugin";
    $object = new $class();
    $object->params();
    echo json_encode($object->params);
    exit;
}
if ($action == 'fetchpathitems') {
    if (isset($connectorid)) {
        $connectorinstance = new \local_data_importer_connectorinstance();
        try {
            $connector = $connectorinstance->get_by_id($connectorid);
            if ($connector instanceof \local_data_importer_connectorinstance) {
                /*$data['openapidefinitionurl'] = $connector->get_openapidefinitionurl();
                $data['openapikey'] = $connector->get_openapi_key();
                $client_connection = new local_data_importer_http_connection();
                $content = $client_connection->get_response();
                $spec = new local_data_importer_openapi_inspector($content);
                $pathitems = $spec->get_pathitems($methodfilter = array("get"));*/
                $items = array();
                $pathitem1 = new \stdClass();
                $pathitem1->name = "PathItem1";
                $pathitem2 = new \stdClass();
                $pathitem2->name = "PathItem2";
                $items = [$pathitem1, $pathitem2];
                echo json_encode(['pathitems' => $items]);
                die;
            }
        } catch (\Exception $e) {

        }
        die;

    }
}
echo json_encode(['server1', 'server2']);
