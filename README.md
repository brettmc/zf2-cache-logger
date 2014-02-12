# zf2-cache-logger
[![Build Status](https://secure.travis-ci.org/brettmc/zf2-cache-logger.png)](http://travis-ci.org/brettmc/zf2-cache-logger)

A plugin for Zend\Cache\Storage which logs events (eg read/write/remove/exception) to a PSR-3 compliant logger. This can be useful for development, or to get an idea of your cache effectiveness if your cache adapter doesn't natively provide such information.

## Options
EventLoggerOptions accepts the following:
### logger
A PSR-3 compliant logger (eg monolog, Zend\Log)
### activeListeners (optional, default all)
Classes of listeners which are active and will respond to events.
Available classes are:

* EventLogger::LISTENERS_ALL (the default)
* EventLogger::LISTENERS_READ
* EventLogger::LISTENERS_WRITE
* EventLogger::LISTENERS_REMOVE
* EventLogger::LISTENERS_EXCEPTION

These options can be bitwise-OR'd to activate multiple classes of listeners.
NB that messages are logged at INFO level, except for exceptions which are logged at ERROR level.

Example usage
-------------
```
use Deakin\Zend\Cache\Storage\Plugin\EventLoggerOptions;
use Deakin\Zend\Cache\Storage\Plugin\EventLogger;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Zend\Cache\Storage\Adapter\Filesystem;

$logger = new Logger('cache-logger');
$log->pushHandler(new StreamHandler('path/to/your.log', Logger::INFO));
/* log write operations and exceptions only */
$options = new EventLoggerOptions(
    array(
        'logger' => $logger,
        'activeListeners' => EventLogger::LISTENERS_EXCEPTION | EventLogger::LISTENERS_WRITE,
    )
);

$eventLogger = new EventLogger();
$eventLogger->setOptions($options);

$cache = new Filesystem();
$cache->addPlugin($eventLogger);
$cache->addItem('key', 'value');
```
