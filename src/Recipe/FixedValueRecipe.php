<?php namespace Lit\Air\Recipe;

use Lit\Air\WritableContainerInterface;

class FixedValueRecipe implements RecipeInterface
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        return $this->value;
    }
}
