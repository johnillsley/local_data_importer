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
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Class connectors_page
 * @package local_data_importer\output
 */
class importers_page implements templatable, renderable {
    /**
     * @var
     */
    public $connectoritems;
    /**
     * @var
     */
    public $pathitems;
    /**
     * @var \local_data_importer_connectorinstance
     */
    public $connectorinstance;
    /**
     * @var \local_data_importer_connectorpathitem
     */
    public $pathiteminstance;

    /**
     * connectors_page constructor.
     */
    public function __construct() {
        $this->connectorinstance = new \local_data_importer_connectorinstance();
        $this->pathiteminstance = new \local_data_importer_connectorpathitem();
    }

    /**
     * @return array
     */
    public function connector_items() {
        $connectors = $this->connectorinstance->get_all();
        if (is_array($connectors)) {
            foreach ($connectors as $connector) {
                $data = new stdClass();
                $data->id = $connector->get_id();
                $data->name = $connector->get_name();
                $data->description = $connector->get_description();
                $data->openapikey = $connector->get_openapi_key();
                $data->server = $connector->get_server();
                $data->serverapikey = $connector->get_server_apikey();
                $data->lastmodified = date('d-m-Y H:i', $connector->get_timemodified());
                $data->openapidefinitionurl = $connector->get_openapidefinitionurl();
                $connectoritems[] = $data;
            }
        }
        return array('connectoritems' => $connectoritems);
    }

    /**
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $connectors = $this->connectorinstance->get_all();
        if (is_array($connectors)) {
            foreach ($connectors as $connector) {
                $data = new stdClass();
                $data->id = $connector->get_id();
                $data->name = $connector->get_name();
                $data->description = $connector->get_description();
                $data->openapikey = $connector->get_openapi_key();
                $data->server = $connector->get_server();
                $data->serverapikey = $connector->get_server_apikey();
                $data->lastmodified = date('d-m-Y H:i', $connector->get_timemodified());
                $data->openapidefinitionurl = $connector->get_openapidefinitionurl();
                $this->connectoritems[] = $data;
            }
        }

        // Get all Path items.
        $pathitems = $this->pathiteminstance->get_all();
        if (is_array($pathitems)) {
            foreach ($pathitems as $pathitem) {
                $data = new stdClass();
                $data->pathitemid = $pathitem->get_id();
                $data->name = $pathitem->get_name();
                $data->http_method = $pathitem->get_http_method();
                $data->plugin_component = $pathitem->get_plugin_component();
                $data->active = $pathitem->get_active();
                $data->pathitem = $pathitem->get_path_item();
                $data->time_created = date('d-m-Y H:i', $pathitem->get_time_created());
                try {
                    $connector = $this->connectorinstance->get_by_id($pathitem->get_connector_id());
                    if ($connector instanceof \local_data_importer_connectorinstance) {
                        $data->connector = $connector->get_name();
                    } else {
                        $data->connector = "Connector Instance does not exist!!";
                    }
                } catch (\Exception $e) {
                    $data->connector = "Connector Instance does not exist!!";
                }

                $this->pathitems[] = $data;
            }
        }
        return array('connectoritems' => $this->connectoritems, 'pathitems' => $this->pathitems);
    }

    /**
     * Return a single connector instance for display
     * @param $id
     * @return array
     */
    public function get_single_connector_instance($id) {
        $connector = $this->connectorinstance->get_by_id($id);
        $data = array();
        if ($connector instanceof \local_data_importer_connectorinstance) {
            $data = new stdClass();
            $data->id = $connector->get_id();
            $data->name = $connector->get_name();
            $data->description = $connector->get_description();
            $data->server = $connector->get_server();
            $data->openapikey = $connector->get_openapi_key();
            $data->openapidefinitionurl = $connector->get_openapidefinitionurl();
            $data->serverapikey = $connector->get_server_apikey();

        }
        return $data;
    }

    /**
     * @return array
     */
    public function get_all_connector_names() {
        $connectors = $this->connectorinstance->get_all();
        $connectoritems = array();
        if (is_array($connectors)) {
            foreach ($connectors as $connector) {
                $data = new stdClass();
                $data->id = $connector->get_id();
                $data->name = $connector->get_name();
                $data->description = $connector->get_description();
                $data->server = $connector->get_server();
                $data->openapidefinitionurl = $connector->get_openapidefinitionurl();
                $connectoritems[] = $data;
            }
        }
        return array('connectoritems' => $connectoritems);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function get_single_path_item_instance($id) {
        if ($id) {
            $pathiteminstance = $this->pathiteminstance->get_by_id($id);
            $data = array();
            if ($pathiteminstance instanceof \local_data_importer_connectorpathitem) {
                $data['id'] = $pathiteminstance->get_id();
                $data['name'] = $pathiteminstance->get_name();
                $data['connnectorid'] = $pathiteminstance->get_connector_id();
                $data['http_method'] = $pathiteminstance->get_http_method();
                $data['plugin_component'] = $pathiteminstance->get_plugin_component();
                $data['active'] = ($pathiteminstance->get_active() == 1 ? 'Yes' : 'No');
                $data['pathitem'] = $pathiteminstance->get_path_item();
            }
            return $data;
        }
        return false;

    }

}