<?php
namespace Smf\Events\Config;

use Nette\Caching\Storages\DevNullStorage;
use Nette\DI\ContainerBuilder;
use Nette\InvalidArgumentException;
use Nette\Loaders\RobotLoader;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nette\Utils\LimitedScope;

class AnnotationScanner extends Object
{
    const ANNOTATION = 'eventListener';

    /** @var RobotLoader */
    private $robotLoader;
    /** @var array */
    private $scanDirs = array();

    /**
     * @param $dir
     * @throws \Nette\InvalidArgumentException
     */
    public function addScanDir($dir)
    {
        if (($path = realpath($dir)) === false) {
            throw new InvalidArgumentException("Dir '$dir' does not exist.");
        }
        $this->scanDirs[] = $dir;
        $this->scanDirs = array_unique($this->scanDirs);
    }

    /**
     * @return array
     */
    public function getAnnotatedListeners()
    {
        $listeners = array();
        $robotLoader = $this->getRobotLoader();
        $robotLoader->rebuild();
        foreach ($robotLoader->getIndexedClasses() as $class => $file) {
            $reflection = new ClassType($class);
            foreach ($reflection->getMethods() as $method) {
                if ($method->isStatic() && $method->hasAnnotation(self::ANNOTATION)) {
                    $annotation = $method->getAnnotation(self::ANNOTATION);
                    $listeners[] = array(
                        'listener' => array($class, $method->getName()),
                        'name' => $annotation['name'],
                        'priority' => isset($annotation['priority']) ? $annotation['priority'] : 0
                    );
                }
            }
        }
        return $listeners;
    }

    /**
     * @return \Nette\Loaders\RobotLoader
     */
    protected function getRobotLoader()
    {
        if ($this->robotLoader === null) {
            $this->robotLoader = new RobotLoader();
            $this->robotLoader->setCacheStorage(new DevNullStorage);
        }

        foreach ($this->scanDirs as $scanDir) {
            $this->robotLoader->addDirectory($scanDir);
        }
        return $this->robotLoader;
    }
}