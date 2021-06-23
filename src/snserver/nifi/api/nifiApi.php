<?php

namespace api;

use api\curl;
use Aws\CloudFront\Exception\Exception;
use nifiComponents\Component;
use nifiComponents\Connection;
use nifiComponents\ProcessGroup;

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
function format_json($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}

function directory_map($source_dir, $directory_depth = 0, $hidden = FALSE)
{
    if ($fp = @opendir($source_dir))
    {
        $filedata   = array();
        $new_depth  = $directory_depth - 1;
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        while (FALSE !== ($file = readdir($fp)))
        {
            // Remove '.', '..', and hidden files [optional]
            if ($file === '.' OR $file === '..' OR ($hidden === FALSE && $file[0] === '.'))
            {
                continue;
            }

            is_dir($source_dir.$file) && $file .= DIRECTORY_SEPARATOR;

            if (($directory_depth < 1 OR $new_depth > 0) && is_dir($source_dir.$file))
            {
                $filedata[$file] = directory_map($source_dir.$file, $new_depth, $hidden);
            }
            else
            {
                $filedata[] = $file;
            }
        }

        closedir($fp);
        return $filedata;
    }

    return FALSE;
}

/**
 *
 */
class nifiApi
{
    /**
     *
     * @var string
     */
    protected $protocol = 'https';
    /**
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     *
     * @var string
     */
    protected $port = '9443';

    /**
     *
     * @var string
     */
    protected $secure = '';

    /**
     *
     * @var string
     */
    protected $sslCert = '';

    /**
     *
     * @var string
     */
    protected $sslCaCert = '';

    /**
     *
     * @var string
     */
    private $username = '';

    /**
     *
     * @var string
     */
    private $password = '';

    /**
     *
     * @var unknown
     */
    protected $cookieFile = null;

    /**
     *
     * @var \KLogger
     */
    protected $log = '';

    /**
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        if (count($config) > 0) {
            $this->initialize($config);
        }
    }

    /**
     *
     * @param array $config
     */
    private function initialize($config = array())
    {
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }
        if (!isset($config['cookieFile']))
            $this->cookieFile = tmpfile();
    }

    /**
     *
     * @return \api\curl
     */
    private function initializeCurl()
    {
        $curl = new \api\curl();
        //$curl->cookie_file = $this->local_cookie_file;
        $curl->options['CURLOPT_TIMEOUT'] = 180;
        $curl->options['CURLOPT_CONNECTTIMEOUT'] = 60;
        //$curl->options['CURLOPT_VERBOSE'] = TRUE;
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = FALSE;
        $curl->options['CURLOPT_SSL_VERIFYHOST'] = FALSE;

        if ($this->sslCert) {
            $curl->options['CURLOPT_SSLCERT'] = $this->sslCert;
        }
        if ($this->sslCaCert) {
            $curl->options['CURLOPT_CAINFO'] = $this->sslCaCert;
        }
        //$curl->options['CURLOPT_SSLCERT'] = "/var/www/softnas/keys/nifi/$this->host/buurst.pem";
        //$curl->options['CURLOPT_CAINFO'] = "/var/www/softnas/keys/nifi/$this->host/server.crt";
        $curl->follow_redirects = FALSE;
        $curl->cookie_file = $this->cookieFile;
        return $curl;
    }

    /**
     *
     * @param string $api_command
     * @return string
     */
    private function _constructUrl($api_command = '')
    {
        $url = "$this->protocol://$this->host";
        if ($this->port) {
            $url .= ":$this->port";
        }
        $url .= "/nifi-api";
        if ($api_command) {
            $url .= "$api_command";
        }

        return $url;
    }

    /**
     * @param int $type Component's type
     * @param string $name Component's name
     * @param ProcessGroup $parent Process Group this component child of
     * @param double $x x position in the Nifi UI layout
     * @param double $y y position in the Nifi UI layout
     * @param array $params Array of additional component's configuration parameters
     * @throws \Exception
     * @return Component
     */
    private function _createComponent($type, $name, &$parent, $x, $y, $params=array())
    {
        switch ($type) {
            case Component::$TYPE_REMOTE_PROCESS_GROUP:
                $url = $this->_constructUrl("/process-groups/{$parent->getId()}/remote-process-groups");
                break;
            case Component::$TYPE_INPUT_PORT:
                $url = $this->_constructUrl("/process-groups/{$parent->getId()}/input-ports");
                break;
            case Component::$TYPE_OUTPUT_PORT:
                $url = $this->_constructUrl("/process-groups/{$parent->getId()}/output-ports");
                break;
            case Component::$TYPE_PROCESSOR:
                $url = $this->_constructUrl("/process-groups/{$parent->getId()}/processors");
                break;
            default:
                throw new \Exception('Wrong component type provided');
        }

        $params['revision'] = array('version' => 0);
        if (array_key_exists('component', $params)) {
            $params['component']['name'] = $name;
            $params['component']['position'] = array('x' => $x, 'y' => $y);
        } else {
            $params['component'] = array(
                'name' => $name,
                'position' => array(
                    'x' => $x,
                    'y' => $y
                )
            );
        }

        $apiReply = $this->post($url, $params);

        $component = new Component($name, $x, $y, $apiReply->uri, $apiReply->id, $type);
        $component->setApiData($apiReply);
        $parent->addComponent($component);

        return $component;
    }

    /**
     * @param string $resource
     * @param string $action
     * @param string $componentId
     * @param $users
     * @return \stdClass
     */
    private function _createAccessPolicy($resource, $action, $componentId, $users)
    {
        $params = array(
            'revision' => array(
                'version' => 0
            ),
            'component' => array(
                'action' => $action,
                'resource' => $resource,
                'componentReference' =>array(
                    'id' => $componentId
                ),
                'users' => $users
            )
        );

        return $this->post($this->_constructUrl('/policies'), $params);
    }

    /**
     * @param \stdClass $accessPolicy
     * @param \stdClass[] $users
     * @return \stdClass
     */
    private function _updateAccessPolicy($accessPolicy, $users)
    {
        $users = array_merge($accessPolicy->component->users, $users);
        $params = array(
            'revision' => $accessPolicy->revision,
            'component' => array(
                'id' => $accessPolicy->component->id,
                'users' => $users
            )
        );

        return $this->put($accessPolicy->uri, $params);
    }

    /**
     * @param Component|ProcessGroup $component - Component to update
     * @param array $params
     * @throws \Exception
     */
    public function _updateComponent(&$component, $params)
    {
        $params['revision'] = $this->getRevision($component->getUri());
        if (array_key_exists('component', $params)) {
            $params['component']['id'] = $component->getId();
        } else {
            $params['component'] = array(
                'id' => $component->getId()
            );
        }
        $apiReply = $this->put($component->getUri(), $params);
        $component->setApiData($apiReply);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $cookieFile
     * @throws \Exception if fails
     * @return \stdClass
     */
    public function post($url, $params = array(), $cookieFile = NULL)
    {
        $curl = $this->initializeCurl();
        if ($cookieFile) {
            $curl->cookie_file = $cookieFile;
        }
        if (gettype($params) == 'array' || gettype($params) == 'object')
            $json = format_json(json_encode($params));
        elseif (gettype($params) == 'string')
            $json = format_json($params);
        else
            throw new \Exception('Invalid parameter type in post request');

        $curl->headers['Content-Type'] = 'application/json';
        $this->log->LogMaint("POST Url : $url");
        $this->log->LogMaint("POST json : $json");

        # retry request on 409
        $retries = 0;
        while ($retries < 90) {
            $response = $curl->post($url, $json);
            if ($response && isset($response->headers['http_code'])) {
                if ($response->headers['http_code'] == 200 || $response->headers['http_code'] == 201 || $response->headers['http_code'] == 202) {
                    return json_decode($response->body);
                } elseif ($response->headers['http_code'] == 409) {
                    sleep(1);
                    $retries += 1;
                    continue;
                } else {
                    $message = $response != '' ? $response : '';
                    $message .= $curl->error() != '' ? ($message != '' ? '. ' : '') . $curl->error() : '';
                    throw new \Exception($message, $response->headers['http_code']);
                }
            } else {
                throw new \Exception('Empty response from server.');
            }
        }
        throw new \Exception('Unable to make post request. Exceeded amount of retries.');
    }

    /**
     * @param string $url
     * @param array|string $params
     * @param string $cookieFile
     * @throws \Exception
     * @return \stdClass
     */
    public function put($url, $params, $cookieFile = NULL)
    {
        $curl = $this->initializeCurl();
        if ($cookieFile) {
            $curl->cookie_file = $cookieFile;
        }
        $curl->headers['Content-Type'] = 'application/json';
        //$this->log->LogDebug("Url : $url");
        $this->log->LogMaint("PUT Url : $url");

        # retry request on 409
        $retries = 0;
        while ($retries < 90) {
            if (gettype($params) == 'array' || gettype($params) == 'object')
                $json = format_json(json_encode($params));
            elseif (gettype($params) == 'string')
                $json = format_json($params);
            else
                throw new \Exception('Invalid parameter type in post request');
            $this->log->LogMaint("PUT json : $json");
            $response = $curl->put($url, $json);
            if ($response && isset($response->headers['http_code'])) {
                $this->log->LogMaint("put: response code {$response->headers['http_code']}");
                if ($response->headers['http_code'] == 200 || $response->headers['http_code'] == 201 || $response->headers['http_code'] == 202) {
                    return json_decode($response->body);
                } elseif ($response->headers['http_code'] == 409 || $response->headers['http_code'] == 400) {
                    $params['revision'] = $this->getRevision($url);
                    sleep(1);
                    $retries += 1;
                    continue;
                } else {
                    $message = $response != '' ? $response : '';
                    $message .= $curl->error() != '' ? ($message != '' ? '. ' : '') . $curl->error() : '';
                    throw new \Exception($message, $response->headers['http_code']);
                }
            } else {
                throw new \Exception('Empty response from server.');
            }
        }
        throw new \Exception('Unable to make post request. Exceeded amount of retries');
    }

    /**
     *
     * @param string $url
     * @param array $params
     * @param string $cookieFile
     * @throws \Exception
     * @return \stdClass
     */
    public function delete($url, $params = array(), $cookieFile = NULL)
    {
        $curl = $this->initializeCurl();

        if ($cookieFile) {
            $curl->cookie_file = $cookieFile;
        }
        if (gettype($params) == 'array' || gettype($params) == 'object')
            $json = format_json(json_encode($params));
        elseif (gettype($params) == 'string')
            $json = format_json($params);
        else
            throw new \Exception ('Invalid parameter type in post request');
        $curl->headers['Content-Type'] = 'application/json';
        $this->log->LogMaint("DELETE Url : $url");
        $this->log->LogMaint("DELETE json : $json");
        $response = $curl->delete($url, $json);
        isset($response->headers) && $this->log->LogMaint(format_json(json_encode($response->headers)));
        isset($response->body) && $this->log->LogMaint(format_json($response->body));
        if ($response && isset($response->headers['http_code']) && ($response->headers['http_code'] == 200 || $response->headers['http_code'] == 202)) {

            if ($response->headers['http_code'] == 200) {
                $reply = json_decode($response->body);
                $reply->httpCode = $response->headers['http_code'];
            } else {
                $reply = new \stdClass();
                $reply->content = $response->body;
                $reply->httpCode = $response->headers['http_code'];
                $reply->headers = $response->headers;
            }
        } else {
            $message = $response != '' ? $response : '';
            $message .= $curl->error() != '' ? ($message != '' ? '. ' : '') . $curl->error() : '';
            $code = isset($response->headers['http_code']) ? $response->headers['http_code'] : 0;
            throw new \Exception($message, $code);
        }
        return $reply;
    }

    /**
     *
     * @param string $url
     * @param array $params
     * @param string $cookieFile
     * @throws \Exception
     * @return \stdClass
     */
    public function get($url, $params = array(), $cookieFile = NULL)
    {
        $curl = $this->initializeCurl();

        if ($cookieFile) {
            $curl->cookie_file = $cookieFile;
        }
        $this->log->LogMaint("GET Url : $url");
        $response = $curl->get($url, $params);
        if ($response && isset($response->headers['http_code']) && ($response->headers['http_code'] == 200 || $response->headers['http_code'] == 201)) {
            //$this->log->LogDebug(format_json($response->body));
            return json_decode($response->body);
        } else {
            $message = $response != '' ? $response : '';
            $message .= $curl->error() != '' ? ($message != '' ? '. ' : '') . $curl->error() : '';
            $code = isset($response->headers['http_code']) ? $response->headers['http_code'] : 0;
            throw new \Exception($message, $code);
        }
    }

    /**
     * Update component from NIFI API
     * @param Component $component
     * @return Component
     */
    public function getComponent(&$component)
    {
        $apiData = $this->get($component->getUri());
        $component->setApiData($apiData);

        return $component;
    }

    /**
     * @param string $url URI of components base, eg. /process-groups/{id}/connections
     * @throws \Exception
     * @return \stdClass
     */
    public function getRevision($url)
    {
        try {
            $response = $this->get($url);
        } catch (\Exception $e) {
            throw new \Exception("Failed to get revision. Details: " . $e->getMessage() . '. Http Code: ' . $e->getCode());
        }
        return $response->revision;
    }

    public function getAccessPolicy($action, $resource) {
        try {
            $response = $this->get($this->_constructUrl("/policies/{$action}{$resource}"));
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                return FALSE;
            } else {
                throw $e;
            }
        }
        return $response;
    }

    /**
     *
     * @param string $name
     * @param ProcessGroup $parent
     * @param double $x
     * @param double $y
     * @return \nifiComponents\ProcessGroup
     */
    public function createProcessGroup($name, &$parent, $x, $y)
    {
        $params = array(
            'revision' => array(
                'version' => 0
            ),
            'component' => array(
                'name' => $name,
                'position' => array(
                    'x' => $x,
                    'y' => $y
                )
            )
        );
        $url = $this->_constructUrl("/process-groups/{$parent->getId()}/process-groups");
        $apiReply = $this->post($url, $params);

        $processGroup = new ProcessGroup($name, $x, $y, $apiReply->uri, $apiReply->id);
        $processGroup->setApiData($apiReply);
        $parent->addComponent($processGroup);

        return $processGroup;
    }

    /**
     * @param string $name
     * @param ProcessGroup $parent
     * @param double $x
     * @param double $y
     * @param string $targetUri
     * @return Component
     */
    public function createRemoteProcessGroup($name, &$parent, $x, $y, $targetUri)
    {
        $params = array(
            'component' => array(
                'targetUri' => $targetUri
            )
        );
        return $this->_createComponent(Component::$TYPE_REMOTE_PROCESS_GROUP, $name, $parent, $x, $y, $params);
    }

    /**
     * @param string $name Port's name
     * @param ProcessGroup $parent Parent Process Group
     * @param double $x x position in the Nifi UI
     * @param double $y y position in the Nifi UI
     * @return Component Output Port
     */
    public function createOutputPort($name, &$parent, $x, $y)
    {
        return $this->_createComponent(Component::$TYPE_OUTPUT_PORT, $name, $parent, $x, $y);
    }

    /**
     * @param string $name Port's name
     * @param ProcessGroup $parent Process Group to add input port to
     * @param double $x x position in the Nifi UI
     * @param double $y y position in the Nifi UI
     * @return Component Input Port
     */
    public function createInputPort($name, &$parent, $x, $y)
    {
        return $this->_createComponent(Component::$TYPE_INPUT_PORT, $name, $parent, $x, $y);
    }

    /**
     * @param ProcessGroup $parent
     * @param ProcessGroup|Component $sourceProcessGroup
     * @param Component $sourceComponent
     * @param ProcessGroup|Component $destinationProcessGroup
     * @param Component $destinationComponent
     * @param array $selectedRelationships
     * @param array $prioritizers Array of prioritizer classes
     * @return Connection
     * @throws \Exception
     */
    public function createConnection(
        &$parent,
        $sourceProcessGroup,
        $sourceComponent,
        $destinationProcessGroup,
        $destinationComponent,
        $selectedRelationships = array(),
        $backPressureObjectThreshold = 10000,
        $backPressureDataSizeThreshold = '1 GB',
        $prioritizers = array('org.apache.nifi.prioritizer.PriorityAttributePrioritizer', 'org.apache.nifi.prioritizer.FirstInFirstOutPrioritizer')
    )
    {
        // Determine source component type for connection
        if ($sourceProcessGroup->type == Component::$TYPE_REMOTE_PROCESS_GROUP) {
            $sourceType = 'REMOTE_OUTPUT_PORT';
        } else {
            switch ($sourceComponent->type) {
                case Component::$TYPE_INPUT_PORT:
                    $sourceType = 'INPUT_PORT';
                    break;
                case Component::$TYPE_OUTPUT_PORT:
                    $sourceType = 'OUTPUT_PORT';
                    break;
                case Component::$TYPE_PROCESSOR:
                    $sourceType = 'PROCESSOR';
                    break;
                default:
                    throw new \Exception('Unsupported component for connection source');
            }
        }
        // Determine destination component type for connection
        if ($destinationProcessGroup->type == Component::$TYPE_REMOTE_PROCESS_GROUP) {
            $destinationType = 'REMOTE_INPUT_PORT';
        } else {
            switch ($destinationComponent->type) {
                case Component::$TYPE_INPUT_PORT:
                    $destinationType = 'INPUT_PORT';
                    break;
                case Component::$TYPE_OUTPUT_PORT:
                    $destinationType = 'OUTPUT_PORT';
                    break;
                case Component::$TYPE_PROCESSOR:
                    $destinationType = 'PROCESSOR';
                    break;
                default:
                    throw new Exception('Unsupported component for connection destination');
            }
        }

        $srcId = ($sourceType === 'REMOTE_OUTPUT_PORT') ? $sourceComponent->getIdInRPG() : $sourceComponent->getId();
        $dstId = ($destinationType === 'REMOTE_INPUT_PORT') ? $destinationComponent->getIdInRPG() : $destinationComponent->getId();

        $params = array(
            'revision' => array(
                'version' => 0
            ),
            'component' => array(
                'prioritizers' => $prioritizers,
                'source' => array(
                    'id' => $srcId,
                    'groupId' => $sourceProcessGroup->getId(),
                    'type' => $sourceType,
                ),
                'destination' => array(
                    'id' => $dstId,
                    'groupId' => $destinationProcessGroup->getId(),
                    'type' => $destinationType,
                ),
                'backPressureObjectThreshold' => $backPressureObjectThreshold,
                'backPressureDataSizeThreshold' => $backPressureDataSizeThreshold
            )
        );

        if (count($selectedRelationships) > 0) {
            $params['component']['selectedRelationships'] = $selectedRelationships;
        }
        $url = $this->_constructUrl("/process-groups/{$parent->getId()}/connections");
        $apiReply = $this->post($url, $params);

        $connection = new Connection($apiReply->uri, $apiReply->id, $sourceProcessGroup, $sourceComponent, $destinationProcessGroup, $destinationComponent);
        $connection->setApiData($apiReply);
        $parent->addConnection($connection);

        return $connection;
    }

    /**
     * @param Component $portComponent
     * @param array $users
     * @throws \Exception
     */
    public function setRootPortPolicy(&$portComponent, $users = array(), $concurrentThread = 1)
    {
        if ($portComponent->type == Component::$TYPE_OUTPUT_PORT) {
            $resource = "/data-transfer/output-ports/{$portComponent->getId()}";
        }
        elseif ($portComponent->type == Component::$TYPE_INPUT_PORT) {
            $resource = "/data-transfer/input-ports/{$portComponent->getId()}";
        }
        else {
            throw new \Exception('Wrong component type provided');
        }

        $params = array(
            'component' => array(
                'concurrentlySchedulableTaskCount' => $concurrentThread
            )
        );

        $this->_updateComponent($portComponent, $params);

        $nifiUsers = array();
        foreach ($users as $user) {
            $nifiUsers += $this->findNifiUser($user);
        }

        $this->_createAccessPolicy($resource, 'write', $portComponent->getId(), $nifiUsers);
        $this->_createAccessPolicy($resource, 'read', $portComponent->getId(), $nifiUsers);
    }

    /**
     * @param string $searchNeedle
     * @return mixed
     */
    public function findNifiUser($searchNeedle)
    {
        $apiResponse = $this->get($this->_constructUrl('/tenants/search-results?q=' . urlencode($searchNeedle)));
        return $apiResponse->users;
    }

    /**
     * @param string $username
     * @return \stdClass
     */
    public function createNifiUser($username)
    {
        $params = array(
            'revision' => array(
                'version' => 0
            ),
            'component' => array(
                'identity' => $username
            )
        );

        // Create user
        $url = $this->_constructUrl('/tenants/users');
        $userApiResponse = $this->post($url, $params);

        // Give it site-to-site details read policy

        $accessPolicy = $this->getAccessPolicy('read', '/site-to-site');
        $this->_updateAccessPolicy($accessPolicy, $this->findNifiUser($username));

        return $userApiResponse;
    }

    /**
     * @param stdClass \Component
     * @return mixed
     */
    public function getProcessorState($processor)
    {
        $state = $this->get($processor->getUri() . "/state");
        return $state->componentState;
    }

    /**
     * @param ProcessGroup $processGroup
     * @param string[] $users
     * @param string $action
     */
    public function setProcessGroupAccessPolicy(&$processGroup, $users, $action = 'write')
    {
        $resource = "/process-groups/{$processGroup->getId()}";

        $nifiUsers = array();
        foreach ($users as $user) {
            $nifiUsers += $this->findNifiUser($user);
        }
        $accessPolicy = $this->getAccessPolicy($action, $resource);
        if (empty($accessPolicy)) {
            $this->_createAccessPolicy($resource, $action, $processGroup->getId(), $nifiUsers);
        } else {
            $this->_updateAccessPolicy($accessPolicy, $nifiUsers);
        }
    }

    /**
     * @param ProcessGroup $processGroup
     * @param string[] $users
     * @param string $action
     */
    public function setProcessGroupDataAccessPolicy(&$processGroup, $users, $action = 'write')
    {
        $resource = "/data/process-groups/{$processGroup->getId()}";
        $nifiUsers = array();
        foreach ($users as $user) {
            $nifiUsers += $this->findNifiUser($user);
        }
        $accessPolicy = $this->getAccessPolicy($action, $resource);
        if (empty($accessPolicy)) {
            $this->_createAccessPolicy($resource, $action, $processGroup->getId(), $nifiUsers);
        } else {
            $this->_updateAccessPolicy($accessPolicy, $nifiUsers);
        }
    }

    /**
     * @param Component|ProcessGroup $component
     * @param string $state
     * @throws \Exception
     */
    public function setComponentState(&$component, $state = "RUNNING")
    {
        $revision = $this->get($component->getUri());
        $params = array(
            'component' => array(
                'id' => $component->getId(),
                'state' => $state
            ),
            'revision' => $revision->revision
        );
        switch ($component->type) {
            case Component::$TYPE_INPUT_PORT:
                $url = $component->getUri();
                break;
            case Component::$TYPE_OUTPUT_PORT:
                $url = $component->getUri();
                break;
            case Component::$TYPE_PROCESSOR:
                $url = $component->getUri();
                break;
            case Component::$TYPE_PROCESS_GROUP:
                $url = $this->_constructUrl("/flow/process-groups/{$component->getId()}");
                $params = array(
                    'id' => $component->getId(),
                    'state' => $state
                );
                break;
            default:
                throw new \Exception('Wrong component type provided');
        }

        $this->put($url, $params);
    }

    /**
     * @param Component $group
     * @param bool $transmitting
     * @param Component[] $inputPorts
     * @param Component[] $outputPorts
     */
    public function setRemoteProcessGroupTransmittingState(&$group, $transmitting = true, $inputPorts = array(), $outputPorts = array())
    {
        if (count($inputPorts) > 0 || count($outputPorts) > 0) {
            $inputPortsParams = array();
            foreach ($inputPorts as $port) {
                array_push($inputPortsParams, array(
                    'id' => $port->getId(),
                    'transmitting' => $transmitting
                ));
            }
            $outputPortsParams = array();
            foreach ($outputPorts as $port) {
                array_push($outputPortsParams, array(
                    'id' => $port->getId(),
                    'transmitting' => $transmitting
                ));
            }
            $params = array(
                'revision' => $this->getRevision($group->getUri()),
                'component' => array(
                    'id' => $group->getId(),
                    'contents' => array(
                        'inputPorts' => $inputPortsParams,
                        'outputPorts' => $outputPortsParams
                    )
                )
            );
        } else {
            $params = array(
                'revision' => $this->getRevision($group->getUri()),
                'component' => array(
                    'id' => $group->getId(),
                    'transmitting' => $transmitting
                )
            );
        }

        $this->put($group->getUri(), $params);
    }

    /**
     * @param Connection $connection
     * @return \stdClass
     */
    public function dropQueueContent(&$connection)
    {
        $url = $this->_constructUrl("/flowfile-queues/{$connection->getId()}/drop-requests");
        $dropReply = $this->post($url);
        return $dropReply->dropRequest;
    }

    /**
     * @param Connection $connection
     * @param string $dropRequestId
     * @return \stdClass
     */
    public function getDropRequestStatus(&$connection, $dropRequestId)
    {
        $url = $this->_constructUrl("/flowfile-queues/{$connection->getId()}/drop-requests/$dropRequestId");
        $dropReply = $this->get($url);
        return $dropReply->dropRequest;
    }

    /**
     *
     * @param ProcessGroup $processGroup
     * @return ProcessGroup
     */
    public function getProcessGroup(&$processGroup)
    {
        $processGroup->setApiData($this->get($this->_constructUrl("/process-groups/{$processGroup->getId()}")));
        return $processGroup;
    }

    /**
     * @param ProcessGroup $processGroup
     * @return \stdClass
     */
    public function getProcessGroupFlow(&$processGroup)
    {
        $url = $this->_constructUrl("/flow/process-groups/{$processGroup->getId()}");
        $processGroupFlowReply = $this->get($url);
        $processGroup->setApiData($processGroupFlowReply->processGroupFlow);
        return $processGroupFlowReply;
    }

    /**
     * @return \stdClass
     */
    public function getControllerConfig()
    {
        return $this->get($this->_constructUrl("/controller/config"));
    }

    public function updateControllerConfig($maxTimerDrivenThreadCount = 0, $maxEventDrivenThreadCount = 0)
    {
        $params = array(
            'revision' => $this->getControllerConfig()->revision,
            'component' => array(
                'maxTimerDrivenThreadCount' => $maxTimerDrivenThreadCount,
                'maxEventDrivenThreadCount' => $maxEventDrivenThreadCount
            )
        );
        return $this->put($this->_constructUrl("/controller/config"), $params);
    }

    /**
     * @param Component|ProcessGroup|Connection $component
     * @return \stdClass
     */
    public function deleteComponent(&$component)
    {
        $revision = $this->getRevision($component->getUri());
        return $this->delete("{$component->getUri()}?version={$revision->version}");
    }

    /**
     * @param Component $component - Processor component
     * @param int $tasksAmount - Amount of concurrent tasks for processor
     * @throws \Exception
     */
    public function setProcessorConcurrentTasks(&$component, $tasksAmount) {
        $params = array(
            'component' => array(
                'config' => array(
                    'concurrentlySchedulableTaskCount' => $tasksAmount
                )
            )
        );

        $this->_updateComponent($component, $params);
    }

    /**
     * Update connection's backpressure settings
     * $param Component $component - Connection component
     * $param int $backPressureObjectThreshold - Number of objects in queue threshold to apply backpressure when reached
     * $param String $backPressureDataSizeThreshold - Size of data in queue threshold to apply backpressure when reached
     */
    public function updateConnectionConfig(&$component, $backPressureObjectThreshold = 10000, $backPressureDataSizeThreshold = '1 GB') {
        $params = array(
            'component' => array(
                'backPressureObjectThreshold' => $backPressureObjectThreshold,
                'backPressureDataSizeThreshold' => $backPressureDataSizeThreshold
            )
        );
        $this->_updateComponent($component, $params);
    }

    /**
     * @param string $name - Name of the registry client
     * @param string $uri - URI of the registry client
     * @param string $description - Description of the registry client
     */
    public function addRegistryClient($name, $uri, $description)
    {
        $regUrl = $this->_constructUrl("/controller/registry-clients");
        $response = $this->get($regUrl);
        $existingRegistry = null;
        $revision = array(
            'version' => '0'
        );
        if ($response) {
            foreach ($response->registries as $registry) {
                if ($registry->component->name == $name) {
                    $existingRegistry = $registry;
                    $revision['version'] = $registry->revision->version;
                    break;
                }
            } 
        }
        $this->log->LogDebug("Registries get response: " . json_encode($response));
        $this->log->LogDebug("Existing registry: " . json_encode($existingRegistry));
        $params = array(
            'revision' => $revision,
            'component' => array(
                'name' => $name,
                'uri' => $uri,
                'description' => $description
            )
        );
        if ($existingRegistry) {
            $params['component']['id'] = $existingRegistry->id;
            $this->put($existingRegistry->uri, $params);
        } else {
            $this->post($regUrl, $params);
        }
    }
}
