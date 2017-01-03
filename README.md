Yeebase.Supervisor
=========

With this Flow package you can control and monitor your supervisor daemon http://supervisord.org/api.html and all configured processes from your Flow application. You can use it to control workers that compute job queue tickets in a complex MessageQueue scenario.

Yeebase.Supervisor is an excellent addition to Flow's Flowpack.JobQueue.Common package and some MessageQueue backend like Flowpack.JobQueue.Beanstalkd:

You would like to defer jobs in a message queue and do things asynchronous within your Flow application?

An example MessageQueue stack with Flow:

1. **Supervisor Daemon** - monitors and controls your job queue workers (they are doing the hard work)
http://supervisord.org/
2. **Beanstalkd Message Queue**- manages what jobs to give to the workers (stupid, fast)
http://kr.github.io/beanstalkd/
3. **Flowpack.JobQueue.Common** - Flow package for putting jobs/functions into an asyncronous pipeline (using a simple @Job\Defer annotation)
https://github.com/Flowpack/jobqueue-common
4. **Flowpack.JobQueue.Beanstalkd** - The job queue implementation for Beanstalkd backends
https://github.com/Flowpack/jobqueue-beanstalkd
5. **Yeebase.Supervisor** client package - monitor and control all configured supervisor processes
https://github.com/yeebase/Yeebase.Supervisor

Installation & configuration
------------

Just add "yeebase/supervisor" as dependency to your composer.json and run a "composer update" in your root folder. You will also have to install the xml-rpc php extension (would be nice to remove this dependency in some future versions).

Configure the supervisor connection in your Settings.yaml:

```yaml
Yeebase:
  Supervisor:
    host: 'unix:///var/run/supervisor.sock'
    port: -1
    timeout: 30
    username:
    password:
```

Command line tool
------------

The Yeebase.Supervisor package comes with a simple Flow command controller:

1) Test the connection to supervisor and get some details
```
./flow supervisor:status
```
This should output something like this:
```
Successfully connected to supervisor service:
Address: unix:///var/run/supervisor.sock:-1
Identification: supervisor
Version: 3.0 
Api-Version: 3.0
State: RUNNING
```

2) Show all configured processes and their details
```
./flow supervisor:processes status all
```

Just type *./flow help supervisor* to get an overview of all available commands or *./flow help supervisor:processes* (f.e.) on how to use a specific command.

Using the SupervisorService in your own classes
------------

The main part of the Yeebase.Supervisor package is a the supervisor client class named "SupervisorService". In Flow you can just inject this class to your custom controllers via the following php code:

```php
...
use Yeebase\Supervisor\Service\SupervisorService;

/**
 * My funky class that shows details about configured supervisor processes
 */
class MyFunkyController extends AbstractBaseController {

	/**
	 * @Flow\Inject
	 * @var SupervisorService
	 */
	protected $supervisorService;
...
```

If the SupervisorService has been injected you can use it in your class without initializing it manually - something like this:

```php
  function showVersionAction() {
    $this->view->assign('version', $this->supervisorService->getVersion();  
  }
```





