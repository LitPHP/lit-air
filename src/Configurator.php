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
            && class_exists(substr($key, 0, -2))
        ) {
            if (!is_array($value)) {
                throw new ContainerException('CLASSNAME:: entry must be array');
            }
            $container->set($key, self::convertArray($value));
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

            if (array_key_exists($type, [
                'alias' => 1,
                'autowire' => 1,
                'cached' => 1,
                'multiton' => 1,
                'singleton' => 1,
                'value' => 1,
            ])) {
                return call_user_func_array([Container::class, $type], $value);
            }

            throw new ContainerException("cannot understand stub");
        }

        return Container::value($value);
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
            }
        }

        return $result;
    }
}
