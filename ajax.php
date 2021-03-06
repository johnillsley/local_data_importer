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
 * Ajax File to handle fetching of server details through Web Services
 *
 * @package    local_data_importer
 * @author     Hittesh Ahuja <ha386@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_login();

$openapikey = optional_param('openapikey', '', PARAM_RAW);
$openapidefinitionurl = optional_param('openapidefinitionurl', '', PARAM_RAW);

try {
    $httpconnection = new local_data_importer_http_connection($openapidefinitionurl, $openapikey);
    $httpresponse = $httpconnection->get_response();

    $openapiinspector = new local_data_importer_openapi_inspector($httpresponse);
    echo json_encode($openapiinspector->servers);
} catch (\Exception $e) {
    echo json_encode(["Error fetching servers from Swaggerhub"]);
}
