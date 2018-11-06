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
 * Unit tests for the local/data_importer/classes/http_connection.php.
 *
 * @group      local_data_importer
 * @group      bath
 * @package    local/data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/data_importer/vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class local_data_importer_http_connection_test extends advanced_testcase {

    public function test_instantiate() {
        $this->resetAfterTest(true);

        $httpconnect = new local_data_importer_http_connection();

        $this->assertInstanceOf('local_data_importer_http_connection', $httpconnect);
    }

    public function test_create_client() {
        $this->resetAfterTest(true);

        $httpconnect = new local_data_importer_http_connection();
        $httpconnect->create_client(
                'https://api.swaggerhub.com/apis/UniversityofBath/GradesTransferOAS20/1.0.0',
                'eyJUb2tlblR5cGUiOiJBUEkiLCJhbGciOiJIUzUxMiJ9.eyJqdGkiOiIxMzU1ZTRkOC02NTU4LTRjZmUtOTFiYy03YTVlMTFjZGYwOTUiLCJpYXQiOjE1MDA0NjY3Mzh9.omTqGuXZLtQzUJqa_U1qfUGZMOuS1hMmofzPiRmZc-ALgKHi_X5Cfzb0t7dkcwabYgjmcEsevDOERNq-1Fak7w');

        $this->assertInstanceOf('GuzzleHttp\Client', $httpconnect->client);
    }

    public function test_get_response() {
        $this->resetAfterTest(true);

        $validjson = '[
 {
   "a": 1,
   "b": 2,
   "c": 3
 }
]';
        $brokenjson = '[
 {
   "a": 1,
   "b" 2,
   "c": 3
 }
]';
        $validxml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
  <row>
    <a>1</a>
    <b>2</b>
    <c>3</c>
  </row>
</root>';
        $brokenxml = '<?xml version="1.0" encoding="UTF-8"?>
<root>
  <row>
    <a>1</a>
    <b>2</b>
    <c>3
  </row>
</root>';

        $httpconnect = new local_data_importer_http_connection();

        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], $validjson),
                new Response(200, ['Content-Type' => 'application/json'], $brokenjson),
                new Response(200, ['Content-Type' => 'application/xml'], $validxml),
                new Response(200, ['Content-Type' => 'application/xml'], $brokenxml),
                new Response(200, ['Content-Type' => 'text/html']),
                new Response(403),
                new Response(504),
                new RequestException("Error Communicating with Server", new Request('GET', 'test'))
        ]);

        $handler = HandlerStack::create($mock);
        $httpconnect->client = new Client(['handler' => $handler]);

        $content = $httpconnect->get_response();
        $this->assertSame(json_decode($validjson, true), $content);

        try {
            $content = $httpconnect->get_response();
        } catch (Exception $e) {
            $this->assertEquals('JSON decode error', $e->getMessage());
            $this->assertEquals(4, $e->getCode());
        }

        $content = $httpconnect->get_response();
        $this->assertSame(json_decode(json_encode(simplexml_load_string($validxml)), true), $content);

        try {
            $content = $httpconnect->get_response();
        } catch (Exception $e) {
            $this->assertEquals(
                    'simplexml_load_string(): Entity: line 7: parser error : Opening and ending tag mismatch: c line 6 and row',
                    $e->getMessage());
            $this->assertEquals(2, $e->getCode());
        }

        try {
            $content = $httpconnect->get_response();
        } catch (Exception $e) {
            $this->assertEquals('Content type is incorrect (text/html). It must be either JSON or XML.', $e->getMessage());
        }

        try {
            $content = $httpconnect->get_response();
        } catch (Exception $e) {
            $this->assertEquals('Client error: `GET ` resulted in a `403 Forbidden` response', $e->getMessage());
            $this->assertEquals(403, $e->getCode());
        }

        try {
            $content = $httpconnect->get_response();
        } catch (Exception $e) {
            $this->assertEquals('Server error: `GET ` resulted in a `504 Gateway Time-out` response', $e->getMessage());
            $this->assertEquals(504, $e->getCode());
        }

        try {
            $content = $httpconnect->get_response();
        } catch (Exception $e) {
            $this->assertEquals('Error Communicating with Server', $e->getMessage());
        }
    }

    public function test_test_connection() {
        $this->resetAfterTest(true);

        $httpconnect = new local_data_importer_http_connection();

        $mock = new MockHandler([
                new Response(200),
                ]);

        $handler = HandlerStack::create($mock);
        $httpconnect->client = new Client(['handler' => $handler]);

        $this->assertTrue($httpconnect->test_connection());
    }
}