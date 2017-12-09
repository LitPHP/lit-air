<?php

declare(strict_types=1);

namespace Lit\Air;

use Lit\Air\Psr\Container;
use Lit\Air\Psr\ContainerException;
use Lit\Air\Recipe\Decorator\AbstractRecipeDecorator;
use Lit\Air\Recipe\Decorator\CacheDecorator;
use Lit\Air\Recipe\Decorator\CallbackDecorator;
use Lit\Air\Recipe\FixedValueRecipe;
use Lit\Air\Recipe\RecipeInterface;
use Symfony\Component\Yaml\Yaml;

class Configurator
{
    protected static $decorators = [
        'cache' => CacheDecorator::class,
        'callback' => CallbackDecorator::class
    ];

    public static function config(Container $container, array $config, bool $force = true): void
    {
        foreach ($config as $key => $value) {
            if (!$force && $container->has($key)) {
                continue;
            }
            self::write($container, $key, $value);
        }
    }

    public static function configString(Container $container, string $config, bool $force = true): void
    {
        if ($config[0] === '{') {
            self::config($container, json_decode($config, true), $force);
        } else {
            self::config($container, Yaml::parse($config), $force);
        }
    }

    public static function configFile(Container $container, string $path, bool $force = true): void
    {
        self::configString($container, file_get_contents($path), $force);
    }

    public static function convertToRecipe($value): RecipeInterface
    {
        if (is_object($value) && $value instanceof RecipeInterface) {
            return $value;
        }

        if (is_callable($value)) {
            return Container::singleton($value);
        }

        if (is_array($value) && array_key_exists(0, $value) && isset($value['$'])) {
            return self::makeRecipe($value);
        }

        return Container::value($value);
    }

    protected static function write(Container $container, $key, $value)
    {
        if (is_scalar($value) || is_resource($value)) {
            $container->set($key, $value);
            return;
        }

        if (
            substr($key, -2) === '::'
            && class_exists(substr($key, 0, -2))
        ) {
            $container->set($key, self::convertArray($value));
            return;
        }

        $recipe = self::convertToRecipe($value);

        if ($recipe instanceof FixedValueRecipe) {
            $container->set($key, $recipe->getValue());
        } else {
            $container->flush($key);
            $container->define($key, $recipe);
        }
    }

    /**
     * @param array $value
     * @return array
     */
    protected static function convertArray(array $value): array
    {
        $result = [];
        foreach ($value as $k => $v) {
            if (is_scalar($v) || is_resource($v)) {
                $result[$k] = $v;
            } else {
                $result[$k] = self::convertToRecipe($v);
                if ($result[$k] instanceof FixedValueRecipe) {
                    $result[$k] = $result[$k]->getValue();
                }
            }
        }

        return $result;
    }

    protected static function makeRecipe(array $value): RecipeInterface
    {
        $type = $value['$'];
        unset($value['$']);

        if (array_key_exists($type, [
            'alias' => 1,
            'autowire' => 1,
            'instance' => 1,
            'multiton' => 1,
            'singleton' => 1,
            'value' => 1,
        ])) {
            $valueDecorator = $value['decorator'] ?? null;
            unset($value['decorator']);

            /**
             * @var RecipeInterface $recipe
             */
            $recipe = call_user_func_array([Container::class, $type], $value);

            if ($valueDecorator) {
                $recipe = self::applyRecipeDecorators($valueDecorator, $recipe);
            }

            return $recipe;
        }

        throw new ContainerException("cannot understand given recipe");
    }

    /**
     * @param array $decorators
     * @param RecipeInterface $recipe
     * @return RecipeInterface
     */
    protected static function applyRecipeDecorators(array $decorators, RecipeInterface $recipe): RecipeInterface
    {
        foreach ($decorators as $name => $option) {
            if (isset(self::$decorators[$name])) {
                $recipe = call_user_func([self::$decorators[$name], 'decorate'], $recipe);
            } elseif (is_subclass_of($name, AbstractRecipeDecorator::class)) {
                $recipe = call_user_func([$option, 'decorate'], $recipe);
            } else {
                throw new ContainerException("cannot understand recipe decorator [$name]");
            }

            if (!empty($option)) {
                $recipe->setOption($option);
            }
        }

        return $recipe;
    }
}
