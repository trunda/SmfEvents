<?php

namespace Smf\Events;

/**
 * Interface for event dispatcher
 */
interface IEventDispatcher
{
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
    public function dispatchEvent($name, Event $event = null);

    /**
     * Adds an event listener that listens on the specified events
     *
     * @param string   $name     The event to listen to
     * @param callable $listener The listener
     * @param int      $priority The higher priority, the earlier an event listener
     *                           will be triggered
     */
    public function addListener($name, $listener, $priority = 0);

    /**
     * Removes event listeners from specific events
     *
     * @param string|array $name      The event(s) to remove a listener from
     * @param callable     $listener  The listener to remove
     */
    public function removeListener($name, $listener);

    /**
     * Returns array of all listeners of specific event or all listeners
     *
     * @param  string $name The name of the event
     * @return array
     */
    public function getListeners($name = null);

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param  string $name The name of the event
     * @return boolean
     */
    public function hasListeners($name);

    /**
     * Adds event listeners of given subscriber
     *
     * @param IEventSubscriber $subscriber
     */
    public function addEventSubscriber(IEventSubscriber $subscriber);

    /**
     * Removes event listeners of given subscriber
     *
     * @param IEventSubscriber $subscriber
     */
    public function removeEventSubscriber(IEventSubscriber $subscriber);
}