<?php
namespace Smf\Events;


use Nette\Object;

class Event extends Object
{
    /** @var string */
    private $name;
    /** @var IEventDispatcher */
    private $dispatcher;
    /** @var boolean */
    private $propagationStopped = false;

    /**
     * Stores dispatcher that dispatches this event
     *
     * @param IEventDispatcher $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Returns dispatcher that dispatches this event
     *
     * @return IEventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }


    /**
     * Stores event's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the event's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;
    }

    /**
     * Returns whether further event listeners should be triggered.
     *
     * @see Event::stopPropagation
     * @return Boolean Whether propagation was already stopped for this event.
     *
     * @api
     */
    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }
}