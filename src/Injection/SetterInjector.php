<?php namespace Lit\Air\Injection;

use Lit\Air\Factory;
use Lit\Air\Psr\ContainerException;
use ReflectionMethod;

class SetterInjector implements InjectorInterface
{
    protected $prefixes = ['inject'];

    public function inject(Factory $factory, $obj, array $extra = [])
    {
        $class = new \ReflectionClass($obj);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$this->shouldBeInjected($method)) {
                continue;
            }
            $parameter = $method->getParameters()[0];
            $paramClassName = null;
            $keys = [$parameter->name];
            $paramClass = $parameter->getClass();
            if (!empty($paramClass)) {
                $keys[] = $paramClassName = $paramClass->name;
            }

            try {
                $value = $factory->produceDependency($class->name, $keys, $paramClassName, $extra);
                $method->invoke($obj, $value);
            } catch (ContainerException $e) {
                //ignore
            }
        }
    }

    public function isTarget($obj)
    {
        $class = get_class($obj);
        return defined("$class::SETTER_INJECTOR") && $class::SETTER_INJECTOR === static::class;
    }

    /**
     *
     * @param array $prefixes
     * @return $this
     */
    public function setPrefixes(array $prefixes)
    {
        $this->prefixes = $prefixes;
        return $this;
    }

    protected function shouldBeInjected(ReflectionMethod $method)
    {
        if ($method->isStatic() || $method->isAbstract()) {
            return false;
        }
        $parameter = $method->getParameters();
        if (count($parameter) !== 1 || $parameter[0]->isOptional()
        ) {
            return false;
        }

        foreach ($this->prefixes as $prefix) {
            if (substr($method->name, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }
        return false;
    }
}
