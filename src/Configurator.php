<?php namespace Lit\Air;

use Lit\Air\Psr\Container;
use Lit\Air\Psr\ContainerException;
use Lit\Air\Recipe\CacheDecoratorRecipe;
use Lit\Air\Recipe\FixedValueRecipe;
use Lit\Air\Recipe\RecipeInterface;
use Symfony\Component\Yaml\Yaml;

class Configurator
{
    protected static $decorators = [
        'cache' => CacheDecoratorRecipe::class
    ];

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
            self::config($container, json_decode($config), $force);
        } else {
            self::config($container, Yaml::parse($config), $force);
        }
    }

    public static function configFile(Container $container, string $path, bool $force = true)
    {
        self::configString($container, file_get_contents($path), $force);
    }

    public static function convertToRecipe($value)
    {
        if (is_object($value) && $value instanceof RecipeInterface) {
            return $value;
        }

        if (is_callable($value)) {
            return Container::singleton($value);
        }

        if ($value instanceof \stdClass && isset($value->{0})) {
            $value = (array)$value;
            $type = array_shift($value);

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

                $recipe = call_user_func_array([Container::class, $type], $value);

                if ($valueDecorator) {
                    foreach ($valueDecorator as $name => $option) {
                        if (!isset(self::$decorators[$name])) {
                            throw new ContainerException("cannot understand recipe decorator [$name]");
                        }
                        $recipe = call_user_func([self::$decorators[$name], 'decorate'], $recipe);
                        if (!empty($option)) {
                            $recipe->setOption($option);
                        }
                    }
                }

                return $recipe;
            }

            throw new ContainerException("cannot understand given recipe");
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
            if (!is_array($value)) {
                throw new ContainerException('CLASSNAME:: entry must be array');
            }
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
}
