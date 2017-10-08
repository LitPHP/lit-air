<?php namespace Lit\Air\Recipe;

use Lit\Air\WritableContainerInterface;

class AliasRecipe implements RecipeInterface
{
    /**
     * @var string
     */
    protected $alias;

    /**
     * @param string $alias
     * @param array $extra
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        return $container->get($this->alias);
    }
}
