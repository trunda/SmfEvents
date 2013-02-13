SmfEvents is central event dispatching system for Nette framework. It is strongly based on symfony 2 event dispatcher

#Installation

Easiest way to install the addon is via `composer`. Add this to your `composer.json`:

    "trunda/smf-events": "1.0.*@dev",

and then register the extension by adding this lines to your `bootstrap.php` before container creation:

```php
Smf\Events\Config\Extension::register($configurator);
```
