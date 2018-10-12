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

class local_data_importer_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        //Connector Name
        $mform->addElement('text', 'connector_name', get_string('connector_name', 'local_data_importer'));
        $mform->addRule('connector_name', get_string('required'), 'required', null, 'client');
        $mform->setType('connector_name', PARAM_TEXT);

        //Connector Description
        $mform->addElement('textarea', 'connector_description', get_string('connector_description', 'local_data_importer'), 'maxlength="254" size="50"');
        $mform->addRule('connector_description', get_string('required'), 'required', null, 'client');
        $mform->setType('connector_description', PARAM_TEXT);

        // Swaggerhub Definition API
        $mform->addElement('text', 'openapidefinitionurl', get_string('openapidefinitionurl_label', 'local_data_importer'));
        $mform->addRule('openapidefinitionurl', get_string('required'), 'required', null, 'client');
        $mform->setType('openapidefinitionurl', PARAM_TEXT);

        // Fetch Definition button
        $mform->addElement('button', 'fetchapidef', 'Fetch');

        $this->add_action_buttons();
    }
}