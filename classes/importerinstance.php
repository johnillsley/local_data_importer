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
 * A class that returns a valid importer instance to be used by the web service.
 *
 * @package    local_data_importer
 * @author     Hittesh Ahuja <h.ahuja@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class local_data_importer_importerinstance
 */
class local_data_importer_importerinstance {
    /**
     * @var
     */
    public $pathiteminstance;
    /**
     * @\local_data_importer_connectorinstance
     */
    public $connectorinstance;
    /**
     * @var
     */
    public $pathitemparameterinstance;
    /**
     * @var
     */
    public $pathitemresponseinstance;
    /**
     * @var
     */
    public $pathitemid;

    /**
     * local_data_importer_importerinstance constructor.
     * @param $pathitemid
     * @throws Exception
     */
    public function __construct($pathitemid) {
        $this->pathitemid = $pathitemid;
        try {
            $this->get_pathitem();
            $this->get_pathitem_parameter();
            $this->get_pathitem_response();
            if ($this->pathiteminstance instanceof local_data_importer_connectorpathitem) {
                $connectorid = $this->pathiteminstance->get_connector_id();
                $this->get_connector($connectorid);
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }


    /** Get a connector instance
     * @param $connectorid
     * @return local_data_importer_connectorinstance
     * @throws Exception
     */
    private function get_connector($connectorid) {
        $connector = new local_data_importer_connectorinstance();
        try {
            $this->connectorinstance = $connector->get_by_id($connectorid);
            return $this->connectorinstance;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }


    }

    /**
     * @return local_data_importer_connectorpathitem
     */
    private function get_pathitem() {
        $pathitem = new local_data_importer_connectorpathitem();
        $this->pathiteminstance = $pathitem->get_by_id($this->pathitemid);
        return $this->pathiteminstance;
    }

    /**
     * @return array
     */
    private function get_pathitem_parameter() {
        $pathitemparameter = new local_data_importer_pathitem_parameter();
        $this->pathitemparameterinstance = $pathitemparameter->get_by_pathitem_id($this->pathitemid);
        return $this->pathitemparameterinstance;

    }

    /**
     * @return array
     */
    private function get_pathitem_response() {
        $pathitemresponse = new local_data_importer_pathitem_response();
        $this->pathitemresponseinstance = $pathitemresponse->get_by_pathitem_id($this->pathitemid);
        return $this->pathitemresponseinstance;
    }


}