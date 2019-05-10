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
 * This file defines the enrolment entity importer which extends base plugin local_data_importer.
 *
 * @package    local/data_importer/importers/enrolment
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/data_importer/importers/entity_importer.php'); // Parent class definition.
require_once($CFG->dirroot . '/enrol/dataimporter/lib.php'); // Enrolment plugin for data_importer.
require_once($CFG->dirroot . '/group/lib.php'); // Group library.

/**
 * Class representing an entity importer to handle courses.
 */
class importers_enrolment_importer extends data_importer_entity_importer {

    /**
     * @const array of role shortnames that cannot be used for auto enrolments.
     */
    const SKIP_ROLES = array('sys_admin');

    /**
     * @const string enrolment plugin.
     */
    const ENROLMENT_METHOD = 'dataimporter';

    /**
     * @var integer
     */
    private $roleid;

    /**
     * @var integer
     */
    private $unenrolroleid;
    
    /**
     * @var string
     */
    private $groupnameformat;

    /**
     * @var object enrol_dataimporter
     */
    private $enrol;
    
    /**
     * @var null|object enrol_manual
     */
    private $enrolmanual = null;

    public function __construct($pathitemid) {

        parent::__construct($pathitemid);

        $this->logtable = 'importers_enrolment';
        $this->languagepack = 'importers_enrolment';

        $this->responses = array(
                'user' => array(
                        'username' => array("unique"),
                        'idnumber' => array("unique")
                ),
                'course' => array(
                        'idnumber' => array("unique")
                ),
                'other' => array(
                        'academic_year' => array("optional"),
                        'timeslot'      => array("optional"),
                        'occurance'     => array("optional")
                )
        );
        $this->parameters = array(
                'course_idnumber',
                'course_categories_name',
                'other_academic_year',
                'other_timeslot',
                'other_occurance'
        );
        $this->roleid = $this->get_setting('enrolment_roleid');
        $this->unenrolroleid = $this->get_setting('unenrolment_roleid');
        // $this->groupnameformat = str_replace('-', '_', $this->get_setting('group_name_format'));
        $this->groupnameformat = $this->get_setting('group_name_format');
        $this->enrol = new enrol_dataimporter_plugin(); // This extends the Moodle enrolment API.
        if ($this->unenrolroleid > 0) {
            // We need the manual enrol plugin for any unenrols.
            if (!$this->enrolmanual = enrol_get_plugin('manual')) {
                throw new Exception('Can not instantiate enrol_manual');
            }
        }
        
        // TODO - NEXT LINES FOR TESTING ONLY.
        global $CFG;
        if ($CFG->prefix == "mdl_") { // Don't want this for unit testing.
            $this->set_parameter_filter(array('course_categories_name' => 'HL')); // Should have 10 students.
        }
        // TODO - WHAT ABOUT ENROLS FOR ONE STUDENT? IS THIS REASONABLE to USE SAME METHOD?
    }

    /**
     * Creates a single enrolment using data that has already been validated.
     * Uses the importer setting to determine the role associated with the enrolment.
     * Creates a record of the enrolment creation locally so that it will not be created again.
     * Adds the user to a group if the group name format setting has been set.
     *
     * @param array $item contains all the data required to create an enrolment
     * @throws Exception from $this->enrol->enrol_user if the enrolment could not be created
     * @return void
     */
    protected function create_entity($item = array()) {
        global $DB;

        $user           = $this->get_user($item['user']['username']);
        $course         = $this->get_course($item['course']['idnumber']);
        $enrolinstance  = $DB->get_record('enrol', array("enrol" => self::ENROLMENT_METHOD, "courseid" => $course->id));
        // TODO - What if enrol instance doesn't exist?
        // $enrol = new enrol_dataimporter_plugin();
        // $enrolinstance = $enrol->add_instance($course); // Need to pass course object not courseid

        $this->enrol->enrol_user($enrolinstance, $user->id, $this->roleid, time());

        if (!empty($this->groupnameformat)) {
            // Need to add to group.
            $this->add_to_group($item, $course->id, $user->id, $enrolinstance);
        }
        // TODO - Put group inside grouping?
        // TODO - Think about exception handling if enrolment created but group fails.
        // Does not return true if successful so need to test.
        if ($userenrol = $DB->get_record('user_enrolments', array("enrolid" => $enrolinstance->id, "userid" => $user->id))) {
            $this->local_log($item, $userenrol->timecreated, 'created');
        }
    }

    /**
     * Updates a single enroment using data that has already been validated.
     * Updates the local log so that the updated import is recorded.
     *
     * @param array $item contains all the data required to create an enrolment
     * @throws Exception from $this->update_user_enrol if the enrolment could not be updated
     * @return void
     */
    protected function update_entity($item = array()) {
        global $DB;

        $user           = $this->get_user($item['user']['username']);
        $course         = $this->get_course($item['course']['idnumber']);
        $enrolinstance  = $DB->get_record('enrol', array("enrol" => self::ENROLMENT_METHOD, "courseid" => $course->id));

        // Not of use unless updating status, timestart or timeend.
        // Update_user_enrol(stdClass $instance, $userid, $status = NULL, $timestart = NULL, $timeend = NULL).
        $this->enrol->update_user_enrol($enrolinstance, $user->id);
        // Does not return true if successful so need to test.
        if ($userenrol = $DB->get_record('user_enrolments', array("enrolid" => $enrolinstance->id, "userid" => $user->id))) {
            $this->local_log($item, $userenrol->timemodified, 'updated');
        }
    }

    /**
     * Deletes a single enrolment.
     * Updates the local log to indicate that the enrolment has been deleted
     * Removes the user from the group if the group name format has been set.
     *
     * @param object $item contains all the data required to delete the enrolment
     * @throws Exception from $this->enrol->unenrol_user if the enrolment could not be deleted
     * @return void
     */
    protected function delete_entity($item) {
        global $DB;

        $user           = $this->get_user($item->user_username);
        $course         = $this->get_course($item->course_idnumber);
        $enrolinstance  = $DB->get_record('enrol', array("enrol" => self::ENROLMENT_METHOD, "courseid" => $course->id));

        if (!empty($this->groupnameformat)) {
            $this->remove_from_group($item, $course->id, $user->id);
        }

        // Does a new role need to replace the role being removed?
        if ($this->unenrolroleid > 0) {
            $manualenrolinstance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'manual'));
            if (!$manualenrolinstance) {
                $manualenrolinstance = $this->enrolmanual->add_instance($course);
            }
            // Create the MANUAL enrolment with role determined by pathitem setting.
            $this->enrolmanual->enrol_user($manualenrolinstance, $user->id, $this->unenrolroleid, time());
        }

        $this->enrol->unenrol_user($enrolinstance, $user->id);
        // This only removes user from groups if it is the only enrol left for the user on the course.
        // Does not return true if successful so need to test.
        if (!$userenrol = $DB->get_record('user_enrolments', array("enrolid" => $enrolinstance->id, "userid" => $user->id))) {
            $this->local_log((array)$item, time(), 'deleted');
        }
    }

    /**
     * Returns an array of parameters that can be used to configure web service requests
     * Uses predefined data for interval codes.
     * Applies any parameter filters that have been set.
     *
     * @throws Exception if prior conditions are not met.
     * @return array of parameters
     */
    public function get_parameters() {
        global $DB;

        if (!$coursepathemid = $this->get_setting('course_pathitem_id')) {
            throw new Exception('There is no course plugin connection, cannot do enrolment imports.');
        }
        if (!$courseimporter = new importers_course_importer($coursepathemid)) {
            throw new Exception('The pathitemid was not associated with a course importer.');
        }
        $timeslots = local_data_importer_global_parameters::date_interval_codes();
        $filtersql = $this->get_parameter_filter_sql();

        // TODO - Make output from SQL unique based on only parameters that are mapped!!!
        if (count($timeslots) > 0) {
            // Make a csv string of current timeslot codes to use in following SQL.
            $timeslotarray = array();
            foreach ($timeslots as $timeslot) {
                $timeslotarray[] = $timeslot;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($timeslotarray);

            $parameters = $DB->get_records_sql("
                    SELECT DISTINCT
                      c.id
                    , c.course_idnumber
                    , c.other_academic_year
                    , c.other_timeslot
                    , c.other_occurence
                    , c.course_categories_name
                    FROM {" . $courseimporter->logtable . "} c
                    WHERE c.pathitemid = $courseimporter->pathitemid
                    AND c.other_timeslot $insql
                    AND c.deleted = 0" . $filtersql
                    , $inparams);
        } else {
            $parameters = $DB->get_records_sql("
                    SELECT DISTINCT
                      c.id
                    , c.course_idnumber
                    , c.other_academic_year
                    , c.other_occurence
                    , c.course_categories_name
                    FROM {" . $courseimporter->logtable . "} c
                    WHERE c.pathitemid = $courseimporter->pathitemid
                    AND c.deleted = 0" . $filtersql
                    );
        }
        return $parameters;
    }

    /**
     * Outputs an array of form elements to create settings that are unique to this type of plugin.
     *
     * additional field type
     * additional field name
     * additional options
     * additional label
     * @return array of html form elements to be added to the form when an instance of this plugin is created
     */
    public function get_additional_form_elements() {
        global $DB;

        // Role selector to use for enrolment importer.
        $roles = $DB->get_records('role');
        $roleoptions = array();
        foreach ($roles as $role) {
            if (!in_array($role->shortname, self::SKIP_ROLES)) {
                $roleoptions[$role->id] = $role->shortname;
            }
        }
        $additionalsettings['enrolment_roleid'] = array(
                'field_label' => get_string('role', 'importers_enrolment'),
                'field_type' => 'select',
                'options' => $roleoptions
        );

        // Select the course importer that the enrolment importer will work with.
        $connectorpathitems = new local_data_importer_connectorpathitem();
        $pathitems = $connectorpathitems->get_by_subplugin('importers_course');
        $courseimporters = array();
        foreach ($pathitems as $pathitem) {
            $courseimporters[$pathitem->get_id()] = $pathitem->get_name();
        }
        $additionalsettings['course_pathitem_id'] = array(
                'field_label' => get_string('courseimporter', 'importers_enrolment'),
                'field_type' => 'select',
                'options' => $courseimporters
        );

        // Input for format of group name.
        $additionalsettings['group_name_format'] = array(
                'field_label' => get_string('groupnameformat', 'importers_enrolment'),
                'field_type' => 'text'
        );

        // Role selector for new role
        array_unshift($roleoptions, get_string('unenrolmentdonothing', 'importers_enrolment'));
        $additionalsettings['unenrolment_roleid'] = array(
                'field_label' => get_string('unenrolmentrole', 'importers_enrolment'),
                'field_type' => 'select',
                'options' => $roleoptions
        );
        return $additionalsettings;
    }

    /**
     * Returns a user from a username.
     *
     * @param string $username
     * @throws Exception if user cannot be found
     * @return object user
     */
    private function get_user($username) {
        global $DB;

        if (!$user = $DB->get_record('user', array('username' => $username))) {

            // TODO - TEMP CODE FOR TESTING.
            // Create user.
            $user = create_user_record($username, 'abcdefg');
            return $user;
            // TODO - END OF TEMP CODE.

            throw new Exception("The user with username " . $username . " could not be found");
        }
        // TODO - could check idnumber too? What happens if idnumber and user name go out of sync in external data?

        return $user;
    }

    /**
     * Returns a course from a course idnumber.
     *
     * @param string $idnumber
     * @throws Exception if course cannot be found
     * @return object course
     */
    private function get_course($idnumber) {
        global $DB;

        if (!$course = $DB->get_record('course', array('idnumber' => $idnumber))) {
            throw new Exception("The course with idnumber " . $idnumber . " could not be found");
        }

        return $course;
    }

    /**
     * Adds a user to an enrolment group.
     *
     * @param array $item
     * @param integer $courseid
     * @param integer $userid
     * @throws Exception if there is no setting for group name format.
     * @return integer group id
     */
    private function add_to_group($item, $courseid, $userid, $enrolinstance) {

        // Get group name format setting.
        if (empty($this->groupnameformat)) {
            throw new Exception('There is no setting for group name format.');
        }
        $groupname = $this->groupnameformat;

        // Put values into placeholders to construct group name.
        foreach ($item as $table => $field) {
            foreach ($field as $fieldname => $value) {
                $groupname = str_replace("{" . $table . "_" . $fieldname . "}", $value, $groupname);
            }
        }

        // Check if there any placeholders left.
        if (strpos($groupname, '{') || strpos($groupname, '}')) {
            throw new Exception('Could not construct the group name.');
        }

        // Check if group already exists.
        if (!$groupid = groups_get_group_by_name($courseid, $groupname)) {
            // Group doesn't exist yet so create it.
            $data = new stdClass();
            $data->courseid    = $courseid;
            $data->name        = $groupname;
            $data->description = get_string('groupcreatedbyplugin', 'importers_enrolment');
            $groupid = groups_create_group($data);
        }
        groups_add_member($groupid, $userid, "enrol_" . self::ENROLMENT_METHOD, $enrolinstance->id);
    }

    private function remove_from_group($item, $courseid, $userid) {

        // Get group name format setting.
        if (empty($this->groupnameformat)) {
            throw new Exception('There is no setting for group name format2.');
        }
        $groupname = $this->groupnameformat;

        // Put values into placeholders to construct group name.
        foreach ($item as $key => $value) {
            $groupname = str_replace("{" . $key . "}", $value, $groupname);
        }
        // Check if there any placeholders left.
        if (strpos($groupname, '{') || strpos($groupname, '}')) {
            throw new Exception('Could not construct the group name2.');
        }
        if (!$groupid = groups_get_group_by_name($courseid, $groupname)) {
            throw new Exception('Could not get group by group name.');
        }

        groups_remove_member($groupid, $userid);

        // If the group is now empty remove the group itself.
        if (count(groups_get_members($groupid)) == 0) {
            groups_delete_group($groupid);
        }
    }
}