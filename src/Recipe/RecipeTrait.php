<?php namespace Lit\Air\Recipe;

use Lit\Air\Recipe\Decorator\CacheDecorator;

trait RecipeTrait
{
    public function cached()
    {
        /**
         * @var RecipeInterface $this
         */
        return CacheDecorator::decorate($this);
    }

}
