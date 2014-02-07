<?php
/**
 * @copyright	Copyright (c) 2013 Deakin University (http://www.deakin.edu.au)
 * @author		Brett mcBride <brett@deakin.edu.au>
 * @license		BSD
 */

namespace Deakin\Zend\Cache\Storage\Plugin;

use Traversable;
use Zend\Cache\Storage\Plugin\PluginOptions;
use Deakin\Zend\Cache\Storage\Plugin\EventLogger;
use Psr\Log\LoggerInterface;

/**
 * These are options specific to the EventLogger adapter
 */
class EventLoggerOptions extends PluginOptions
{
	/**
	 * 
	 * @var Psr\Log\LoggerInterface
	 */
	protected $logger;
	
	protected $activeListeners;
	
    /**
     * Constructor
     *
     * @param  array|Traversable|null $options
     * @return EventLoggerOptions
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($options = null)
    {
        $this->activeListeners = EventLogger::LISTENERS_ALL;
    	parent::__construct($options);
    }

    public function setLogger(LoggerInterface &$logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger()
    {
    	return $this->logger;
    }
    
    public function setActiveListeners($activeListeners)
    {
    	$this->activeListeners = $activeListeners;
    }
    
    public function getActiveListeners()
    {
    	return $this->activeListeners;
    }
}
