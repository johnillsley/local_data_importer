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

class local_data_importer_add_importer_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        if (isset($this->_customdata['connectorid'])) {
            $options = null;
            if (isset($this->_customdata['selectedconnector'])) {
                $mform->addElement('static', 'selectedconnector', 'Selected Connector', $this->_customdata['selectedconnector']->name);
                $mform->setType('connectorid', PARAM_INT);
                $mform->addElement('hidden', 'connectorid', $this->_customdata['selectedconnector']->id);
                $mform->addElement('text', 'pathitemname', 'Path Item Name');
                $mform->addRule('pathitemname', get_string('required'), 'required', null, 'client');
                $mform->setType('pathitemname', PARAM_TEXT);
            }

            if (isset($this->_customdata['subplugin'])) {
                if (is_array($this->_customdata['subplugin'])) {
                    $options = $this->_customdata['subplugin'];
                    $mform->addElement('select', 'subplugin', 'Select Subplugin', $options);
                    $mform->addRule('subplugin', get_string('required'), 'required', null, 'client');
                } else {
                    $mform->addElement('static', 'selectedsubplugin', 'Sub plugin Selected', $this->_customdata['subplugin']);
                    $mform->addElement('hidden', 'subplugin', $this->_customdata['subplugin']);
                    $mform->setType('subplugin', PARAM_RAW);

                }
            }
            if (isset($this->_customdata['pathitem'])) {
                if (is_array($this->_customdata['pathitem'])) {
                    $options = $this->_customdata['pathitem'];
                    $mform->addElement('select', 'pathitem', 'Select Path Item', $options);
                    $mform->addRule('pathitem', get_string('required'), 'required', null, 'client');
                } else {
                    $mform->addElement('static', 'selectedpathitem', 'Path Item Selected', $this->_customdata['pathitem']);
                    $mform->addElement('static', 'selectedpathitemname', 'Path Item Name', $this->_customdata['pathitemname']);
                    $mform->addElement('hidden', 'pathitem', $this->_customdata['pathitem']);
                    $mform->setType('pathitem', PARAM_RAW);
                }

            }

            $options = null;
            if (isset($this->_customdata['subpluginparams'])) {
                $subpluginparams = $this->_customdata['subpluginparams'];
                foreach ($subpluginparams as $paramkey => $arrayparam) {
                    $mform->addElement('static', 'subpluginparams', "Sub plugin response", "<strong>" . $arrayparam['name'] . "</strong>");
                    $options = $this->_customdata['pathitemparams'];
                    $mform->addElement('select', $arrayparam["name"], 'Web Service response', $options);
                    $mform->addRule($arrayparam["name"], get_string('required'), 'required', null, 'client');

                }
            }
        } else {
            $renderable = new local_data_importer\output\importers_page();
            $connectors = $renderable->connector_items();
            if (is_array($connectors)) {
                foreach ($connectors['connectoritems'] as $k => $objconnector) {
                    $connectoroptions[$objconnector->id] = $objconnector->name;
                }
                $mform->addElement('select', 'connectorid', 'Select Connector', $connectoroptions);
                $mform->addRule('connectorid', get_string('required'), 'required', null, 'client');


            }

        }
        $mform->addElement('hidden', 'action', 'fetch_path_items');
        $mform->setType('action', PARAM_RAW);
        $this->add_action_buttons();
    }

    public function definition_after_data() {
        $mform = $this->_form;
        $hiddenelement = $mform->getElement('action');
        if (isset($this->_customdata['pathitem'])) {
            $hiddenelement->setValue('fetch_response_params');
        }
        if (isset($this->_customdata['subpluginparams'])) {
            $hiddenelement->setValue('save');
        }

    }
}
