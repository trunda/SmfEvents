<?php
namespace Smf\Events;

use Nette\Object;

class EventDispatcher extends Object implements IEventDispatcher
{

    /** @var array */
    private $listeners = array();
    /** @var array */
    private $sorted = array();

    /**
     * Triggers all listeners listening specific action
     *
     * @param  string $name  The name of the event to dispatch. The name of
     *                       the event is the name of the method that is
     *                       invoked on listeners.
     * @param  Event  $event The event to pass to the event handlers/listeners.
     *                       If not supplied, an empty Event instance is created.
     * @return Event
     */
    public function dispatchEvent($name, Event $event = null)
    {
        if (is_null($event)) {
            $event = new Event();
        }

        $event->setDispatcher($this);
        $event->setName($name);

        if (!isset($this->listeners[$name])) {
            return $event;
        }

        $this->doDispatch($this->getListeners($name), $event);
        return $event;
    }

    /**
     * Adds an event listener that listens on the specified events
     *
     * @param string   $name     The event to listen to
     * @param callable $listener The listener
     * @param int      $priority The higher priority, the earlier an event listener
     *                           will be triggered
     */
    public function addListener($name, $listener, $priority = 0)
    {
        $this->listeners[$name][$priority][] = $listener;
        unset($this->sorted[$name]);
    }

    /**
     * Removes event listeners from specific events
     *
     * @param string|array $name      The event(s) to remove a listener from
     * @param callable     $listener  The listener to remove
     */
    public function removeListener($name, $listener)
    {
        if (!isset($this->listeners[$name])) {
            return;
        }
        foreach ($this->listeners[$name] as $priority => $listeners) {
            if (false !== ($key = array_search($listener, $listeners))) {
                unset($this->listeners[$name][$priority][$key], $this->sorted[$name]);
            }
        }
    }

    /**
     * Returns array of all listeners of specific event or all listeners
     *
     * @param  string $name The name of the event
     * @return array
     */
    public function getListeners($name = null)
    {
        if (!is_null($name)) {
            if (!isset($this->sorted[$name])) {
                $this->sortListeners($name);
            }
            return $this->sorted[$name];
        }

        foreach (array_keys($this->listeners) as $name) {
            if (!isset($this->sorted[$name])) {
                $this->sortListeners($name);
            }
        }
        return $this->sorted;

    }

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param  string $name The name of the event
     * @return boolean
     */
    public function hasListeners($name)
    {
        return count($this->getListeners($name)) > 0;
    }

    /**
     * Adds event listeners of given subscriber
     *
     * @param IEventSubscriber $subscriber
     */
    public function addEventSubscriber(IEventSubscriber $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $name => $params) {
            if (is_string($params)) {
                $this->addListener($name, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($name, array($subscriber, $params[0]), isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($name, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    /**
     * Removes event listeners of given subscriber
     *
     * @param IEventSubscriber $subscriber
     */
    public function removeEventSubscriber(IEventSubscriber $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, array($subscriber, $listener[0]));
                }
            } else {
                $this->removeListener($eventName, array($subscriber, is_string($params) ? $params : $params[0]));
            }
        }
    }

    /**
     * Sorts the listeners of the event by priority
     * @param $name Name of the event
     */
    private function sortListeners($name)
    {
        $this->sorted[$name] = array();

        if (isset($this->listeners[$name])) {
            krsort($this->listeners[$name]);
            $this->sorted[$name] = call_user_func_array('array_merge', $this->listeners[$name]);
        }
    }

    /**
     * Triggers the listener of an event
     *
     * This method can be overwritten to add functionality that is executed for
     * each listener
     *
     * @param array $listeners
     * @param Event $event
     */
    protected function doDispatch(array $listeners, Event $event)
    {
        foreach ($listeners as $listener) {
            call_user_func($listener, $event);
            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }
}