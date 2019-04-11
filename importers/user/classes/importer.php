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
 * This file defines the user entity importer which extends base plugin local_data_importer.
 *
 * @package    local/data_importer/importers/user
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/data_importer/importers/entity_importer.php'); // Parent class definition.
require_once($CFG->dirroot . '/user/lib.php'); // User lib functions.

/**
 * Class representing an entity importer to handle courses.
 */
class importers_user_importer extends data_importer_entity_importer {

    public function __construct($pathitemid) {

        parent::__construct($pathitemid);

        $this->logtable = 'importers_user';
        $this->languagepack = 'importers_user';

        $this->responses = array(
                'user' => array(
                        'username'  => array("unique"),
                        'idnumber'  => array("unique"),
                        'firstname' => array(),
                        'lastname'  => array(),
                        'email'     => array()
                )
        );
        $this->parameters = array();
        $this->parentimporter = null;
    }

    protected function create_entity($item = array()) {
        global $DB;
        
        $user = new stdClass();
        $user->username     = $item['user']['username'];
        $user->idnumber     = $item['user']['idnumber'];
        $user->firstname    = $item['user']['firstname'];
        $user->lastname     = $item['user']['lastname'];
        $user->email        = $item['user']['email'];
        if ($newuserid = user_create_user($user)) {
            // Make sure timecreated in local log matches time created in user table.
            $timecreated = $DB->get_field('user', 'timecreated', array('id' => $newuserid));
            $this->local_log($item, $timecreated, 'created');
        }
    }

    protected function update_entity($item = array()) {
        global $DB;
        
        $userid = $DB->get_field('user', 'id', array('idnumber' => $item['user']['idnumber']));

        $user = new stdClass();
        $user->id           = $userid;
        $user->username     = $item['user']['username'];
        $user->firstname    = $item['user']['firstname'];
        $user->lastname     = $item['user']['lastname'];
        $user->email        = $item['user']['email'];
        if (user_update_user($user)) {
            // Make sure timecreated in local log matches time created in user table.
            $timemodified = $DB->get_field('user', 'timemodified', array('id' => $userid));
            $this->local_log($item, $timemodified, 'updated');
        }
    }

    protected function delete_entity($item = array()) {
        global $DB;


        $userid = $DB->get_field('user', 'id', array('idnumber' => $item['user']['idnumber']));
        $user = new stdClass();
        $user->id = $userid;
        
        if (user_delete_user($user)) {
            $this->local_log($item, time(), 'deleted');
        }
    }

    public function get_parameters() {

        $parameters = array();

        return $parameters;
    }

    public function get_additional_form_elements() {
        
        $additionalsettings = array();

        // Authentication selector for users created with this importer.
        $auths = core_component::get_plugin_list('auth');
        $authoptions = array();
        foreach($auths as $auth => $plugin) {
            if (is_enabled_auth($auth)) {
                $authoptions[$auth] = get_string('pluginname', "auth_{$auth}");
            }
        }
        $additionalsettings['user_auth'] = array(
                'field_label' => get_string('authtype', 'importers_enrolment'),
                'field_type' => 'select',
                'options' => $authoptions
        );
        return $additionalsettings;
    }
}