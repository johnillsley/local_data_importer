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
$openapikey = optional_param('openapikey', '', PARAM_RAW);
$openapidefinitionurl = optional_param('openapidefinitionurl', '', PARAM_RAW);
$componentname = optional_param('componentname', '', PARAM_RAW);
$connectorid = optional_param('connectorid', '', PARAM_INT);

try {
    $httpconnection = new local_data_importer_http_connection($openapidefinitionurl, $openapikey);
    $httpresponse = $httpconnection->get_response();
    $openapiinspector = new local_data_importer_openapi_inspector($httpresponse);
    echo json_encode($openapiinspector->servers);
} catch (\Exception $e) {
    echo json_encode(["Error fetching servers from Swaggerhub"]);
}
