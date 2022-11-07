<?php

define('ERR_CONNECTION',	1001);
define('ERR_READ',		1002);
define('ERR_PARSE',		1003);
define('ERR_DISCOVERY',	1004);
define('ERR_VERSION',	1005);

define('API_AUTH',		'SYNO.API.Auth');
define('API_AUTH_VER',	6);
define('API_TASK',		'SYNO.DownloadStation.Task');
define('API_TASK_VER',	3);

class DownloadStationAPI {
	protected $sid;
	protected $user;
	protected $password;
	protected $dsmUri;

	protected $apiEndpoints = array();

	protected $verbose = false;

	public $error = null;

	public function __construct($user, $password, $dsmUri) {
		$this->user = $user;
		$this->password = $password;
		$this->dsmUri = $dsmUri;
	}

	public function __destruct() {
	}

	protected function log($msg) {
		if ($verbose) echo("$msg\n");
	}

	protected function setError($errNo) {
		$this->log("ERROR $errNo");
		$this->error = $errNo;
		return false;
	}


	protected function callAPI($method, $target, $params) {

		$http = array( 'method'	=> $method);
		$opts = array( 'http' => $http );

		if (count($params) > 0) {
			if ( $method === 'GET' ) {
				$target .= '?' . http_build_query($params);
			} else if ($method === 'POST') {
				$http['header'] = "Content-type: application/x-www-form-urlencoded\r\n";
				$http['content'] = http_build_query($params);
			}
		}	

		$context = stream_context_create($opts);

		if ($fp = fopen($this->dsmUri . $target, 'r', false, $context)) {
			$body = stream_get_contents($fp);
			if ($body === false) return $this->setError(ERR_READ);

			$result = json_decode($body);
			if ($result === null) return $this->setError(ERR_PARSE);

			fclose($fp);

			if ($result->success) {
				return $result->data;
			}
			return $this->setError($result->error->code);
		} 

		return $this->setError(ERR_CONNECTION); 

	}

	public function getApiInfo($api) {
		return $this->callAPI('GET', 'query.cgi', array(
			'api'		=> 'SYNO.API.Info',
			'version'	=> 1,
			'method'	=> 'query',
			'query'		=> $api
		));
	}

	public function getEndpoint($api, $version) {

		if (! array_key_exists($api, $this->apiEndpoints)) {
			$res = $this->getApiInfo($api);
			if (!$res) return $res;

			$endpointData = $res->$api;
			if (!$endpointData) return $this->setError(ERR_DISCOVERY);

			$this->apiEndpoints[$api] = $endpointData;
			$this->log("Found endpoint " . $this->apiEndpoints[$api]->path . " for api $api");
		}

		if ($version >= $this->apiEndpoints[$api]->minVersion && $version <= $this->apiEndpoints[$api]->maxVersion) return $this->apiEndpoints[$api]->path;

		return $this->setError(ERR_VERSION);
	}

	public function login() {
		if ($endpoint = $this->getEndpoint(API_AUTH, API_AUTH_VER)) {
			if ($auth = $this->callAPI('GET', $endpoint, array(
				'api'		=> API_AUTH,
				'version'	=> API_AUTH_VER,
				'method'	=> 'login',
				'account'	=> $this->user,
				'passwd'	=> $this->password,
			))) {
				$this->sid = $auth->sid;

				$this->log("Authentication successful. Storing sid.");
				return $this->sid;
			}
		}
		return false;
	}

	public function getSid() {
		if ($this->sid) return $this->sid;
		return $this->login();
	}

	public function getTaskList() {
		if ($sid = $this->getSid()) {
			if ($endpoint = $this->getEndpoint(API_TASK, API_TASK_VER)) {
				return $this->callAPI('GET', $endpoint, array(
						'api'		=> API_TASK,
						'version'	=> API_TASK_VER,
						'method'	=> 'list',
						'additional'	=> 'detail,transfer,file',
						'_sid'		=> $sid
				));
				
			}
		}
		return false;
	}

	public function createTasks($uri) {
		if ($sid = $this->getSid()) {
			if ($endpoint = $this->getEndpoint(API_TASK, API_TASK_VER)) {
				return $this->callAPI('GET', $endpoint, array(
						'api'		=> API_TASK,
						'version'	=> API_TASK_VER,
						'method'	=> 'create',
						'uri'		=> $uri,
						'_sid'		=> $sid
				));
				
			}
		}
		return false;
	}

	public function pauseTasks($id) {
		if ($sid = $this->getSid()) {
			if ($endpoint = $this->getEndpoint(API_TASK, API_TASK_VER)) {
				return $this->callAPI('GET', $endpoint, array(
						'api'		=> API_TASK,
						'version'	=> API_TASK_VER,
						'method'	=> 'pause',
						'id'		=> $id,
						'_sid'		=> $sid
				));
				
			}
		}
		return false;
	}

	public function resumeTasks($id) {
		if ($sid = $this->getSid()) {
			if ($endpoint = $this->getEndpoint(API_TASK, API_TASK_VER)) {
				return $this->callAPI('GET', $endpoint, array(
						'api'		=> API_TASK,
						'version'	=> API_TASK_VER,
						'method'	=> 'resume',
						'id'		=> $id,
						'_sid'		=> $sid
				));
				
			}
		}
		return false;
	}

	public function deleteTasks($id) {
		if ($sid = $this->getSid()) {
			if ($endpoint = $this->getEndpoint(API_TASK, API_TASK_VER)) {
				return $this->callAPI('GET', $endpoint, array(
						'api'		=> API_TASK,
						'version'	=> API_TASK_VER,
						'method'	=> 'delete',
						'id'		=> $id,
						'_sid'		=> $sid
				));
				
			}
		}
		return false;
	}
}

