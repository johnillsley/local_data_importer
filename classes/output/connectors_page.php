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
namespace local_data_importer\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Class connectors_page
 * @package local_data_importer\output
 */
class connectors_page implements templatable, renderable {
    /**
     * @var
     */
    public $connectoritems;
    /**
     * @var \local_data_importer_connectorinstance
     */
    public $connectorInstance;

    /**
     * connectors_page constructor.
     */
    public function __construct() {
        $this->connectorInstance = new \local_data_importer_connectorinstance();
    }

    /**
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $connectors = $this->connectorInstance->getAll();
        if (is_array($connectors)) {
            foreach ($connectors as $connector) {
                $data = new stdClass();
                $data->id = $connector->getid();
                $data->name = $connector->get_name();
                $data->description = $connector->getdescription();
                $data->openapikey = $connector->get_openapi_key();
                $data->server = $connector->getserver();
                $data->serverapikey = $connector->get_server_apikey();
                $data->lastmodified = date('d-m-Y H:i', $connector->get_timemodified());
                $data->openapidefinitionurl = $connector->get_openapidefinitionurl();
                $this->connectoritems[] = $data;
            }
        }

        return array('connectoritems' => $this->connectoritems);
    }

    /**
     * Return a single connector instance for display
     * @param $id
     * @return array
     */
    public function get_single_connector_instance($id) {
        $connector = $this->connectorInstance->get_by_id($id);
        $data = array();
        if ($connector instanceof \local_data_importer_connectorinstance) {
            $data['id'] = $connector->getid();
            $data['name'] = $connector->get_name();
            $data['description'] = $connector->getdescription();
            $data['openapikey'] = $connector->get_openapi_key();
            $data['serverapikey'] = $connector->get_server_apikey();
            $data['server'] = $connector->getserver();
            $data['lastmodified'] = date('d-m-Y H:i', $connector->get_timemodified());
            $data['openapidefinitionurl'] = $connector->get_openapidefinitionurl();
        }
        return $data;
    }

}