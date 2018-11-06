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

class local_data_importer_edit_importer_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'pathitemname', 'Path Item Name');
        $mform->setType('pathitemname', PARAM_RAW);
        $mform->setDefault('pathitemname', $this->_customdata['name']);

        $mform->addElement('text', 'connnectorid', 'Connector ID', ['disabled']);
        $mform->setType('connnectorid', PARAM_RAW);
        $mform->setDefault('connnectorid', $this->_customdata['connnectorid']);

        $mform->addElement('text', 'pathitem', 'Path Item', ['disabled']);
        $mform->setType('pathitem', PARAM_RAW);
        $mform->setDefault('pathitem', $this->_customdata['pathitem']);
        $mform->addElement('text', 'subplugin', 'Subplugin', ['disabled']);
        $mform->setType('subplugin', PARAM_RAW);
        $mform->setDefault('subplugin', $this->_customdata['plugin_component']);
        $mform->addElement('hidden', 'pathitemid', $this->_customdata['id']);
        $mform->setType('pathitemid', PARAM_INT);
        $this->add_action_buttons();
    }
}