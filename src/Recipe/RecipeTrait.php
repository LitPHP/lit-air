<?php

declare(strict_types=1);

namespace Lit\Air\Recipe;

use Lit\Air\Recipe\Decorator\CacheDecorator;

trait RecipeTrait
{
    public function cached(): RecipeInterface
    {
        /**
         * @var RecipeInterface $this
         */
        return CacheDecorator::decorate($this);
    }

}
