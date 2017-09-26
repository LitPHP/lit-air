<?php namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\WritableContainerInterface;

class AliasRecipe implements RecipeInterface
{
    /**
     * @var string
     */
    private $alias;
    /**
     * @var array
     */
    private $extra;

    /**
     * @param string $alias
     * @param array $extra
     */
    public function __construct(string $alias, array $extra = [])
    {
        $this->alias = $alias;
        $this->extra = $extra;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        return Factory::of($container)->produce($this->alias, $this->extra);
    }
}
