<?php

error_reporting(E_ERROR);
ini_set('display_errors', 1);

require_once __DIR__.'/../common.php';
require_once __DIR__.'/../KLogger.php';
require_once __DIR__.'/../utils.php';
require_once __DIR__.'/nifiComponents/Component.php';
require_once __DIR__.'/nifiComponents/ProcessGroup.php';
require_once __DIR__.'/api/curl.php';
require_once __DIR__.'/api/nifiApi.php';

use api\nifiApi;
use nifiComponents\ProcessGroup;
use nifiComponents\Component;
use nifiComponents\Connection;

// initialize flags
$getrootpgs = $prettyprint = $getprocstates = $add_registry_client = false;
$get_maxtimer_threadcount = $set_maxtimer_threadcount = $getPgFlow = false;

// initialize short options
$shortopts  = "";
$shortopts .= "R";	// get root flow
$shortopts .= "r";	// get process groups under root
$shortopts .= "s";	// get processor states
$shortopts .= "t";	// get processor states
$shortopts .= "T";	// set processor states
$shortopts .= "a";  // add registry client
$shortopts .= "n:"; // name of the registry client
$shortopts .= "l:"; // url of the registry client
$shortopts .= "d:"; // description of the registry client
$shortopts .= "p";	// pretty print


// initialize long options
$longopts = array(
	"getPgFlow",      // get process group flow
	"pgId:",          // process group name
	"getrootpgs",     // get process groups under root
	"getprocstates",  // get processor states
	"get_maxtimer_threadcount",  // get nifi's max timer-driven thread count
	"set_maxtimer_threadcount:", // set nifi's max timer-driven thread count
	"add_registry_client", // add registry client
	"registry_name:", // registry name
	"registry_url:",  // registry uri
	"registry_desc:", // registry description
	"prettyprint"     // pretty print
);

// parse options
$options = getopt($shortopts, $longopts);
//print_r($options);
if (isset($options['r']) || isset($options['getrootpgs'])) {
	$getrootpgs = true;
}
if (isset($options['s']) || isset($options['getprocstates'])) {
	$getprocstates = true;
}
if (isset($options['p']) || isset($options['prettyprint'])) {
	$prettyprint = true;
}
if (isset($options['a']) || isset($options['add_registry_client'])) {
	$add_registry_client = true;
}
if (isset($options['t']) || isset($options['get_maxtimer_threadcount'])) {
	$get_maxtimer_threadcount = true;
}
if (isset($options['T']) || isset($options['set_maxtimer_threadcount'])) {
	$set_maxtimer_threadcount = true;
}
if (isset($options['getPgFlow'])) {
	$getPgFlow = true;
}

// retrieve nifi configs
$result = sudo_execute("/var/www/softnas/scripts/getnificonf.sh");
if($result['rv'] !== 0) {
	echo "Failed in getting nifi config";
	exit (1);
}
$values = array();
foreach ($result['output_arr'] as $configValue) {
    $configValueArray = explode('=', $configValue);
    $values[$configValueArray[0]] = $configValueArray[1];
}

// initialize logging
$basepath = dirname(dirname(__DIR__));
$log = new KLogger("/var/www/softnas/logs/nifi/nificmd.log", KLogger::MAINT);

// NiFiCmd class contains functions that interact with NiFi via REST APIs
class NiFiCmd {

	public function __construct($nifiApi) {
        $this->nifiApi = $nifiApi;
    }

    public function getProcessGroupFlow($pgId) {
    	$pg = new ProcessGroup('', 0, 0, '', $pgId);
  		$this->nifiApi->getProcessGroupFlow($pg);
			$apiData = $pg->getApiData();
			if (isset($apiData)) {
				return $apiData;
			}
			return array();
	  }

    public function getRootProcessGroups() {
    	$apiData = $this->getProcessGroupFlow('root');
    	if (isset($apiData) && isset($apiData->flow) && isset($apiData->flow->processGroups)) {
    		return $apiData->flow->processGroups;
    	}
    	return array();
    }

    public function getProcessorStates($processGroup = null, &$state) {
			if ($processGroup == null) {
				$processGroup = new ProcessGroup('root', 0, 0, '', 'root');
			}
			$this->nifiApi->getProcessGroupFlow($processGroup);
			$processGroupApiData = $processGroup->getApiData();
			foreach ($processGroupApiData->flow->processors as $childProcessor) {
				$newChildProcessor = new Component($childProcessor->component->name,
			                                   $childProcessor->component->position->x,
			                                   $childProcessor->component->position->y,
			                                   $childProcessor->uri,
			                                   $childProcessor->id,
			                                   Component::$TYPE_PROCESSOR);
				$result = $this->nifiApi->getProcessorState($newChildProcessor);
				$state[$childProcessor->component->name] = $result;
			}
			foreach ($processGroupApiData->flow->processGroups as $childProcessGroup) {
				$newChildProcessGroup = new ProcessGroup($childProcessGroup->component->name,
			                                         $childProcessGroup->component->position->x,
			                                         $childProcessGroup->component->position->y,
			                                         $childProcessGroup->uri,
			                                         $childProcessGroup->id,
			                                         Component::$TYPE_PROCESS_GROUP);
				$this->getProcessorStates($newChildProcessGroup, $state);
			}
    }

    public function addregistryclient($name, $uri, $description) {
    	$this->nifiApi->addregistryclient($name, $uri, $description);
    }

		public function getTimerThreadCount() {
			$sourceControllerConfiguration = $this->nifiApi->getControllerConfig();
			return $sourceControllerConfiguration->component->maxTimerDrivenThreadCount;
		}

		public function setTimerThreadCount($threadCount) {
			$sourceControllerConfiguration = $this->nifiApi->getControllerConfig();
			$this->nifiApi->updateControllerConfig($threadCount, $sourceControllerConfiguration->component->maxEventDrivenThreadCount);
		}
}

try {
	// initialize nifi api and cmd
	$nifiApi = new nifiApi(array(
		'host' => '127.0.0.1',
		'port' => $values['nifi.web.https.port'],
		'username' => '',
		'password' => '',
		'log' => $log,
		'sslCert' => '/var/www/softnas/keys/nifi/localhost/buurst.pem',
		'sslCaCert' => '/var/www/softnas/keys/nifi/localhost/server.crt'
	));
	$nifiCmd = new NiFiCmd($nifiApi);

	// decide and execute requests
	if ($getrootpgs) {
		$result = $nifiCmd->getRootProcessGroups();
		if ($prettyprint) {
			echo format_json(json_encode($result));
		} else {
			echo (json_encode($result));
		}
	} elseif ($getprocstates) {
		$result = array();
		$nifiCmd->getProcessorStates(null, $result);
		if ($prettyprint) {
			echo format_json(json_encode($result));
		} else {
			echo (json_encode($result));
		}
	} elseif ($add_registry_client) {
		$name = '';
		if(isset($options['n'])) {
			$name = $options['n'];
		} elseif (isset($options['registry_name'])) {
			$name = $options['registry_name'];
		}
		$uri = '';
		if(isset($options['l'])) {
			$uri = $options['l'];
		} elseif (isset($options['registry_url'])) {
			$uri = $options['registry_url'];
		}
		$desc = '';
		if(isset($options['d'])) {
			$desc = $options['d'];
		} elseif (isset($options['registry_desc'])) {
			$desc = $options['registry_desc'];
		}

		$log->LogInfo("Add Registry Client: Name={$name}, URI={$uri}, Description={$desc}");
		$nifiCmd->addregistryclient($name, $uri, $desc);
	} elseif ($get_maxtimer_threadcount) {
		$threadcount = $nifiCmd->getTimerThreadCount();
		echo($threadcount);
	} elseif ($set_maxtimer_threadcount) {
		$threadcount = false;
		if(isset($options['T'])) {
			$threadcount = $options['T'];
		} elseif (isset($options['set_maxtimer_threadcount'])) {
			$threadcount = $options['set_maxtimer_threadcount'];
		}
		if (intval($threadcount) > 0) {
			$log->LogInfo("Setting max timer thread: $threadcount");
			$nifiCmd->setTimerThreadCount($threadcount);
			
		}
	} elseif ($getPgFlow) {
		$pgId = false;
		if (isset($options['pgId'])) {
			$pgId = $options['pgId'];
		}
		$flow = array();
		if ($pgId) {
			$flow = $nifiCmd->getProcessGroupFlow($pgId);
		}
		if ($prettyprint) {
			echo format_json(json_encode($flow));
		} else {
			echo (json_encode($flow));
		}
	}
} catch (Exception $e) 
{
	$log->LogError($e->getMessage());
	echo ($e->getMessage());
	exit (254);
}

?>
