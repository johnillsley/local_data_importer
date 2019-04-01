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
 * An interface with Guzzle for making http requests.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/data_importer/vendor/autoload.php');

use Guzzle\Http\Client;
use Guzzle\Http\Psr7\Response;
use Guzzle\Http\Psr7\Request;
use Guzzle\Http\Exception\RequestException;

class local_data_importer_http_connection {

    /**
     * @var object $client - Guzzle http client object.
     */
    public $client;

    /**
     * @var string $baseuri - the base_uri for the web service.
     */
    private $baseuri;

    /**
     * Constructor. If URI and API key are supplied the Guzzle client is created.
     *
     * @param string $baseuri - location of web service (optional)
     * @param string $apikey - API key required to access web service (optional)
     * @throws Exception if Guzzle returns an error
     * @return void
     */
    public function __construct($baseuri = null, $apikey = null) {

        if (isset($baseuri) && isset($apikey)) {
            try {
                $this->create_client($baseuri, $apikey);
            } catch (Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * Creates the Guzzle client adding proxy settings if they have been set in Moodle core settings.
     *
     * @param string $baseuri - location of web service (required)
     * @param string $apikey - API key required to access web service (required)
     * @throws Exception if connection parameters are missing or Guzzle new client fails.
     * @return void
     */
    public function create_client($baseuri, $apikey) {
        global $CFG;

        if (empty($baseuri) || empty($apikey)) {
            throw new Exception('Connection parameters are missing. Both URI and API key need to be specified.');
        }
        // The baseuri should have a trailing slash.
        if (substr($baseuri, -1) != '/') {
            $baseuri = $baseuri . '/';
        }
        // The baseuri should always be HTTPS.
        if (substr($baseuri, 0, 8) !== 'https://') {
            if (substr($baseuri, 0, 7) == 'http://') {
                $baseuri = str_replace('http://', 'https://', $baseuri);
            } else {
                $baseuri = 'https://' . $baseuri;
            }
        }
        $this->baseuri = $baseuri;
        // If set, use Moodle core settings to configure proxy.
        if (!empty($CFG->proxyhost) && !empty($CFG->proxyport)) {
            $proxycredentials = (!empty($CFG->proxyuser) && !empty($CFG->proxypassword))
                ? $CFG->proxyuser . ':' . $CFG->proxypassword . "@"
                : '';

            $proxy = array(
                'http' => 'http://' . $proxycredentials . $CFG->proxyhost . ':' . $CFG->proxyport, // Use this proxy with "http".
                'https' => 'http://' . $proxycredentials . $CFG->proxyhost . ':' . $CFG->proxyport, // Use this proxy with "https".
            );
        } else {
            $proxy = null;
        }

        $headers = ['Authorization' => $apikey];
        $httptimeout = get_config('local_data_importer', 'http_timeout');

        // TODO - test error when guzzle not installed.
        $this->client = new GuzzleHttp\Client([
                'base_uri' => $baseuri,
                'headers' => $headers,
                'proxy' => $proxy,
                'timeout' => $httptimeout,
                'debug' => false,
                'verify' => true
        ]);
    }

    /**
     * Gets a response through the Guzzle client using the preconfigured connection using create_client().
     *
     * @param string $relativeuri - optional relative path from base URI.
     * @param string $method http method
     * @throws Exception if get fails or content not in the correct format.
     * @return array - if valid JSON or XML have been received the return value will be an array
     */
    public function get_response($relativeuri = '', $method = 'GET') {
        global $DB;

        $timestart = microtime(true);
        $relativeuri = trim($relativeuri, '/'); // Relative URI should not have a leading slash.
        $errormessage = '';

        try {
            $content = "";
            $response = $this->client->request($method, $relativeuri);

            $contenttype = (isset($response->getHeader("Content-Type")[0]))
                    ? $response->getHeader("Content-Type")[0]
                    : 'Cannot determine content type';

            switch ($contenttype) {

                case 'application/json' : // The correct content type for JSON.
                case 'text/json' : // Is commonly used.
                case 'text/json;charset=UTF-8' : // Used by SAMIS.
                    $body = $response->getBody();
                    $content = json_decode($body, true);
                    // Doesn't throw error - nothing to catch.
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('JSON decode error', json_last_error());
                    }
                    break;

                case 'application/xml' : // The correct content type for XML.
                case 'text/xml' : // Is commonly used.
                case 'text/xml;charset=UTF-8' : // Used by SAMIS.
                    $body = $response->getBody();
                    $xml = simplexml_load_string($body);
                    $content = json_decode(json_encode($xml), true);
                    break;

                default :
                    throw new \Exception("Content type is incorrect (" . $contenttype . "). It must be either JSON or XML.");
            }

        } catch (\Throwable $e) {
            $errorcode = $e->getCode();
            $errormessage = "(" . $e->getCode() . ") " . $e->getMessage();
            throw $e; // Unit tests need this line to check exceptions.

        } finally {
            // Log the http connection outcome.
            if (!isset($contenttype)) {
                $contenttype = 'N/A';
            }
            if (isset($response)) {
                $responsecode = $response->getStatusCode();
            } else {
                $responsecode = $errorcode;
            }
            $timeend = microtime(true);
            $timetotal = ($timeend - $timestart) * 1000;
            $logitem = array(
                    'statuscode'    => $responsecode,
                    'url'           => $this->baseuri . $relativeuri,
                    'method'        => $method,
                    'contenttype'   => $contenttype,
                    'timesent'      => date( 'Y-m-d H:i:s'),
                    'milliseconds'  => $timetotal,
                    'errormessage'  => $errormessage
            );
            $DB->insert_record('local_data_importer_httplog', $logitem);
        }
        return $content;
    }

    /**
     * Checks the connection to the HTTP server.
     *
     * @return boolean - indicates whether the server returned a http 200 code
     */
    public function test_connection() {

        try {
            $response = $this->client->request('GET', '/', ['verify' => true, 'debug' => false]);
            if ($response->getStatusCode() == 200) {
                return true;
            } else {
                return false;
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            print $e->getMessage();
            local_data_importer_error_handler::log($e);
        }
    }
}