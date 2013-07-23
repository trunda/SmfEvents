<?php
namespace Smf\Events\Config;

use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\DI\Container;
use Nette\DI\ServiceDefinition;
use Nette\Loaders\RobotLoader;
use Smf\Events\Event;
use Smf\Events\IEventDispatcher;

if (!class_exists('Nette\DI\CompilerExtension')) {
    class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
    class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
    class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
    unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
    class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

class Extension extends Nette\DI\CompilerExtension
{
    const DEFAULT_EXTENSION_NAME = 'eventDispatcher',
        LISTENER_TAG_NAME = 'eventDispatcherListener',
        SUBSCRIBER_TAG_NAME = 'eventDispatcherSubscriber';

    public $defaults = array(
        'annotations' => array(
            'enabled' => true,
            'scanDirs' => array('%appDir%'),
            'autogenerate' => null,
        ),
    );

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $options = $this->getConfig($this->defaults);

        if ($options['annotations']['autogenerate'] === null) {
            $options['annotations']['autogenerate'] = !$builder->parameters['productionMode'];
        }

        $dispatcher = $builder->addDefinition($this->prefix('eventDispatcher'))
            ->setClass('Smf\Events\EventDispatcher')
            ->addSetup(get_called_class() . '::setupEventListeners', array('@self', '@container'))
            ->addSetup(get_called_class() . '::setupEventSubscribers', array('@self', '@container'));

        if ($options['annotations']['enabled']) {
            if ($options['annotations']['autogenerate']) {
                $dispatcher->addSetup(get_called_class() . '::addAnnotatedEvents',
                    array('@self', '@cacheStorage', $options['annotations']['scanDirs']));
            } else {
                $this->registerAnnotatedListeners($dispatcher, $options['annotations']['scanDirs']);
            }
        }

    }

    /**
     * Fixed annotated listeners registration
     * @param \Nette\DI\ServiceDefinition $definition
     * @param array $scanDirs
     */
    protected function registerAnnotatedListeners(ServiceDefinition $definition, array $scanDirs)
    {
        $scanner = new AnnotationScanner();
        foreach ($scanDirs as $dir) {
            $scanner->addScanDir($dir);
        }
        foreach ($scanner->getAnnotatedListeners() as $listener) {
            $definition->addSetup('addListener', array($listener['name'], $listener['listener'], $listener['priority']));
        }
    }

    /**
     * @param \Smf\Events\IEventDispatcher $dispatcher
     * @param \Nette\DI\Container $container
     */
    public static function setupEventListeners(IEventDispatcher $dispatcher, Container $container)
    {
        foreach ($container->findByTag(self::LISTENER_TAG_NAME) as $name => $value) {
            self::addEventListener($dispatcher, $container,
                $name, $value['name'], $value['method'], isset($value['priority']) ? $value['priority'] : 0);
        }
    }

    /**
     * Adds lazy event listener from container
     *
     * @param \Smf\Events\IEventDispatcher $dispatcher
     * @param \Nette\DI\Container $container
     * @param $service
     * @param $name
     * @param $method
     * @param $priority
     */
    public static function addEventListener(IEventDispatcher $dispatcher,Container $container,
                                            $service, $name, $method, $priority)
    {
        $dispatcher->addListener(
            $name,
            function(Event $event) use ($container, $service, $method, $priority) {
                return call_user_func(array($container->getService($service), $method), $event);
            },
            $priority
        );
    }

    /**
     * @param \Smf\Events\IEventDispatcher $dispatcher
     * @param \Nette\DI\Container $container
     */
    public static function setupEventSubscribers(IEventDispatcher $dispatcher, Container $container)
    {
        foreach ($container->findByTag(self::SUBSCRIBER_TAG_NAME) as $service => $value) {

            if (!is_string($value) || !class_exists($value)) {
                $dispatcher->addEventSubscriber($container->getService($service));
            } else {
                // Lazy
                $class = $value;
                foreach (call_user_func(array($class, 'getSubscribedEvents')) as $name  => $params) {
                    if (is_string($params)) {
                        self::addEventListener($dispatcher, $container, $service, $name, $params, 0);
                    } elseif (is_string($params[0])) {
                        self::addEventListener($dispatcher, $container, $service, $name, $params[0],
                            isset($params[1]) ? $params[1] : 0);
                    } else {
                        foreach ($params as $listener) {
                            self::addEventListener($dispatcher, $container, $service, $name, $listener[0],
                                isset($listener[1]) ? $listener[1] : 0);
                        }
                    }
                }

            }
        }
    }

    /**
     * Cached annotated listeners registration (use during development phase)
     * @param \Smf\Events\IEventDispatcher $eventDispatcher
     * @param \Nette\Caching\IStorage $cacheStorage
     * @param array $scanDirs
     */
    public static function addAnnotatedEvents(IEventDispatcher $eventDispatcher, IStorage $cacheStorage, array $scanDirs)
    {
        $cache = new Cache($cacheStorage, 'Smf.EventDispatcher');
        if (null === ($register = $cache->load('annotatedListeners'))) {
            $scanner = new AnnotationScanner();
            foreach ($scanDirs as $dir) {
                $scanner->addScanDir($dir);
            }
            $register = $scanner->getAnnotatedListeners();
            $cache->save('annotatedListeners', $register, array(
               Cache::CALLBACKS => array(
                   array(array(__CLASS__, 'checkDir'), $scanDirs, self::dirsmtine($scanDirs))
               )
            ));
        }
        foreach ($register as $item) {
            $eventDispatcher->addListener($item['name'], $item['listener'], $item['priority']);
        }
    }

    /**
     * Cache validator
     * @param $dir
     * @param $time
     * @return bool
     */
    public static function checkDir($dir, $time)
    {
        return self::dirsmtine($dir) == $time;
    }

    /**
     * Helper to count modification time for given directories
     * @param $dirs
     * @return int
     */
    public static function dirsmtine($dirs)
    {
        $mtime = -1;
        if (!is_array($dirs)) {
            $dirs = (array) $dirs;
        }

        foreach ($dirs as $dir) {
            $iterator = new \RecursiveDirectoryIterator($dir);
            foreach (new \RecursiveIteratorIterator($iterator) as $fileinfo) {
                if ($fileinfo->isFile()) {
                    if ($fileinfo->getMTime() > $mtime) {
                        $mtime = $fileinfo->getMTime();
                    }
                }
            }
        }
        return $mtime;
    }

    /**
     * Register extension to compiler.
     *
     * @param \Nette\Config\Configurator
     * @param string
     */
    public static function register(Configurator $configurator, $name = self::DEFAULT_EXTENSION_NAME)
    {
        $class = get_called_class();
        $configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) use ($class, $name) {
            $compiler->addExtension($name, new $class);
        };
    }
}