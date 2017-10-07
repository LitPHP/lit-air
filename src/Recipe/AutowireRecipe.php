<?php namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\Psr\ContainerException;
use Lit\Air\WritableContainerInterface;

class AutowireRecipe implements RecipeInterface
{
    /**
     * @var null|string
     */
    protected $className;
    /**
     * @var array
     */
    protected $extra;

    public function __construct(array $extra = [], ?string $className = null)
    {
        $this->extra = $extra;
        $this->className = $className;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        $className = is_null($this->className) ? $id : $this->className;
        if (!class_exists($className)) {
            throw new ContainerException('unknown autowire class name');
        }

        return Factory::of($container)->produce($className, $this->extra);
    }
}
