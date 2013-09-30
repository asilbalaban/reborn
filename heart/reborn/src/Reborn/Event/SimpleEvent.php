<?php

namespace Reborn\Event;

/**
 * Simple Event Driver Class for Reborn
 *
 * @package Reborn\Event
 * @author Myanmar Links Professional Web Development Team
 **/
class SimpleEvent implements \Reborn\Event\EventInterface
{

    /**
     * Variable for events list
     *
     * @var array
     */
    protected $events = array();

    /**
     * Default Construct method for Event Class
     *
     * @param array $events Events array list
     */
    public function __construct($events = array())
    {
        if (! empty($events)) {
            foreach ($events as $k => $e) {
                $this->on($e['name'], $e['callback']);
            }
        }
    }

    /**
     * Add(Register) the Event.
     * Note: Use prefix to prevent event name conflict.
     *
     * @param string $name Event name (eg: blog_post_create)
     * @param string $callback Callback function name.
     * @return void
     */
    public function on($name, $callback)
    {
        $this->events[$name][]['callback'] = $callback;
    }

    /**
     * Check the given event name is have or not
     *
     * @param string $name Event name
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->events[$name]);
    }

    /**
     * Remove(UnRegister) the given event name.
     *
     * @param string $name Name of the event
     * @return void
     */
    public function off($name)
    {
        if (isset($this->events[$name])) {
            unset($this->events[$name]);
        }
    }

    /**
     * Clear the all event from app.
     *
     * @return void
     **/
    public function clear()
    {
        unset($this->events);
    }

    /**
     * Call the event first register
     *
     * @param string $name Name of event
     * @param array $params Paramater array for callback event (optional)
     * @return mixed
     **/
    public function first($name, $params = array())
    {
        $params = (array)$params;

        if (isset($this->events[$name])) {
            return call_user_func_array($this->events[$name][0]['callback'], $params);
        }

        return null;
    }

    /**
     * Call(Trigger) the event.
     *
     * @param string $name Name of event
     * @param array $params Paramater array for callback event (optional)
     * @return mixed
     */
    public function call($name, $params = array())
    {
        $result = array();

        $params = (array)$params;

        if (isset($this->events[$name])) {
            foreach ($this->events[$name] as $call) {
                if (is_callable($call['callback'])) {
                    $result[] = call_user_func_array($call['callback'], $params);
                }
            }
        }

        return $result;
    }

} // END class SimpleEvent
