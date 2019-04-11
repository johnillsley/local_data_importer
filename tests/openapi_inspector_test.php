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
 * Unit tests for the local/data_importer/classes/openapi_inspector.php.
 * Moodle code checker does not like some lines of code with spaces at the end.
 * Do not remove these spaces or the phpunit tests will fail.
 * Also be careful that your text/code editor does not automatically strip these trailing spaces when saving.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2019 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_data_importer_openapi_inspector_testcase
 */
class local_data_importer_openapi_inspector_testcase extends advanced_testcase {

    /**
     * Test for method local_data_importer_openapi_inspector->__construct().
     */
    public function test_instantiate() {
        global $CFG;
        $this->resetAfterTest(true);

        $openapiarray = json_decode(file_get_contents($CFG->dirroot .
                '/local/data_importer/tests/fixtures/openapi_response.json'), true);
        $openapiinspector = new local_data_importer_openapi_inspector($openapiarray);
        $this->assertTrue($openapiinspector->openapiversion == "2.0"
                && $openapiinspector->version == "1.0.0"
                && $openapiinspector->title == "Grades Transfer Web Services"
                && $openapiinspector->description == "Grades Transfer Web Services"
                && array_pop($openapiinspector->servers) == "virtserver.swaggerhub.com/UniversityofBath/GT20/1.0.0"
                );
    }

    /**
     * Test for method local_data_importer_openapi_inspector->get_pathitems().
     */
    public function test_get_pathitems() {
        global $CFG;
        $this->resetAfterTest(true);

        $openapiarray = json_decode(file_get_contents($CFG->dirroot .
                '/local/data_importer/tests/fixtures/openapi_response.json'), true);
        $openapiinspector = new local_data_importer_openapi_inspector($openapiarray);
        $pathitems = $openapiinspector->get_pathitems();

        $expected = "array (
  '/MABS/MOD_CODE/{modcode}' => 
  array (
    'summary' => 'Get MABS details based on module code',
    'description' => 'Get MABS details based on module code',
    'operationId' => 'getMABS',
    'produces' => 
    array (
      0 => 'application/xml',
    ),
    'method' => 'get',
    'path' => '/MABS/MOD_CODE/{modcode}',
  ),
  '/USERS/STU_UDF1/{username}' => 
  array (
    'operationId' => 'getUser',
    'summary' => 'Get user details details based on username',
    'description' => 'Get user details details based on username',
    'method' => 'get',
    'path' => '/USERS/STU_UDF1/{username}',
  ),
  '/ASSESSMENTS/' => 
  array (
    'method' => 'get',
    'path' => '/ASSESSMENTS/',
  ),
)";

        $this->assertSame($expected, var_export($pathitems, true));
    }

    /**
     * Test for method local_data_importer_openapi_inspector->get_pathitem_parameters().
     */
    public function test_get_pathitem_parameters() {
        global $CFG;
        $this->resetAfterTest(true);

        $openapiarray = json_decode(file_get_contents($CFG->dirroot .
                '/local/data_importer/tests/fixtures/openapi_response.json'), true);
        $openapiinspector = new local_data_importer_openapi_inspector($openapiarray);
        $parameters = $openapiinspector->get_pathitem_parameters('/USERS/STU_UDF1/{username}');

        $expected = "array (
  0 => 
  array (
    'name' => 'username',
    'in' => 'path',
    'description' => 'Computing Services username',
    'required' => true,
    'type' => 'string',
  ),
)";

        $this->assertSame($expected, var_export($parameters, true));
    }

    /**
     * Test for method local_data_importer_openapi_inspector->get_pathitem_responses().
     */
    public function test_get_pathitem_responses() {
        global $CFG;
        $this->resetAfterTest(true);

        $openapiarray = json_decode(file_get_contents($CFG->dirroot .
                '/local/data_importer/tests/fixtures/openapi_response.json'), true);
        $openapiinspector = new local_data_importer_openapi_inspector($openapiarray);
        $responses = $openapiinspector->get_pathitem_responses('/USERS/STU_UDF1/{username}');

        $expected = "array (
  200 => 
  array (
    'STU' => 
    array (
      'STU.SRS' => 
      array (
        'STU_CODE' => 
        array (
          'type' => 'string',
        ),
        'SCJ' => 
        array (
          'SCJ.SRS' => 
          array (
            'SCJ_CODE' => 
            array (
              'type' => 'string',
              'example' => '149240600/1',
            ),
            'SCJ_STUC' => 
            array (
              'type' => 'string',
              'example' => 149240600,
            ),
            'SCJ_SPRC' => 
            array (
              'type' => 'string',
              'example' => 149240600,
            ),
            'SCJ_CRSC' => 
            array (
              'type' => 'string',
              'example' => 'UEAM-AKM04',
            ),
            'SCJ_STAC' => 
            array (
              'type' => 'string',
            ),
          ),
        ),
        'SCN' => 
        array (
          'SCN.CAMS' => 
          array (
            'SCN_STUC' => 
            array (
              'type' => 'string',
            ),
            'SCN_AYRC' => 
            array (
              'type' => 'string',
            ),
            'SCN_CODE' => 
            array (
              'type' => 'string',
            ),
          ),
        ),
      ),
    ),
  ),
)";

        $this->assertSame($expected, var_export($responses, true));
    }

    /**
     * Test for method local_data_importer_openapi_inspector->get_pathitem_responses_selectable().
     */
    public function test_get_pathitem_responses_selectable() {
        global $CFG;
        $this->resetAfterTest(true);

        $openapiarray = json_decode(file_get_contents($CFG->dirroot .
                '/local/data_importer/tests/fixtures/openapi_response.json'), true);
        $openapiinspector = new local_data_importer_openapi_inspector($openapiarray);
        $responses = $openapiinspector->get_pathitem_responses_selectable('/USERS/STU_UDF1/{username}');

        $expected = "array (
  'STU_CODE' => '[\"STU\"][\"STU.SRS\"][\"STU_CODE\"]',
  'SCJ_CODE' => '[\"STU\"][\"STU.SRS\"][\"SCJ\"][\"SCJ.SRS\"][\"SCJ_CODE\"]',
  'SCJ_STUC' => '[\"STU\"][\"STU.SRS\"][\"SCJ\"][\"SCJ.SRS\"][\"SCJ_STUC\"]',
  'SCJ_SPRC' => '[\"STU\"][\"STU.SRS\"][\"SCJ\"][\"SCJ.SRS\"][\"SCJ_SPRC\"]',
  'SCJ_CRSC' => '[\"STU\"][\"STU.SRS\"][\"SCJ\"][\"SCJ.SRS\"][\"SCJ_CRSC\"]',
  'SCJ_STAC' => '[\"STU\"][\"STU.SRS\"][\"SCJ\"][\"SCJ.SRS\"][\"SCJ_STAC\"]',
  'SCN_STUC' => '[\"STU\"][\"STU.SRS\"][\"SCN\"][\"SCN.CAMS\"][\"SCN_STUC\"]',
  'SCN_AYRC' => '[\"STU\"][\"STU.SRS\"][\"SCN\"][\"SCN.CAMS\"][\"SCN_AYRC\"]',
  'SCN_CODE' => '[\"STU\"][\"STU.SRS\"][\"SCN\"][\"SCN.CAMS\"][\"SCN_CODE\"]',
)";
        $this->assertSame($expected, var_export($responses, true));
    }

    /**
     * Test for method local_data_importer_openapi_inspector->debug().
     */
    public function test_debug() {
        global $CFG;
        $this->resetAfterTest(true);

        $openapiarray = json_decode(file_get_contents($CFG->dirroot .
                '/local/data_importer/tests/fixtures/openapi_response.json'), true);
        $openapiinspector = new local_data_importer_openapi_inspector($openapiarray);
        $debug = $openapiinspector->debug('/USERS/STU_UDF1/{username}');

        $expected = file_get_contents($CFG->dirroot . '/local/data_importer/tests/fixtures/openapi_inspector_debug_output.html');

        $this->assertSame($expected, $debug);
    }
}