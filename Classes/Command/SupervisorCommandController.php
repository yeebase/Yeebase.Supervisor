<?php
namespace Yeebase\Supervisor\Command;

/*                                                                        *
 * This script belongs to the Flow package "Yeebase.Supervisor".          *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Log\SystemLoggerInterface;
use Yeebase\Supervisor\Service\SupervisorService;

/**
 * Controller for the Setup Commands
 */
class SupervisorCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var SupervisorService
     */
    protected $supervisorService;

    /**
     * Test the supervisor connection
     *
     * This allows to connect to supervisor daemon and test the connection
     *
     * @return void
     */
    public function statusCommand()
    {
        try {
            $state = $this->supervisorService->getState();
            $this->outputLine('Successfully connected to supervisor service:');
            $this->outputLine('Address: %s', array($this->supervisorService->getAddress()));
            $this->outputLine('Identification: %s', array($this->supervisorService->getIdentification()));
            $this->outputLine('Version: %s ', array($this->supervisorService->getVersion()));
            $this->outputLine('Api-Version: %s', array($this->supervisorService->getApiVersion()));
            $this->outputLine('State: %s', array($state['statename']));
        } catch (\Exception $exception) {
            $this->outputLine('EXCEPTION: "%s"', array($exception->getMessage()));
            $this->systemLogger->logException($exception);
        }
    }

    /**
     * Start or stop a specific supervisor process or all processes
     *
     * This allows you to start or stop all processes or a specific process defined by its unique name.
     * You can also use the full name including the process group f.e. "testgroup:process"
     *
     * @param string $method The method to apply on the process (start/stop/status/readLog/readErrorLog/clearLogs)
     * @param string $processname The name of the process or 'all' for all processes
     * @return void
     */
    public function processesCommand($method, $processName)
    {
        try {
            switch ($method) {
                case 'start':
                    if ($processName === 'all') {
                        $this->supervisorService->startAllProcesses();
                        $this->outputLine('Successfully started all processes.');
                    } else {
                        $this->supervisorService->startProcess($processName);
                        $this->outputLine('Successfully startet process(es) "%s"', array($processName));
                    }
                    break;
                case 'stop':
                    if ($processName === 'all') {
                        $this->supervisorService->stopAllProcesses();
                        $this->outputLine('Successfully stopped all processes.');
                    } else {
                        $this->supervisorService->stopProcess($processName);
                        $this->outputLine('Successfully stopped process(es) "%s"', array($processName));
                    }
                    break;
                case 'status':
                    if ($processName === 'all') {
                        $processes = $this->supervisorService->getAllProcessInfo();
                    } else {
                        $processes = array($this->supervisorService->getProcessInfoByName($processName));
                    }
                    foreach ($processes as $process) {
                        $this->outputLine('%s:%s, %s, %s', array($process['group'], $process['name'], $process['statename'], $process['description']));
                    }
                    break;
                case 'readLog':
                    $logfileData = $this->supervisorService->tailProcessLogfile($processName, 1000);
                    $this->outputLine($logfileData[0]);
                    break;
                case 'readErrorLog':
                    $logfileData = $this->supervisorService->tailProcessErrorLogfile($processName, 1000);
                    $this->outputLine($logfileData[0]);
                    break;
                case 'clearLogs':
                    if ($processName === 'all') {
                        $this->supervisorService->clearAllProcessLogfiles();
                    }
                    $this->supervisorService->clearProcessLogfiles($processName);
                    break;
                default:
                    $this->outputLine('Error: Given method is unknown. Use "start", "stop", "status", "readLog", "readErrorLog" oder "clearLogs".');
                    break;
            }
        } catch (\Exception $exception) {
            $this->outputLine('EXCEPTION: "%s"', array($exception->getMessage()));
            $this->systemLogger->logException($exception);
        }
    }
}
