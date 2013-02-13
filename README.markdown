SmfEvents is central event dispatching system for Nette framework. It is strongly based on symfony 2 event dispatcher

#Installation

Easiest way to install the addon is via `composer`. Add this to your `composer.json`:

    "trunda/smf-events": "1.0.*@dev",

and then register the extension by adding this line to your `bootstrap.php` before container creation:

```php
Smf\Events\Config\Extension::register($configurator);
```

## The dispatcher

The event dispatcher is central container holding all listeners and also dispatches all events to their listeners.

```php
use Smf\Events\EventDispatcher;

$dispatcher = new EventDispatcher();
```

## Adding listeners

If you want to listen particular event, you need to register the listener. It is done by `addListener` method,
which takes event name as the first argument and valid PHP callback as the second one.

```php
$listener = new SomeClass();
// this class has method onFooAction which takes Event instance as argument

$dispatcher->addEventListener('fooAction', array($listener, 'onFooAction'));
```

Method `addListener` can take 3 arguments

1. The event name that the listener wants to listen to
2. A valid [PHP callback](http://www.php.net/manual/en/language.pseudo-types.php#language.types.callback) that will be called whenever the event is triggered
3. A priority which is gives you availability to sort event dispatching (event with higher priority will be dispatched earlier)