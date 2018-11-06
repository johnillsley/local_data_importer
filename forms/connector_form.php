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

class local_data_importer_connector_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;
        // Connector Name.
        $mform->addElement('text', 'connector_name', get_string('connector_name', 'local_data_importer'));
        $mform->addRule('connector_name', get_string('required'), 'required', null, 'client');
        $mform->setType('connector_name', PARAM_TEXT);
       // $mform->setDefault('connector_name', $this->_customdata['name']);

        // Connector Description.
        $mform->addElement('textarea', 'connector_description', get_string('connector_description', 'local_data_importer'), 'maxlength="254" size="50"');
        $mform->addRule('connector_description', get_string('required'), 'required', null, 'client');
        $mform->setType('connector_description', PARAM_TEXT);
        //$mform->setDefault('connector_description', $this->_customdata['description']);
        // Swaggerhub Definition API.
        $mform->addElement('text', 'openapidefinitionurl', get_string('openapidefinitionurl_label', 'local_data_importer'));
        $mform->addRule('openapidefinitionurl', get_string('required'), 'required', null, 'client');
        $mform->setType('openapidefinitionurl', PARAM_TEXT);
        //$mform->setDefault('openapidefinitionurl', $this->_customdata['openapidefinitionurl']);
        // Open API key.
        $mform->addElement('text', 'openapikey', get_string('apikey_label', 'local_data_importer'));
        $mform->setType('openapikey', PARAM_TEXT);
       // $mform->setDefault('openapikey', $this->_customdata['openapikey']);

        // Fetch Definition button.
        /*if (!isset($this->_customdata)) {
            $mform->addElement('button', 'fetchapidef', 'Fetch');
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
        }

        // Server API Key.
        $mform->addElement('text', 'serverapikey', get_string('apikey_label', 'local_data_importer'));
        $mform->setType('serverapikey', PARAM_TEXT);
        $mform->setDefault('serverapikey', $this->_customdata['serverapikey']);
        if (isset($this->_customdata['id'])) {
            // Its an edit.
            //$mform->addElement('hidden', 'connectorid', $this->_customdata['id']);
            $mform->setType('connectorid', PARAM_INT);
        }*/
        $this->add_action_buttons();
    }



}