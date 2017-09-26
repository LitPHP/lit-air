<?php

namespace Lit\Air\Tests;

use Lit\Air\Psr\Container;
use Lit\Air\Recipe\SingletonRecipe;

class SingletonStubTest extends AbstractTestCase
{
    public function testSmoke()
    {
        $key = self::randKey();
        $key2 = self::randKey();
        $obj = new \stdClass();
        $obj2 = new \stdClass();
        $counter = 0;
        $factory = function () use ($obj, &$counter) {
            $counter++;
            return $obj;
        };
        $stub = Container::singleton($factory);
        $stub2 = Container::singleton($factory);

        self::assertTrue($stub instanceof SingletonRecipe);

        $this->container->define($key, $stub);

        $this->assertKeyExistWithValue($key, $obj);
        self::assertSame(1, $counter, 'factory should be invoked once');
        $this->container->get($key);
        self::assertSame(1, $counter, 'factory should be invoked still once');
        $this->container->flush($key);
        $this->container->get($key);
        self::assertSame(1, $counter, 'factory should be invoked still once');

        //the cache is factory-based, another key or stub instance use same cache
        $this->container->define($key2, $stub);
        $this->container->get($key2);
        self::assertSame(1, $counter, 'factory should be invoked still once');

        $this->container->define($key2, $stub2);
        $this->container->get($key2);
        self::assertSame(1, $counter, 'factory should be invoked still once');

        //re define the stub affect immediately (the cache is on Stub class, not countainer)
        $this->container->define($key, Container::value($obj2));
        $this->assertKeyExistWithValue($key, $obj2);
    }
}
