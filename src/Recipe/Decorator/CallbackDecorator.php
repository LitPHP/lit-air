<?php namespace Lit\Air\Recipe\Decorator;

use Lit\Air\WritableContainerInterface;

class CallbackDecorator extends AbstractRecipeDecorator
{
    public function resolve(WritableContainerInterface $container, ?string $id = null)
    {
        $delegate = function () use ($container) {
            return $this->recipe->resolve($container);
        };

        return call_user_func($this->option, $delegate, $container, $id);
    }
}
