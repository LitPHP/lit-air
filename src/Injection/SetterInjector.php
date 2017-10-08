<?php namespace Lit\Air\Injection;

use Lit\Air\Factory;
use ReflectionMethod;

class SetterInjector implements InjectorInterface
{
    protected $prefix = ['set', 'inject'];

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

            $value = $factory->produceDependency($class->name, $keys, $paramClassName, $extra);
            $method->invoke($obj, $value);
        }
    }

    public function isTarget($obj)
    {
        return $obj instanceof SetterInjectionInterface;
    }

    protected function shouldBeInjected(ReflectionMethod $method)
    {
        if ($method->isStatic() || $method->isAbstract()) {
            return false;
        }
        $parameter = $method->getParameters();
        if (count($parameter) !== 1) {
            return false;
        }
        $parameter = $parameter[0];
        if ($parameter->isOptional() || $parameter->allowsNull()) {
            return false;
        }

        foreach ($this->prefix as $prefix) {
            if (substr($method->name, 0, strlen($prefix)) === $prefix) {
                return true;
            }
        }
        return false;
    }
}
