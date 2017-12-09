<?php

declare(strict_types=1);

namespace Lit\Air\Recipe;

use Lit\Air\Factory;
use Lit\Air\WritableContainerInterface;

class MultitonRecipe extends AbstractRecipe
{
    /**
     * @var callable
     */
    protected $builder;

    /**
     * MultitonStub constructor.
     * @param callable $builder
     */
    public function __construct(callable $builder)
    {
        $this->builder = $builder;
    }

    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        return Factory::of($container)->invoke($this->builder);
    }
}
