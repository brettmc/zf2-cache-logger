<?php

namespace DeakinTest\Zend\Cache\Storage\Plugin;

use Deakin\Zend\Cache\Storage\Plugin\EventLoggerOptions;
use Psr\Log\NullLogger;

class EventLoggerOptionsTest extends \PHPUnit_Framework_TestCase
{

	public function testBadMethod()
	{
		$this->setExpectedException('Zend\Stdlib\Exception\BadMethodCallException');
		$new = new EventLoggerOptions(array(
		    'no_such_option' => 'test'
		));
	}

	public function testOptions()
	{
		$logger = new NullLogger();
		$activeListeners = 1;
		$new = new EventLoggerOptions(array(
			'logger' => $logger,
			'activeListeners' => $activeListeners,
		));
		$this->assertSame($logger, $new->getLogger());
		$this->assertSame($activeListeners, $new->getActiveListeners());
	}

}

