<?php namespace Lit\Air;

use Lit\Air\Psr\Container;
use Lit\Air\Psr\ContainerException;
use Psr\Container\ContainerInterface;

class Factory
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(ContainerInterface $container = null)
    {
        if ($container instanceof Container) {
            $this->container = $container;
        } else {
            $this->container = Container::wrap($container);
        }
        $this->container->set(Container::KEY_FACTORY, $this);
    }

    public static function of(ContainerInterface $container): self
    {
        if (!$container->has(Container::KEY_FACTORY)) {
            return new self($container);
        }

        return $container->get(Container::KEY_FACTORY);
    }

    /**
     * @param string $className
     * @param array $extraParameters
     * @return object
     */
    public function instantiate(string $className, array $extraParameters = [])
    {
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();

        $constructParams = $constructor
            ? $this->resolveParams($constructor->getParameters(), $className, $extraParameters)
            : [];

        $instance = $class->newInstanceArgs($constructParams);

        return $instance;
    }

    /**
     * @param callable $callback
     * @param array $extra
     * @return mixed
     */
    public function invoke(callable $callback, array $extra = [])
    {
        if (is_string($callback) || $callback instanceof \Closure) {
            $params = (new \ReflectionFunction($callback))->getParameters();
        } else {
            if (is_object($callback)) {
                $callback = [$callback, '__invoke'];
            }
            $params = (new \ReflectionClass($callback[0]))->getMethod($callback[1])->getParameters();
        }

        return call_user_func_array($callback, $this->resolveParams($params, '', $extra));
    }

    /**
     * @param string $className
     * @param array $extraParameters
     * @return object of $className«
     */
    public function produce($className, $extraParameters = [])
    {
        if ($this->container->has($className)) {
            return $this->container->get($className);
        }

        if (!class_exists($className)) {
            throw new \RuntimeException("$className not found");
        }

        $instance = $this->instantiate($className, $extraParameters);

        $this->container->set($className, $instance);

        return $instance;
    }


    protected function resolveParams(array $params, string $className, array $extra = [])
    {
        return array_map(
            function (\ReflectionParameter $parameter) use ($className, $extra) {
                return $this->resolveParam($className, $parameter, $extra);
            },
            $params
        );
    }

    protected function resolveParam($className, \ReflectionParameter $parameter, array $extraParameters)
    {
        list($keys, $paramClassName) = $this->parseParameter($parameter);

        try {
            return $this->produceDependency($className, $keys, $paramClassName, $extraParameters);
        } catch (ContainerException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw new ContainerException(
                sprintf('failed to produce constructor parameter "%s" for %s', $parameter->getName(), $className),
                0,
                $e
            );
        }
    }

    /**
     * @param \ReflectionParameter $parameter
     * @return array
     */
    protected function parseParameter(\ReflectionParameter $parameter)
    {
        $paramClassName = null;
        $keys = [$parameter->name];

        try {
            $paramClass = $parameter->getClass();
            if (!empty($paramClass)) {
                $keys[] = $paramClassName = $paramClass->name;
            }
        } catch (\ReflectionException $e) {
            //ignore exception when $parameter is type hinting for interface
        }

        $keys[] = $parameter->getPosition();

        return [$keys, $paramClassName];
    }

    protected function produceDependency($className, array $keys, $dependencyClassName = null, array $extra = [])
    {
        do {
            if (!empty($extra)) {
                foreach ($keys as $key) {
                    if (isset($extra[$key])) {
                        return $this->container->resolveRecipe($extra[$key]);
                    }
                }
            }

            if ($className && $this->container->has("$className::")) {
                $params = $this->container->get("$className::");
                foreach ($keys as $key) {
                    if (isset($params[$key])) {
                        return $this->container->resolveRecipe($params[$key]);
                    }
                }
            }
        } while ($className = get_parent_class($className));

        if ($dependencyClassName && $this->container->has($dependencyClassName)) {
            return $this->container->get($dependencyClassName);
        }

        if (isset($dependencyClassName) && class_exists($dependencyClassName)) {
            return $this->produce($dependencyClassName);
        }

        throw new ContainerException('failed to produce dependency');
    }
}
