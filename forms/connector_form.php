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
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
/**
 * Class to display forms for edit and adding connectors
 *
 * @package    local_data_importer
 * @author     Hittesh Ahuja <j.s.illsley@bath.ac.uk>
 * @uses       moodleform
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_data_importer_connector_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;
        // Connector Name.
        $mform->addElement('text', 'name', get_string('connector_name', 'local_data_importer'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', $this->_customdata['name']);

        // Connector Description.
        $mform->addElement('textarea', 'description', get_string('connector_description', 'local_data_importer'),
            'maxlength="254" size="50"');
        $mform->addRule('description', get_string('required'), 'required', null, 'client');
        $mform->setType('description', PARAM_TEXT);
        $mform->setDefault('description', $this->_customdata['description']);

        // Swaggerhub Definition API.
        $mform->addElement('text', 'openapidefinitionurl', get_string('openapidefinitionurl_label', 'local_data_importer'));
        $mform->setType('openapidefinitionurl', PARAM_RAW);
        $mform->setDefault('openapidefinitionurl', $this->_customdata['openapidefinitionurl']);
        // Open API key.
        $mform->addElement('text', 'openapikey', get_string('apikey_label', 'local_data_importer'));
        $mform->setType('openapikey', PARAM_RAW);
        $mform->setDefault('openapikey', $this->_customdata['openapikey']);

        // Fetch Definition button.
        if (!isset($this->_customdata)) {
            $mform->addElement('button', 'fetchapidef', 'Fetch');
        }
        if (isset($this->_customdata['openapidefinitionurl'])) {
            $mform->updateElementAttr('openapidefinitionurl', ['disabled']);
        }
        if (isset($this->_customdata['openapikey'])) {
            $mform->updateElementAttr('openapikey', ['disabled']);
        }
        // Servers drop-down.
        if (isset($this->_customdata['server'])) {
            // Show a disabled text input.
            $mform->addElement('text', 'apiserver', get_string('server_label', 'local_data_importer'), ['disabled']);
            $mform->setDefault('apiserver', $this->_customdata['server']);
            $mform->setType('apiserver', PARAM_TEXT);
        } else {
            $servers = array();
            $mform->addElement('select', 'apiserver', get_string('server_label', 'local_data_importer'), $servers);
            $mform->addRule('apiserver', get_string('required'), 'required', null, 'client');
        }

        // Server API Key.
        $mform->addElement('text', 'serverapikey', get_string('serverapikey_label', 'local_data_importer'));
        $mform->setType('serverapikey', PARAM_TEXT);
        $mform->setDefault('serverapikey', $this->_customdata['serverapikey']);
        $mform->addRule('serverapikey', get_string('required'), 'required', null, 'client');
        if (isset($this->_customdata['id'])) {
            // Its an edit.
            $mform->addElement('hidden', 'connectorid', $this->_customdata['id']);
            $mform->setType('connectorid', PARAM_INT);
        }
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        $mform =& $this->_form;
    }
}