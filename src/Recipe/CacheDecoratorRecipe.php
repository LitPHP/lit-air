<?php namespace Lit\Air\Recipe;

use Lit\Air\WritableContainerInterface;

class CacheDecoratorRecipe extends AbstractRecipeDecoratorRecipe
{
    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        $value = $this->recipe->resolve($container, $id);
        if (!is_null($id)) {
            $container->set($id, $value);
        }

        return $value;
    }
}
