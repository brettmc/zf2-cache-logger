<?php

namespace DeakinTest\Zend\Cache\Storage\Plugin;

use Deakin\Zend\Cache\Storage\Plugin\EventLogger;
use Deakin\Zend\Cache\Storage\Plugin\EventLoggerOptions;

use Zend\Cache\Storage\PostEvent;
use Zend\Cache\Storage\ExceptionEvent;
use ArrayObject;
use Zend\Cache\Storage\Adapter\AdapterOptions;
use Psr\Log\NullLogger;

class EventLoggerTest extends \PHPUnit_Framework_TestCase
{
	protected $_adapter;
	protected $_options;
	protected $_plugin;
	protected $_logger;
	
	public function setUp()
	{
		$this->_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');
		$adapterOptions = new AdapterOptions();
		$adapterOptions->setNameSpace('DeakinTestNamespace');
		$adapterOptions->setTtl(10);
		$this->_adapter->setOptions($adapterOptions);
		
		$this->_plugin = new EventLogger();
	}
	
	public function testPluginDefaultOptions()
	{
		$plugin = new EventLogger();
		$this->assertInstanceOf('Deakin\Zend\Cache\Storage\Plugin\EventLoggerOptions', $plugin->getOptions());
		$this->assertEquals(EventLogger::LISTENERS_ALL, $plugin->getOptions()->getActiveListeners());
		$this->assertNull($plugin->getOptions()->getLogger());
	}
	
	/**
	 * @dataProvider addPluginProvider
	 * @param int $activeListeners
	 * @param array $expectedListeners
	 */
	public function testAddPlugin($activeListeners, $expectedListeners)
	{
		$this->_plugin->setOptions(
			new EventLoggerOptions(
				array(
					'logger' => new NullLogger(),
					'activeListeners' => $activeListeners
				)
			)
		);
		
		$this->_adapter->addPlugin($this->_plugin, 100);
	
		foreach ($expectedListeners as $eventName => $expectedCallbackMethod) {
			$listeners = $this->_adapter->getEventManager()->getListeners($eventName);
	
			// event should attached only once
			$this->assertSame(1, $listeners->count());
	
			// check expected callback method
			$cb = $listeners->top()->getCallback();
			$this->assertArrayHasKey(0, $cb);
			$this->assertSame($this->_plugin, $cb[0]);
			$this->assertArrayHasKey(1, $cb);
			$this->assertSame($expectedCallbackMethod, $cb[1]);
	
			// check expected priority
			$meta = $listeners->top()->getMetadata();
			$this->assertArrayHasKey('priority', $meta);
			if (substr($eventName, -4) == '.pre') {
				$this->assertSame(100, $meta['priority']);
			} else {
				$this->assertSame(-100, $meta['priority']);
			}
		}
	}
	
	public function addPluginProvider()
	{
		return array(
			array(
				EventLogger::LISTENERS_ALL,
				array(
					'getItem.post' => 'onReadItemPost',
					'getItems.post' => 'onReadItemsPost',
					'getItem.exception' => 'onException',
					'getItems.exception' => 'onException',
						
					'setItem.post' => 'onWriteItemPost',
					'setItems.post' => 'onWriteItemsPost',
					'setItem.exception' => 'onException',
					'setItems.exception' => 'onException',
						
					'addItem.post' => 'onWriteItemPost',
					'addItems.post' => 'onWriteItemsPost',
					'addItem.exception' => 'onException',
					'addItems.exception' => 'onException',
							
					'replaceItem.post' => 'onWriteItemPost',
					'replaceItems.post' => 'onWriteItemsPost',
					'replaceItem.exception' => 'onException',
					'replaceItems.exception' => 'onException',
							
					'checkAndSetItem.post' => 'onWriteItemPost',
					'checkAndSetItem.exception' => 'onException',
							
					'touchItem.post' => 'onWriteItemPost',
					'touchItems.post' => 'onWriteItemsPost',
					'touchItem.exception' => 'onException',
					'touchItems.exception' => 'onException',
						
					'removeItem.post' => 'onRemoveItemPost',
					'removeItems.post' => 'onRemoveItemsPost',
					'removeItem.exception' => 'onException',
					'removeItems.exception' => 'onException',
				),
			),
			array(
				EventLogger::LISTENERS_EXCEPTION,
				array(
					'getItem.exception' => 'onException',
					'getItems.exception' => 'onException',
					'setItem.exception' => 'onException',
					'setItems.exception' => 'onException',
					'addItem.exception' => 'onException',
					'addItems.exception' => 'onException',
					'replaceItem.exception' => 'onException',
					'replaceItems.exception' => 'onException',
					'checkAndSetItem.exception' => 'onException',
					'touchItem.exception' => 'onException',
					'touchItems.exception' => 'onException',
					'removeItem.exception' => 'onException',
					'removeItems.exception' => 'onException',
				),
			),
			array(
				EventLogger::LISTENERS_WRITE | EventLogger::LISTENERS_REMOVE, //test that we can bit-OR multiple listeners
				array(
					'setItem.post' => 'onWriteItemPost',
					'setItems.post' => 'onWriteItemsPost',
					'addItem.post' => 'onWriteItemPost',
					'addItems.post' => 'onWriteItemsPost',
					'replaceItem.post' => 'onWriteItemPost',
					'replaceItems.post' => 'onWriteItemsPost',
					'checkAndSetItem.post' => 'onWriteItemPost',
					'touchItem.post' => 'onWriteItemPost',
					'touchItems.post' => 'onWriteItemsPost',
					'removeItem.post' => 'onRemoveItemPost',
					'removeItems.post' => 'onRemoveItemsPost',
				),
			),
			array(
				EventLogger::LISTENERS_READ,
				array(
					'getItem.post' => 'onReadItemPost',
					'getItems.post' => 'onReadItemsPost',
				),
			),
		);
	}
	
	public function testRemovePlugin()
	{
		$this->_adapter->addPlugin($this->_plugin);
		$this->_adapter->removePlugin($this->_plugin);
	
		// no events should be attached
		$this->assertEquals(0, count($this->_adapter->getEventManager()->getEvents()));
	}
	
	/**
	 * Tests that when the plugin is attached to an adapter, an event is successfully logged to the logger.
	 */
	public function testSomethingIsLoggedWhenPluginAttachedToAdapter()
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		$mock->expects($this->once())
			->method('info')
			->with($this->stringContains('read miss'));
		
		$pluginOptions = new EventLoggerOptions(array('logger' => $mock));
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
		
		$this->_adapter->getItem('a_key');
	}
	
	/**
	 * @dataProvider getItemPostProvider
	 */
	public function testGetItemPost($key, $result, $success, $expectedLogContent)
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		foreach($expectedLogContent as $at => $msg)
		{
			$mock->expects($this->at($at))
				->method('info')
				->with($this->stringContains($msg));
		}
		
		$pluginOptions = new EventLoggerOptions();
		//$pluginOptions->setLogger($this->_logger);
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
		
		$event = new PostEvent('getItem.post', $this->_adapter, new ArrayObject(array(
			'key' => $key,
			'success' => $success
		)), $result);
		$plugin->onReadItemPost($event);
		$this->assertSame($result, $event->getResult());
	}
	
	public function getItemPostProvider()
	{
		return array(
			array(
				'key1', 'value1', true, array('hit')
			),
			array(
				'non_existent_key', null, false, array('miss')
			)
		);
	}
	
	/**
	 * @dataProvider getItemsPostProvider
	 */
	public function testGetItemsPost($keys, $resultsFound, $expectedLogContent)
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		foreach($expectedLogContent as $at => $msg)
		{
			$mock->expects($this->at($at))
				->method('info')
				->with($this->stringContains($msg));
		}
	
		$pluginOptions = new EventLoggerOptions();
		//$pluginOptions->setLogger($this->_logger);
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
	
		$event = new PostEvent('getItem.post', $this->_adapter, new ArrayObject(array(
				'keys' => $keys
		)), $resultsFound);
		$plugin->onReadItemsPost($event);
		$this->assertSame($resultsFound, $event->getResult());
	}
	
	public function getItemsPostProvider()
	{
		return array(
			array(
				array('key1', 'key2', 'key3'), array(), array('miss', 'miss', 'miss')
			),
			array(
				array('key1', 'key2', 'key3'), array('key1' => 'data1'), array('hit', 'miss', 'miss')
			),
			array(
				array('key1', 'key2', 'key3'), array('key1' => 'data1', 'key2' => 'data2'), array('hit', 'hit', 'miss')
			),
			array(
				array('key1', 'key2', 'key3'), array('key1' => 'data1', 'key2' => 'data2', 'key3' => 'data3'), array('hit', 'hit', 'hit')
			)
		);
	}
	
	/**
	 * @dataProvider removeItemPostProvider
	 */
	public function testRemoveItemPost($key, $result, $expectedLogContent)
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		foreach($expectedLogContent as $at => $msg)
		{
			$mock->expects($this->at($at))
				->method('info')
				->with($this->stringContains($msg));
		}
	
		$pluginOptions = new EventLoggerOptions();
		//$pluginOptions->setLogger($this->_logger);
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
	
		$event = new PostEvent('removeItem.post', $this->_adapter, new ArrayObject(array(
				'key' => $key
		)), $result);
		$plugin->onRemoveItemPost($event);
		$this->assertSame($result, $event->getResult());
	}
	
	public function removeItemPostProvider()
	{
		return array(
			array(
				'key1', true, array('success')
			),
			array(
				'some_crazy_key', false, array('fail') //is this a "key not found" or storage adapter failure?
			)
		);
	}
	
	/**
	 * @dataProvider removeItemsPostProvider
	 */
	public function testRemoveItemsPost($keys, $keysNotRemoved, $expectedLogContent)
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		foreach($expectedLogContent as $at => $msg)
		{
			$mock->expects($this->at($at))
			->method('info')
			->with($this->stringContains($msg));
		}
	
		$pluginOptions = new EventLoggerOptions();
		//$pluginOptions->setLogger($this->_logger);
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
	
		$event = new PostEvent('removeItems.post', $this->_adapter, new ArrayObject(array('keys' => $keys)), $keysNotRemoved);
		$plugin->onRemoveItemsPost($event);
		$this->assertSame($keysNotRemoved, $event->getResult());
	}
	
	public function removeItemsPostProvider()
	{
		return array(
			array(	
				array('keyToRemove1', 'keyToRemove2', 'keyToRemove3'), array(), array('success', 'success', 'success')
			),
			array(
				array('keyToRemove1', 'keyToRemove2', 'keyToRemove3'), array('keyToRemove1'), array('fail', 'success', 'success')
			),
			array(
				array('keyToRemove1', 'keyToRemove2', 'keyToRemove3'), array('keyToRemove1', 'keyToRemove2'), array('fail', 'fail', 'success')
			),
			array(
				array('keyToRemove1', 'keyToRemove2', 'keyToRemove3'), array('keyToRemove1', 'keyToRemove2', 'keyToRemove3'), array('fail', 'fail', 'fail')
			)
		);
	}
	
	/**
	 * @dataProvider writeItemPostProvider
	 */
	public function testWriteItemPost($key, $value, $result, $expectedLogContent)
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		foreach($expectedLogContent as $at => $msg)
		{
			$mock->expects($this->at($at))
				->method('info')
				->with($this->stringContains($msg));
		}
	
		$pluginOptions = new EventLoggerOptions();
		//$pluginOptions->setLogger($this->_logger);
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
	
		$event = new PostEvent('writeItem.post', $this->_adapter, new ArrayObject(array(
				'key' => $key,
				'value' => $value
		)), $result);
		$plugin->onWriteItemPost($event);
		$this->assertSame($result, $event->getResult());
	}
	
	public function writeItemPostProvider()
	{
		return array(
			array(
				'key1', 'data1', true, array('success')
			),
			array(
				'some_crazy_key', 'data1', false, array('fail') //is this a "key not found" or storage adapter failure?
			)
		);
	}
	
	/**
	 * @dataProvider writeItemsPostProvider
	 */
	public function testWriteItemsPost($keyValuePairs, $keysNotWritten, $expectedLogContent)
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		foreach($expectedLogContent as $at => $msg)
		{
			$mock->expects($this->at($at))
			->method('info')
			->with($this->stringContains($msg));
		}
	
		$pluginOptions = new EventLoggerOptions();
		//$pluginOptions->setLogger($this->_logger);
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
	
		$event = new PostEvent('writeItems.post', $this->_adapter, new ArrayObject($keyValuePairs), $keysNotWritten);
		$plugin->onWriteItemsPost($event);
		$this->assertSame($keysNotWritten, $event->getResult());
	}
	
	public function writeItemsPostProvider()
	{
		return array(
			array(
				array('key1' => 'data1', 'key2' => 'data2', 'key3' => 'data3'), array(), array('success', 'success', 'success')
			),
			array(
				array('key1' => 'data1', 'key2' => 'data2', 'key3' => 'data3'), array('key1'), array('fail', 'success', 'success')
			),
			array(
				array('key1' => 'data1', 'key2' => 'data2', 'key3' => 'data3'), array('key1', 'key2'), array('fail', 'fail', 'success')
			),
			array(
				array('key1' => 'data1', 'key2' => 'data2', 'key3' => 'data3'), array('key1', 'key2', 'key3'), array('fail', 'fail', 'fail')
			)
		);
	}
	
	public function testException()
	{
		$mock = $this->getMockBuilder('Psr\Log\NullLogger')->getMock();
		$mock->expects($this->once())
			->method('error')
			->with($this->stringContains('exception'));
		
		$pluginOptions = new EventLoggerOptions();
		$pluginOptions->setLogger($mock);
		$plugin = new EventLogger();
		$plugin->setOptions($pluginOptions);
		$this->_adapter->addPlugin($plugin);
		
		$testException = new \Exception('a test exception');
		$result = false;
		
		$event = new ExceptionEvent('getItem.exception', $this->_adapter, new ArrayObject(), $result, $testException);
		$plugin->onException($event);
	}
}

