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
## Output
Example logged output of a fairly standard missed read, successful write, successful read:
```
[2014-02-07 15:00:11] cache-logger.INFO: read miss {"operation":"read","success":"false","key":"F1B349595E0B2322E043BCE1440AB5A7","event":"getItem.post","adapter":"Zend\\Cache\\Storage\\Adapter\\Filesystem","namespace":"my.namespace"} []
[2014-02-07 15:00:11] cache-logger.INFO: write success {"operation":"write","key":"F1B349595E0B2322E043BCE1440AB5A7","success":"true","event":"setItem.post","adapter":"Zend\\Cache\\Storage\\Adapter\\Filesystem","namespace":"my.namespace","ttl":300} []
[2014-02-07 15:00:12] cache-logger.INFO: read hit {"operation":"read","success":"true","key":"F1B349595E0B2322E043BCE1440AB5A7","event":"getItem.post","adapter":"Zend\\Cache\\Storage\\Adapter\\Filesystem","namespace":"my.namespace"} []
```

## Notes
* Messages are logged at INFO level, the exception being exceptions which are logged at ERROR level.
* Depending on your usage, logging READ operations can log a lot of messages
