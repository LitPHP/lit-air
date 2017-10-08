<?php namespace Lit\Air\Recipe;

abstract class AbstractRecipeDecoratorRecipe implements RecipeInterface
{
    use RecipeTrait;
    /**
     * @var RecipeInterface
     */
    protected $recipe;
    protected $option;

    public function __construct(RecipeInterface $recipe)
    {
        $this->recipe = $recipe;
    }

    public static function decorate(RecipeInterface $recipe)
    {
        return new static($recipe);
    }

    /**
     * @param mixed $option
     * @return AbstractRecipeDecoratorRecipe
     */
    public function setOption($option)
    {
        $this->option = $option;
        return $this;
    }
}
