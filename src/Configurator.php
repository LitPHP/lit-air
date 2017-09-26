<?php namespace Lit\Air;

use Lit\Air\Psr\Container;
use Lit\Air\Psr\ContainerException;
use Lit\Air\Recipe\RecipeInterface;
use Symfony\Component\Yaml\Yaml;

class Configurator
{
    public static function config(Container $container, array $config, bool $force = true)
    {
        foreach ($config as $key => $value) {
            if (!$force && $container->has($key)) {
                continue;
            }
            self::write($container, $key, $value);
        }
    }

    public static function configString(Container $container, string $config, bool $force = true)
    {
        if ($config[0] === '{') {
            self::config($container, json_decode($config, true), $force);
        } else {
            self::config($container, Yaml::parse($config), $force);
        }
    }

    public static function configFile(Container $container, string $path, bool $force = true)
    {
        self::configString($container, file_get_contents($path), $force);
    }

    protected static function write(Container $container, $key, $value)
    {
        if (is_scalar($value) || is_resource($value)) {
            $container->set($key, $value);
            return;
        }

        if (
            substr($key, -2) === '::'
            && class_exists(substr($key, 0. - 2))
        ) {
            $container->set($key, array_combine(array_keys($value), array_map(function ($v) {
                if (is_scalar($v) || is_resource($v)) {
                    return $v;
                }
                return self::convertToRecipe($v);
            }, $value)));
            return;
        }

        $container->flush($key);
        $container->define($key, self::convertToRecipe($value));
    }

    protected static function convertToRecipe($value)
    {
        if (is_object($value) && $value instanceof RecipeInterface) {
            return $value;
        }

        if (is_callable($value)) {
            return Container::singleton($value);
        }

        if (is_array($value)) {
            $type = array_shift($value);
            switch ($type) {
                case "alias":
                case "autowire":
                case "cached":
                case "multiton":
                case "singleton":
                case "value":
                    return call_user_func_array([Container::class, $type], $value);
                default:
                    throw new ContainerException("cannot understand stub");
            }
        }

        return Container::value($value);
    }
}
