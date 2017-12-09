<?php

declare(strict_types=1);

namespace Lit\Air\Psr;

use Lit\Air\Configurator;
use Lit\Air\Factory;
use Lit\Air\Injection\InjectorInterface;
use Lit\Air\Recipe\AliasRecipe;
use Lit\Air\Recipe\AutowireRecipe;
use Lit\Air\Recipe\FixedValueRecipe;
use Lit\Air\Recipe\InstanceRecipe;
use Lit\Air\Recipe\MultitonRecipe;
use Lit\Air\Recipe\RecipeInterface;
use Lit\Air\Recipe\SingletonRecipe;
use Lit\Air\WritableContainerInterface;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface, WritableContainerInterface
{
    const KEY_FACTORY = Factory::class;
    const KEY_INJECTORS = InjectorInterface::class;
    /**
     * @var RecipeInterface[]
     */
    protected $recipe = [];
    protected $cache = [];

    /**
     * @var ContainerInterface
     */
    protected $delegateContainer;

    public function __construct(?array $config = null)
    {
        if ($config) {
            Configurator::config($this, $config);
        }
    }

    public static function alias(string $alias): RecipeInterface
    {
        return new AliasRecipe($alias);
    }

    public static function autowire(?string $className = null, array $extra = []): RecipeInterface
    {
        return new AutowireRecipe($className, $extra);
    }

    public static function instance(?string $className = null, array $extra = []): RecipeInterface
    {
        return new InstanceRecipe($className, $extra);
    }

    public static function multiton(callable $builder): RecipeInterface
    {
        return new MultitonRecipe($builder);
    }


    public static function singleton(callable $builder): RecipeInterface
    {
        return new SingletonRecipe($builder);
    }

    public static function value($value): RecipeInterface
    {
        return new FixedValueRecipe($value);
    }

    public static function wrap(ContainerInterface $container): self
    {
        return (new static)->setDelegateContainer($container);
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        if (array_key_exists($id, $this->recipe)) {
            return $this->recipe[$id]->resolve($this, $id);
        }

        if ($id === static::KEY_FACTORY) {
            return $this->cache[$id] = new Factory($this);
        }

        if ($this->delegateContainer && $this->delegateContainer->has($id)) {
            return $this->delegateContainer->get($id);
        }

        throw new NotFoundException();
    }

    public function has($id)
    {
        return array_key_exists($id, $this->cache)
            || array_key_exists($id, $this->recipe)
            || $id === static::KEY_FACTORY
            || ($this->delegateContainer && $this->delegateContainer->has($id));
    }

    public function define(string $id, RecipeInterface $recipe): self
    {
        $this->recipe[$id] = $recipe;
        return $this;
    }

    public function getRecipe(string $id): ?RecipeInterface
    {
        if (array_key_exists($id, $this->recipe)) {
            return $this->recipe[$id];
        }

        return null;
    }

    public function extendRecipe(string $id, callable $wrapper): self
    {
        if (!array_key_exists($id, $this->recipe)) {
            throw new \InvalidArgumentException("recipe [$id] unexists");
        }

        $recipe = self::applyRecipeWrapper($wrapper, $this->recipe[$id]);

        $this->recipe[$id] = $recipe;

        return $this;
    }

    public function hasCacheEntry(string $id): bool
    {
        return array_key_exists($id, $this->cache);
    }

    public function flush(string $id): self
    {
        unset($this->cache[$id]);
        return $this;
    }

    public function addInjector(InjectorInterface $injector): self
    {
        if (!isset($this->cache[static::KEY_INJECTORS])) {
            $this->cache[static::KEY_INJECTORS] = [$injector];
        } else {
            $this->cache[static::KEY_INJECTORS][] = $injector;
        }

        return $this;
    }

    public function resolveRecipe($value)
    {
        return Configurator::convertToRecipe($value)->resolve($this);
    }

    public function set($id, $value): self
    {
        $this->cache[$id] = $value;
        return $this;
    }

    /**
     * @param ContainerInterface $delegateContainer
     * @return $this
     */
    public function setDelegateContainer(ContainerInterface $delegateContainer): self
    {
        $this->delegateContainer = $delegateContainer;

        return $this;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * @param callable $wrapper
     * @param RecipeInterface $recipe
     * @return RecipeInterface
     */
    protected static function applyRecipeWrapper(callable $wrapper, RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $wrapper($recipe);

        return $recipe;
    }
}