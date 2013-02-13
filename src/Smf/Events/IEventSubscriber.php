<?php
/**
 * Created by JetBrains PhpStorm.
 * User: trunda
 * Date: 30.01.13
 * Time: 16:29
 * To change this template use File | Settings | File Templates.
 */

namespace Smf\Events;


/**
 * Event subscriber is way, how to register more listeners (usually those that have something in common)
 * in once
 */
interface IEventSubscriber
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents();
}