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
     * @var object enrol_dataimporter
     */
    private $enrol;
    
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
                'course_academic_year',
                'course_timeslot',
                'course_occurance',
                'course_categories_name');

        $this->roleid = $this->get_setting('enrolment_roleid');

        $this->enrol = new enrol_dataimporter_plugin();
    }

    /**
     * Creates a single enrolment using data that has already been validated.
     * Uses the importer setting to determine the role associated with the enrolment.
     * Creates a record of the enrolment creation locally so that it will not be created again.
     *
     * @param array $item contains all the data required to create an enrolment
     * @throws Exception from $this->enrol->enrol_user if the enrolment could not be created
     * @return void
     */
    protected function create_entity($item = array()) {
        global $DB;

        $userid         = $this->get_userid($item['user']['username']);
        $courseid       = $this->get_courseid($item['course']['idnumber']);
        $enrolinstance  = $DB->get_record('enrol', array("enrol" => self::ENROLMENT_METHOD, "courseid" => $courseid));
        
        $this->enrol->enrol_user($enrolinstance, $userid, $this->roleid, time());
        // Does not return true if successful so need to test.
        if ($userenrol = $DB->get_record('user_enrolments', array("enrolid" => $enrolinstance->id, "userid" => $userid))) {
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
        
        $userid         = $this->get_userid($item['user']['username']);
        $courseid       = $this->get_courseid($item['course']['idnumber']);
        $enrolinstance  = $DB->get_record('enrol', array("enrol" => self::ENROLMENT_METHOD, "courseid" => $courseid));
        
        // Not of use unless updating status, timestart or timeend.
        // Update_user_enrol(stdClass $instance, $userid, $status = NULL, $timestart = NULL, $timeend = NULL).
        $this->enrol->update_user_enrol($enrolinstance, $userid);
        // Does not return true if successful so need to test.
        if ($userenrol = $DB->get_record('user_enrolments', array("enrolid" => $enrolinstance->id, "userid" => $userid))) {
            $this->local_log($item, $userenrol->timemodified, 'updated');
        }
    }

    /**
     * Deletes a single enrolment.
     * Updates the local log to indicate that the enrolment has been deleted
     *
     * @param object $item contains all the data required to delete the enrolment
     * @throws Exception from $this->enrol->unenrol_user if the enrolment could not be deleted
     * @return array of 
     */
    protected function delete_entity($item) {
        global $DB;

        $userid         = $this->get_userid($item->user_username);
        $courseid       = $this->get_courseid($item->course_idnumber);
        $enrolinstance  = $DB->get_record('enrol', array("enrol" => self::ENROLMENT_METHOD, "courseid" => $courseid));
        
        $this->enrol->unenrol_user($enrolinstance, $userid);
        // Does not return true if successful so need to test.
        if ($userenrol = $DB->get_record('user_enrolments', array("enrolid" => $enrolinstance->id, "userid" => $userid))) {
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

        $config = get_config('local_data_importer'); // Needed to get time interval database table/fields. 

        // Only use timeslots that are current.
        $timeslots = $DB->get_records_sql("
                SELECT id, $config->date_interval_code_field code, $config->date_interval_academic_year_field year
                FROM {" . $config->date_interval_table . "}
                WHERE STR_TO_DATE($config->date_interval_start_date_field, '%Y-%m-%d') <= NOW()
                AND STR_TO_DATE($config->date_interval_end_date_field, '%Y-%m-%d') >= NOW()
         ");
        
        $filtersql = $this->get_parameter_filter_sql();

        if (count($timeslots) > 0) {
            // Make a csv string of current timeslot codes to use in following SQL. 
            $timeslotarray = array();
            foreach ($timeslots as $timeslot) {
                $timeslotarray[] = $timeslot->code;
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
        foreach($roles as $role) {
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
        $pathitems = $connectorpathitems->get_by_subplugin('course');
        $courseimporters = array();
        foreach ($pathitems as $pathitem) {
            $courseimporters[$pathitem->get_id()] = $pathitem->get_name();
        }
        $additionalsettings['course_pathitem_id'] = array(
                'field_label' => get_string('courseimporter', 'importers_enrolment'),
                'field_type' => 'select',
                'options' => $courseimporters
        );
        return $additionalsettings;
    }

    /**
     * Returns a userid from a username.
     *
     * @param string $username
     * @throws Exception if user cannot be found
     * @return integer userid
     */
    private function get_userid($username) {
        global $DB;

        if (!$userid = $DB->get_field('user', 'id', array('username' => $username))) {
            // TODO - TEMP CODE FOR TESTING.
            // Create user
            $user = create_user_record($username, 'abcdefg');
            return $user->id;
            // TODO - END OF TEMP CODE.
            throw new Exception("The user with username " . $username . " could not be found");
        }
        // TODO - could check idnumber too? What happens if idnumber and user name go out of sync in external data?

        return $userid;
    }

    /**
     * Returns a courseid from a course idnumber.
     *
     * @param string $idnumber
     * @throws Exception if course cannot be found
     * @return integer courseid
     */
    private function get_courseid($idnumber) {
        global $DB;

        if (!$courseid = $DB->get_field('course', 'id', array('idnumber' => $idnumber))) {
            throw new Exception("The course with idnumber " . $idnumber . " could not be found");
        }

        return $courseid;
    }
}