<?php
namespace Yeebase\Supervisor\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Yeebase.Gurumanage".    *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;

/**
 * Service that encapsulates interaction with the Supervisor API
 *
 * @Flow\Scope("singleton")
 */
class SupervisorService {

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * @var integer
	 */
	protected $port;

	/**
	 * @var integer
	 */
	protected $timeout;

	/**
	 * supervisor socket
	 *
	 * @var resource
	 */
	protected $socket;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
		$this->host = $this->settings['host'];
		$this->port = $this->settings['port'];
		$this->timeout = $this->settings['timeout'];
		$this->username = $this->settings['username'];
		$this->password = $this->settings['password'];
	}

	/**
	 * Return the address (host and port) of the supervisor daemon
	 *
	 * @return string
	 */
	public function getAddress() {
		return $this->host.':'.$this->port;
	}

	/**
	 * Return the identification string of the supervisor daemon
	 *
	 * @return string
	 */
	public function getIdentification() {
		return $this->sendRequest('supervisor', 'getIdentification');
	}

	/**
	 * Return the supervisor api version
	 *
	 * @return string
	 */
	public function getApiVersion() {
		/**
		 * Check supervisor version and use deprecated getVersion method for supervisor versions below 3.0a1
		 */
		$supervisorVersion = $this->getVersion();
		if (intval(substr($supervisorVersion,0,1)) <= 2){
			return $this->sendRequest('supervisor', 'getVersion');
		}
		return $this->sendRequest('supervisor', 'getAPIVersion');
	}

	/**
	 * Return the version of the supervisor daemon
	 *
	 * @return string
	 */
	public function getVersion() {
		return $this->sendRequest('supervisor', 'getSupervisorVersion');
	}

	/**
	 * Return the state of the supervisor daemon
	 *
	 * @return string
	 */
	public function getState() {
		return $this->sendRequest('supervisor', 'getState');
	}

	/**
	 * Start all processes
	 *
	 * @param boolean $wait wait until all processes has been started or let them start in the background
	 * @return boolean returns TRUE or throws an exception
	 */
	public function startAllProcesses($wait = TRUE) {
		return $this->sendRequest('supervisor', 'startAllProcesses', array($wait));
	}

	/**
	 * Start a specific process
	 *
	 * @param string $processname The unique name of the process or string including the group - f.e. "processgroup:processname"
	 * @param boolean $wait wait until the process has been started or let it start in the background
	 * @return boolean returns TRUE or throws an exception
	 */
	public function startProcess($processname, $wait = TRUE) {
		return $this->sendRequest('supervisor', 'startProcess', array($processname, $wait));
	}

	/**
	 * Stop all processes
	 *
	 * @param boolean $wait wait until stopped or let them stop in the background
	 * @return boolean returns TRUE or throws an exception
	 */
	public function stopAllProcesses($wait = TRUE) {
		return $this->sendRequest('supervisor', 'stopAllProcesses', array($wait));
	}

	/**
	 * Stop a specific process
	 *
	 * @param string $processname The unique name of the process or string including the group - f.e. "processgroup:processname"
	 * @param boolean $wait wait until the process has been stopped or let it stop in the background
	 * @return boolean returns TRUE or throws an exception
	 */
	public function stopProcess($processname, $wait = TRUE) {
		return $this->sendRequest('supervisor', 'stopProcess', array($processname, $wait));
	}

	/**
	 * Get info about a process by process name or ‘group:name’
	 *
	 * @param string $processName The unique name of the process
	 * @return array All details of the process
	 */
	public function getProcessInfoByName($processName) {
		return $this->sendRequest('supervisor', 'getProcessInfo', array($processName));
	}

	/**
	 * Get details about all processes
	 *
	 * @return array All details of all processes
	 */
	public function getAllProcessInfo() {
		return $this->sendRequest('supervisor', 'getAllProcessInfo');
	}

	/**
	 * Read data from the supervisor logfile starting at offset
	 *
	 * @param integer $offset Offset to start reading from
	 * @param integer $length Number of bytes to read from the log
	 * @return string the log data
	 */
	public function readLogfile($offset, $length = 0) {
		return $this->sendRequest('supervisor', 'readLog', array($offset, $length));
	}

	/**
	 * Clear the supervisor logfile
	 *
	 * @return boolean Result always returns true unless error
	 */
	public function clearLogfile() {
		return $this->sendRequest('supervisor', 'clearLog');
	}

	/**
	 * Reads the stdout logfile of a process
	 *
	 * @param string $processName The name of the process or ‘group:name’
	 * @param int $length Number of bytes to read from the log.
	 * @param int $offset Offset to start reading from.
	 * @return string
	 */
	public function tailProcessLogfile($processName, $length, $offset = 0) {
		return $this->sendRequest('supervisor', 'tailProcessStdoutLog', array($processName, $offset, $length));
	}

	/**
	 * Reads the error logfile of a process
	 *
	 * @param string $processName The name of the process or ‘group:name’
	 * @param int $length Number of bytes to read from the log.
	 * @param int $offset Offset to start reading from.
	 * @return string
	 */
	public function tailProcessErrorLogfile($processName, $length, $offset = 0) {
		return $this->sendRequest('supervisor', 'tailProcessStderrLog', array($processName, $offset, $length));
	}

	/**
	 * Clear the logfile of a process
	 *
	 * @param string $processName The name of the process
	 * @return boolean Result always returns true unless error
	 */
	public function clearProcessLogfiles($processName) {
		return $this->sendRequest('supervisor', 'clearProcessLogs', array($processName));
	}

	/**
	 * Clear all process logfiles at once
	 *
	 * @return boolean Result always returns true unless error
	 */
	public function clearAllProcessLogfiles() {
		return $this->sendRequest('supervisor', 'clearAllProcessLogs');
	}

	/**
	 * Send a request to the supervisor backend and return the result
	 *
	 * @param string $namespace The namespace of the request
	 * @param string $method The method in the namespace
	 * @param array $arguments Optional arguments
	 * @return string
	 * @throws Exception
	 */
	protected function sendRequest($namespace, $method, array $arguments = array()) {
		$this->sendApiRequest($namespace, $method, $arguments);

		$httpResponse = NULL;
		$headerLength = NULL;
		$contentLength = NULL;
		do {
			$httpResponse .= fread($this->getSocket(), 8192);
			if ($headerLength === NULL) {
				$headerLength = strpos($httpResponse, "\r\n\r\n");
			}

			if ($contentLength == NULL && $headerLength !== NULL) {
				$header = substr($httpResponse, 0, $headerLength);
				$headerLines = explode("\r\n", $header);
				$headerFields = array_slice($headerLines, 1); //Remove http status code

				foreach ($headerFields as $headerField) {
					list($headerName, $headerValue) = explode(': ', $headerField);
					if ($headerName == 'Content-Length') {
						$contentLength = $headerValue;
					}
				}
				if ($contentLength === NULL) {
					throw new Exception('No Content-Length field found in the HTTP header.', 1400166915);
				}
			}
			$bodyStartPosition = $headerLength + strlen("\r\n\r\n");
			$bodyLength = strlen($httpResponse) - $bodyStartPosition;
		} while ($bodyLength < $contentLength);

		// Parse response.
		$body = substr($httpResponse, $bodyStartPosition);
		$response = \xmlrpc_decode($body, 'utf-8');

		if (is_array($response) && \xmlrpc_is_fault($response)) {
			throw new Exception($response['faultString'], $response['faultCode']);
		}
		return $response;
	}

	/**
	 * Open a socket to the configured backend or return it if it has been opened before
	 *
	 * @return resource
	 * @throws Exception
	 */
	protected function getSocket() {
		//check if socket already exists
		if ($this->socket) {
			return $this->socket;
		}

		//open socket
		$errorCode = NULL;
		$errorMessage = NULL;
		$this->socket = @fsockopen(
			$this->host,
			$this->port,
			$errorCode,
			$errorMessage,
			$this->timeout
		);

		if (!$this->socket) {
			throw new Exception(sprintf('Cannot open supervisor host at %s. Error %d: "%s"', $this->host, $errorCode, $errorMessage), 1400166897);
		}
		stream_set_timeout($this->socket, $this->timeout);
		return $this->socket;
	}

	/**
	 * Send a request to the supervisor XML-RPC API
	 *
	 * @param string $namespace namespace of the request
	 * @param string $method request method of the namespace
	 * @param mixed $arguments request arguments
	 */
	protected function sendApiRequest($namespace, $method, $arguments) {
		//check if username and password are defined
		$authorizationHeader = NULL;
		if ($this->username && $this->password) {
			$authorizationHeader = "\r\nAuthorization: Basic " . base64_encode($this->username . ':' . $this->password);
		}

		//build xmlrpc request
		$xml_rpc = \xmlrpc_encode_request($namespace . '.' . $method, $arguments, array('encoding' => 'utf-8'));
		$httpRequest = "POST /RPC2 HTTP/1.1\r\n" .
			"Content-Length: " . strlen($xml_rpc) .
			$authorizationHeader .
			"\r\n\r\n" .
			$xml_rpc;

		//write request to the socket
		fwrite($this->getSocket(), $httpRequest);
	}
}

?>