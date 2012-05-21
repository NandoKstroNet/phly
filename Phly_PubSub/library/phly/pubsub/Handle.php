<?php
/**
 * Phly - PHp LibrarY
 * 
 * @category  Phly
 * @package   Phly_PubSub
 * @copyright Copyright (C) 2008 - Present, Matthew Weier O'Phinney
 * @author    Matthew Weier O'Phinney <mweierophinney@gmail.com> 
 * @license   New BSD {@link http://mwop.net/license}
 */

namespace phly\pubsub;

use ReflectionClass;

/**
 * Handle: unique handle subscribed to a given topic
 * 
 * @package Phly_PubSub
 * @version $Id: $
 */
class Handle
{
    /**
     * @var string|array PHP callback to invoke
     */
    protected $_callback;

    /**
     * @var string Topic to which this handle is subscribed
     */
    protected $_topic;

    /**
     * Constructor
     * 
     * @param  string $topic Topic to which handle is subscribed
     * @param  string|object $context Function name, class name, or object instance
     * @param  string|null $handler Method name, if $context is a class or object
     * @return void
     */
    public function __construct($topic, $context, $handler = null)
    {
        $this->_topic = $topic;

        if (null === $handler) {
            $this->_callback = $context;
        } else {
            $this->_callback = array($context, $handler);
        }
    }

    /**
     * Get topic to which handle is subscribed
     * 
     * @return string
     */
    public function getTopic()
    {
        return $this->_topic;
    }

    /**
     * Retrieve registered callback
     * 
     * @return Callback
     */
    public function getCallback()
    {
        if (is_string($this->_callback) && class_exists($this->_callback)) {
            $this->_callback = new $this->_callback;
        } elseif (is_array($this->_callback)) {
            $class = $this->_callback[0];
            if (is_string($class) && class_exists($class)) {
                $method = $this->_callback[1];
                if (!is_callable(array($class, $method))) {
                    $this->_callback = array(new $class(), $method);
                }
            }
        }
        return $this->_callback;
    }

    /**
     * Invoke handler
     * 
     * @param  array $args Arguments to pass to callback
     * @return mixed
     */
    public function call(array $args = array())
    {
        $callback = $this->getCallback();
        if (is_array($callback)) {
            if (is_object($callback[0])
                && !is_callable($callback)
                && !method_exists($callback[0], '__call')
            ) {
                throw new InvalidCallbackException();
            } elseif (!is_callable($callback)) {
                throw new InvalidCallbackException();
            }
            if (is_string($callback[0])) {
                $r = new ReflectionClass($callback[0]);
                $m = $r->getMethod($callback[1]);
                if (!$m->isStatic()) {
                    // Create instance
                    $class = $callback[0];
                    $callback[0] = new $class();
                }
            }
        } elseif (!is_callable($callback)) {
            throw new InvalidCallbackException();
        }
        return call_user_func_array($callback, $args);
    }
}
