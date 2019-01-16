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
 * Renderer for the local data importer
 * This class deals with all of the display pages and mostly delete confirmation pages
 *
 * @package    local_data_importer
 * @uses       plugin_renderer_base
 * @author     Hittesh Ahuja <ha386@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class local_data_importer_renderer
 */
class local_data_importer_renderer extends plugin_renderer_base {
    /**
     * This method is responsible for display the index page
     * @return bool|string
     * @throws \moodle_exception
     */
    public function index_page($connector, $pathitem) {
        $renderable = new local_data_importer\output\importers_page($connector, $pathitem);
        $connectorspathitems = $renderable->export_for_template($this->output);
        return parent::render_from_template('local_data_importer/connectors', $connectorspathitems);
    }


    /**
     * @param $id
     * @return mixed
     */
    public function delete_connector_page($id) {
        global $OUTPUT;
        $continuebutton = new single_button(new moodle_url('index.php', ['confirmdelete' => 1,
            'connectorid' => $id]), 'Yes', 'get', true);
        $cancelbutton = new single_button(new moodle_url('index.php', ['confirmdelete' => 0]), 'No', 'get', false);
        return $OUTPUT->confirm('Are you sure you want to delete this connector ?', $continuebutton, $cancelbutton);
    }
    /**
     * @param $id
     * @return mixed
     */
    public function delete_pathitem_page($id) {
        global $OUTPUT;
        $continuebutton = new single_button(new moodle_url('edit_importer.php', ['confirmdelete' => 1,
            'pathitemid' => $id]), 'Yes', 'get', true);
        $cancelbutton = new single_button(new moodle_url('edit_importer.php', ['confirmdelete' => 0]), 'No', 'get', false);
        return $OUTPUT->confirm('Are you sure you want to delete this pathitem ? ', $continuebutton, $cancelbutton);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function edit_importer_page($id) {
        $renderable = new local_data_importer\output\importers_page();
        $pathitemdata = $renderable->get_single_path_item_instance($id);
        $importereditform = new local_data_importer_edit_importer_form(null, $pathitemdata);
        return $importereditform->display();
    }

    /**
     * @return mixed
     */
    public function importer_form_builder() {
        $renderable = new local_data_importer\output\importers_page();
        $connectorspathitems = $renderable->export_for_template($this->output);
        return $this->render_from_template('local_data_importer/importer_form', $connectorspathitems);
    }

    /**
     * @param $items
     * @param $connectordata
     * @param $pluginlist
     * @return mixed
     */
    public function select_path_item_subplugin($items, $connectordata, $pluginlist) {
        return $this->render_from_template('local_data_importer/select_path_item',
            ['pathitems' => $items, 'connectordata' => $connectordata, 'subplugins' => $pluginlist]);

    }

    /**
     * @param $selectedconnector
     * @param $selectedplugin
     * @param $subpluginparams
     * @param $selectedpathitem
     * @return mixed
     */
    public function select_response_params($selectedconnector, $selectedplugin, $subpluginparams, $selectedpathitem) {
        return $this->render_from_template('local_data_importer/select_response_params',
            [
                'connectordata' => $selectedconnector,
                'subpluginparams' => $subpluginparams,
                'selectedsubplugin' => $selectedplugin,
                'selectedpathitem' => $selectedpathitem
            ]);

    }


}