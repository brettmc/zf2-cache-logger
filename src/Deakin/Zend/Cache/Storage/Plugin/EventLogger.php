<?php
/**
 * @copyright	Copyright (c) 2013 Deakin University (http://www.deakin.edu.au)
 * @author		Brett mcBride <brett@deakin.edu.au>
 * @license		BSD
 */

namespace Deakin\Zend\Cache\Storage\Plugin;

use stdClass;
use Zend\Cache\Storage\PostEvent;
use Zend\Cache\Storage\Plugin\AbstractPlugin;
use Zend\EventManager\EventManagerInterface;

class EventLogger extends AbstractPlugin
{
	/**
	 * @var array
	 */
	protected $capabilities = array();
	
	/**
	* {@inheritDoc}
	*/
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        // The higher the priority the sooner the plugin will be called on pre events
        // but the later it will be called on post events.
        $prePriority = $priority;
        $postPriority = -$priority;

        // read
        $this->listeners[] = $events->attach('getItem.post', array($this, 'onReadItemPost'), $postPriority);
        $this->listeners[] = $events->attach('getItems.post', array($this, 'onReadItemsPost'), $postPriority);

        // write
        $this->listeners[] = $events->attach('setItem.post', array($this, 'onWriteItemPost'), $postPriority);
        $this->listeners[] = $events->attach('setItems.post', array($this, 'onWriteItemsPost'), $postPriority);

        $this->listeners[] = $events->attach('addItem.post', array($this, 'onWriteItemPost'), $postPriority);
        $this->listeners[] = $events->attach('addItems.post', array($this, 'onWriteItemsPost'), $postPriority);
        
        $this->listeners[] = $events->attach('touchItem.post', array($this, 'onWriteItemPost'), $postPriority);
        $this->listeners[] = $events->attach('touchItems.post', array($this, 'onWriteItemsPost'), $postPriority);

        $this->listeners[] = $events->attach('replaceItem.post', array($this, 'onWriteItemPost'), $postPriority);
        $this->listeners[] = $events->attach('replaceItems.post', array($this, 'onWriteItemsPost'), $postPriority);

        $this->listeners[] = $events->attach('checkAndSetItem.post', array($this, 'onWriteItemPost'), $postPriority);
        
        // remove
        $this->listeners[] = $events->attach('removeItem.post', array($this, 'onRemoveItemPost'), $postPriority);
        $this->listeners[] = $events->attach('removeItems.post', array($this, 'onRemoveItemsPost'), $postPriority);
    }

	public function onReadItemPost(PostEvent $event)
	{
		$storage = $event->getStorage();
		$success = $event->getResult() === true;
		$this->getOptions()->getLogger()->info(
			sprintf('read %s', ($success === true) ? 'hit' : 'miss'),
			array(
				'class' => get_class($storage),
				'namespace' => $storage->getOptions()->getNamespace(),
				'operation' => 'read',
				'key' => $event->getParam('key'),
				'success' => $event->getResult())
		);
	}
	
	public function onReadItemsPost(PostEvent $event)
	{
		$storage = $event->getStorage();
		/* success for each key is determined by comparing input keys against output keys */
		$inKeys = $event->getParam('keys');
		$result = $event->getResult();
		foreach($inKeys as $key)
		{
			$success = array_key_exists($key, $result);
			$this->getOptions()->getLogger()->info(
				sprintf('read %s', ($success === true) ? 'hit' : 'miss'),
				array(
					'class' => get_class($storage),
					'namespace' => $storage->getOptions()->getNamespace(),
					'operation' => 'read',
					'key' => $key,
					'success' => $success)
			);
		}
	}
	
	public function onWriteItemPost(PostEvent $event)
	{
		$storage = $event->getStorage();
		$success = $event->getResult(); /* @see Zend\Cache\Storage\Adapter\AbstractAdapter::internalAddItem */
		$this->getOptions()->getLogger()->info(
			sprintf('write %s', ($success === true) ? 'success' : 'fail'),
			array(
				'class' => get_class($storage),
				'namespace' => $storage->getOptions()->getNamespace(),
				'ttl' => $storage->getOptions()->getTtl(),
				'operation' => 'read',
				'key' => $event->getParam('key'),
				'success' => $success)
		);
	}
	
	public function onWriteItemsPost(PostEvent $event)
	{
		$storage = $event->getStorage();
		$notStoredKeys = $event->getResult(); /* @see Zend\Cache\Storage\Adapter\AbstractAdapter::internalAddItems */
		foreach($event->getParams() as $key => $value)
		{
			$success = !in_array($key, $notStoredKeys);
			$this->getOptions()->getLogger()->info(
				sprintf('write %s', ($success === true) ? 'success' : 'fail'),
				array(
					'class' => get_class($storage),
					'namespace' => $storage->getOptions()->getNamespace(),
					'ttl' => $storage->getOptions()->getTtl(),
					'operation' => 'write',
					'key' => $key,
					'success' => $success)
			);
		}
	}
	
	public function onRemoveItemPost(PostEvent $event)
	{
		$storage = $event->getStorage();
		$success = $event->getResult();
		$this->getOptions()->getLogger()->info(
			sprintf('remove %s', ($success === true) ? 'success' : 'fail'),
			array(
				'class' => get_class($storage),
				'namespace' => $storage->getOptions()->getNamespace(),
				'operation' => 'remove',
				'key' => $event->getParam('key'),
				'success' => $success)
		);
	}
	
	public function onRemoveItemsPost(PostEvent $event)
	{
		$storage = $event->getStorage();
		$notRemovedKeys = $event->getResult(); /* @see Zend\Cache\Storage\Adapter\AbstractAdapter::internalRemoveItems */
		foreach($event->getParam('keys') as $key)
		{
			$success = !in_array($key, $notRemovedKeys);
			$this->getOptions()->getLogger()->info(
				sprintf('remove %s', ($success === true) ? 'success' : 'fail'),
				array(
					'class' => get_class($storage),
					'namespace' => $storage->getOptions()->getNamespace(),
					'operation' => 'remove',
					'key' => $key,
					'success' => $success)
			);
		}
	}
}
