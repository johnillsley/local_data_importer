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
     * @return bool|string
     * @throws moodle_exception
     */
    public function connectors_page() {
        $renderable = new local_data_importer\output\connectors_page();
        $connectors = $renderable->export_for_template($this->output);
        return parent::render_from_template('local_data_importer/connectors', $connectors);
    }

    /**
     * @param $id
     */
    public function edit_connector_page($id) {
        global $PAGE;
        $PAGE->requires->js_call_amd('local_data_importer/fetch_api_definition', 'init', []);
        $renderable = new local_data_importer\output\connectors_page();
        $connectordata = $renderable->get_single_connector_instance($id);
        $importereditform = new local_data_importer_form(null, $connectordata);
        return $importereditform->display();
    }

    /**
     * @param $id
     */
    public function delete_connector_page($id) {
        global  $OUTPUT;
        $continuebutton = new single_button(new moodle_url('index.php', ['confirmdelete' => 1,
            'connectorid' => $id]), 'Yes', 'get', true);
        $cancelbutton = new single_button(new moodle_url('index.php', ['confirmdelete' => 0]), 'No', 'get', false);
        return $OUTPUT->confirm('Are you sure you want to delete this connector', $continuebutton, $cancelbutton);
    }


}