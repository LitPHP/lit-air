<?php namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\WritableContainerInterface;

class SingletonRecipe implements RecipeInterface
{
    protected static $cache = [];
    protected $hash;
    /**
     * @var callable
     */
    protected $factory;

    /**
     * MultitonStub constructor.
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;

        if (is_array($factory)) {
            $hash = implode('::', [
                is_string($factory[0]) ? $factory[0] : spl_object_hash($factory[0]),
                $factory[1],
            ]);
        } elseif (is_object($factory)) {
            /** @noinspection PhpParamsInspection */
            $hash = spl_object_hash($factory);
        } else {
            throw new \InvalidArgumentException();
        }

        $this->hash = $hash;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        if (isset(self::$cache[$this->hash])) {
            return self::$cache[$this->hash];
        }

        $value = Factory::of($container)->invoke($this->factory);
        self::$cache[$this->hash] = $value;

        return $value;
    }
}
