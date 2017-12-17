<?php

declare(strict_types=1);

namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\WritableContainerInterface;

class SingletonRecipe extends AbstractRecipe
{
    protected static $cache = [];
    protected $hash;
    /**
     * @var callable
     */
    protected $builder;
    /**
     * @var array
     */
    protected $extra;

    /**
     * MultitonStub constructor.
     * @param callable $builder
     * @param array $extra
     */
    public function __construct(callable $builder, array $extra = [])
    {
        $this->builder = $builder;

        if (is_array($builder)) {
            $hash = implode('::', [
                is_string($builder[0]) ? $builder[0] : spl_object_hash($builder[0]),
                $builder[1],
            ]);
        } elseif (is_object($builder)) {
            /** @noinspection PhpParamsInspection */
            $hash = spl_object_hash($builder);
        } else {
            throw new \InvalidArgumentException();
        }

        $this->hash = $hash;
        $this->extra = $extra;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        if (isset(self::$cache[$this->hash])) {
            return self::$cache[$this->hash];
        }

        $value = Factory::of($container)->invoke($this->builder, $this->extra);
        self::$cache[$this->hash] = $value;

        return $value;
    }
}
