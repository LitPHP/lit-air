<?php namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\WritableContainerInterface;

class CachedRecipe implements RecipeInterface
{
    /**
     * @var callable
     */
    protected $builder;

    /**
     * @param callable $builder
     */
    public function __construct(callable $builder)
    {
        $this->builder = $builder;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        $value = Factory::of($container)->invoke($this->builder);
        if (!is_null($id)) {
            $container->set($id, $value);
        }
        
        return $value;
    }
}
