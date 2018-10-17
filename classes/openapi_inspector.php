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
 * A handler to process OpenAPI responses.
 * For details about Open API see https://swagger.io/specification/
 * This handler will work for openAPI version 3 and Swagger version 2
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A handler to process OpenAPI responses.
 *
 * @package    local_data_importer
 * @author     John Illsley <j.s.illsley@bath.ac.uk>
 * @copyright  2018 University of Bath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_data_importer_openapi_inspector {

    /**
     * @var $apiversion - The version of the specification used by the OpenAPI document.
     */
    public $openapiversion;

    /**
     * @var $version - The version of the OpenAPI document (which is distinct from the OpenAPI Specification version.
     */
    public $version;

    /**
     * @var $title - The title of the application.
     */
    public $title;

    /**
     * @var $description - A short description of the application.
     */
    public $description;

    /**
     * @var $servers - An array of Server Objects, which provide connectivity information to a target server.
     */
    public $servers;

    /**
     * @var $spec - full OpenAPI document converted into an array.
     */
    private $spec;

    /**
     * Constructor. Takes the raw OpenAPI in json format and sets commonly used parameters from the content.
     * It identifies the specification used by the OpenAPI document.
     *
     * @param string $json the OpenAPI document
     * @return void
     */
    public function __construct($json) {

        $this->spec = json_decode($json);

        $this->title        = $this->spec->info->title;
        $this->description  = $this->spec->info->description;
        $this->version      = $this->spec->info->version;

        if (!empty ($this->spec->swagger)) {
            // For swagger API version 2.
            $this->openapiversion = $this->spec->swagger;
            $this->servers = array( $this->spec->host . $this->spec->basePath );
        } else if (!empty ($this->spec->openapi)) {
            // For openAPI version 3.
            $this->openapiversion = $this->spec->openapi;
            $this->servers = array();
            foreach ($this->spec->servers as $server) {
                $this->servers[] = $server->url;
            }
        }
    }

    /**
     * Get pathitems from the OpenAPI specification. These can be filtered by HTTP method.
     *
     * @param array $methodfilter used to limit return to specific HTTP methods, default is "get" only.
     * @return array $pathitems as defined in the OpenAPI document.
     */
    public function get_pathitems($methodfilter = array("get")) {
        $pathitems = array();
        try {
            foreach ($this->spec->paths as $key => $path) {
                foreach ($path as $method => $methoddesc) {
                    if (in_array($method, $methodfilter)) {
                        if (array_key_exists($key, $pathitems)) {
                            if ($pathitems[$key]->method == $method) {
                                throw new Exception('A method / pathitem is duplicated in the OpenAPI definition');
                            }
                        }
                        $pathitems[$key] = clone $methoddesc;
                        $pathitems[$key]->method = $method;
                        $pathitems[$key]->path = $key;
                        unset($pathitems[$key]->responses);
                        unset($pathitems[$key]->parameters);
                    }
                }
            }
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage();
        }

        return $pathitems;
    }

    /**
     * Get all parameters used by a specific path and HTTP method. The default HTTP method is "get".
     *
     * @param string $pathitem identifier.
     * @param string $methodtype HTTP method, the default is "get".
     * @return array $parameters defining all parameters used by a specific path.
     */
    public function get_pathitem_parameters($pathitem, $methodtype="get") {
        $parameters = array();
        foreach ($this->spec->paths as $pathkey => $path) {
            foreach ($path as $method => $methoddesc) {
                if ($method == $methodtype && $pathkey == $pathitem) {
                    foreach ($methoddesc->parameters as $parameter) {
                        $parameters[$parameter->name] = $parameter;
                    }
                }
            }
        }
        return $parameters;
    }

    /**
     * Get definition of response for a specific path and HTTP method. The default HTTP method is "get".
     * Note: this will not process references to external documents
     *
     * @param string $pathitem identifier.
     * @param string $methodtype HTTP method, the default is "get".
     * @return array $responses defining all response values returned by a specific path.
     */
    public function get_pathitem_responses($pathitem, $methodtype="get") {
        $responses = array();
        foreach ($this->spec->paths as $pathkey => $path) {
            foreach ($path as $method => $methoddesc) {
                if ($method == $methodtype && $pathkey == $pathitem) {
                    foreach ($methoddesc->responses as $responsecode => $response) {
                        $responses[(string) $responsecode] = array();
                        $responses[(string) $responsecode]['description'] = $response->description;

                        if (substr($this->openapiversion, 0, 2) == '2.') {
                            // For swagger API version 2.
                            if (isset($response->schema)) {
                                $schema = $response->schema;
                            }
                        }
                        if (substr($this->openapiversion, 0, 2) == '3.') {
                            // For openAPI version 3.
                            if (is_array($response->content)) {
                                $schema = array_pop( $response->content );
                            }
                        }
                        if (isset($schema)) {
                            $responses[(string) $responsecode] = $this->build_response_properties($schema);
                        }
                    }
                }
            }
        }
        return $responses;
    }

    /**
     * Get easy to use list of responses for a specific path, response code and HTTP method. The default HTTP method is "get".
     * Because array keys for values in the response have been serialised they can be easy stored in the DB.
     *
     * @param string $pathitem identifier.
     * @param string $responsecode HTTP response code, the default is "200".
     * @param string $methodtype HTTP method, the default is "get".
     * @return array $selectables list of all values that exist in the path response.
     */
    public function get_pathitem_responses_selectable($pathitem, $responsecode="200", $methodtype="get") {
        $responses = $this->get_pathitem_responses($pathitem, $methodtype);
        $response = $responses[$responsecode];

        $selectables = $this->build_selectable_properties($response);
        return $selectables;
    }

    /**
     * Get a summary of all aspects of a OpenAPI definition and optional information and a specific path item.
     *
     * @param string $pathitem identifier.
     * @return string $debug easy to read HTML output.
     */
    public function debug($pathitem=null) {
        $debug = "<h2>Web service details</h2>";
        $debug .= "\nOpenAPI version: " . $this->openapiversion;
        $debug .= "\n<br/>Title: " . $this->title;
        $debug .= "\n<br/>Description: " . $this->description;
        $debug .= "\n<br/>Version: " . $this->version;
        $debug .= "\n<br/>Servers:";
        $debug .= "\n<pre>";
        $debug .= var_export($this->servers, true);
        $debug .= "</pre>";

        $debug .= "\n<h2>Path item list</h2>";
        $debug .= "\n<pre>";
        $debug .= var_export($this->get_pathitems(), true);
        $debug .= "</pre>";

        if (!empty($pathitem)) {

            $debug .= "\n<h2>Path item parameter list for " . $pathitem . "</h2>";
            $debug .= "\n<pre>";
            $debug .= var_export($this->get_pathitem_parameters($pathitem), true);
            $debug .= "</pre>";

            $debug .= "\n<h2>Path item response list for " . $pathitem . "</h2>";
            $debug .= "\n<pre>";
            $debug .= var_export($this->get_pathitem_responses($pathitem), true);
            $debug .= "</pre>";

            $debug .= "\n<h2>Serialised path item response list for " . $pathitem . "</h2>";
            $debug .= "\n<pre>";
            $debug .= var_export($this->get_pathitem_responses_selectable($pathitem, '200'), true);
            $debug .= "</pre>";
        }
        return $debug;
    }

    /**
     * Handles the responses part of a path item and combines any internal $ref references to definitions
     *
     * @param array $schema responses section of path item.
     * @return array $properties easy to read HTML output.
     */
    private function build_response_properties($schema) {
        $properties = array();
        $definition = new stdClass();

        foreach ($schema as $k => $v) {
            switch ($k) {

                case '$ref':
                    $pointer = explode("/", $v);
                    foreach ($pointer as $p) {
                        if ($p == "#") {
                            $definition = $this->spec;
                        } else {
                            $definition = $definition->$p;
                        }
                    }
                    $properties[$p] = $this->build_response_properties($definition);
                    break;

                case 'properties':
                    if (is_object($schema->properties)) {
                        $properties = $this->build_response_properties($schema->properties);
                    }
                    break;

                case 'type':
                    break;

                default:
                    if (empty($v->type) && key($v) == '$ref') {
                        $properties = $this->build_response_properties($v);
                    } else if ($v->type == "object" || $v->type == "array") {
                        $properties[$k] = $this->build_response_properties($v);
                    } else {
                        $properties[$k] = (array) $v;
                    }
                    break;
            }
        }
        return $properties;
    }

    /**
     * Get a summary of all aspects of a OpenAPI definition and optional information and a specific path item.
     *
     * @param string $pathitem identifier.
     * @return string $debug easy to read HTML output.
     */
    private function build_selectable_properties($properties) {
        $selectables = array();
        foreach ($properties as $property => $attributes) {
            if (isset($attributes['type'])) {
                $selectables[$property] = '["' . $property . '"]';
            } else {
                $sublevel = $this->build_selectable_properties($attributes);
                foreach ($sublevel as $k => $v) {
                    $selectables[$k] = '["' . $property . '"]' . $v;
                }
            }
        }
        return $selectables;
    }
}