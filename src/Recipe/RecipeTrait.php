<?php namespace Lit\Air\Recipe;

trait RecipeTrait
{
    public function cached()
    {
        /**
         * @var RecipeInterface $this
         */
        return CacheDecoratorRecipe::decorate($this);
    }

}
