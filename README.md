Lit Air
=======

> Dependency Injection for Lit

### Features

- PSR-11 compliant

- Recipe (for singleton, alias, lazy instantiate, etc.)

- Delegate lookup
  - works with composite container pattern
  - provide features for other container 

- Smart autowire

  ```php
  Factory::of($container)->produce($className[, $extraParameters]);
  ```

  - in addition to classname, search parameter name & position for dependency
  - parameter with default value can safely ignored
  - provide extra parameter at call time

- Method injection and injected instantiate

  ```php
  Factory::of($container)->instantiate($className[, $extraParameters]); //this won't write $className to $container
  Factory::of($container)->invoke($callback[, $extraParameters]);
  ```

  - one-shot dependency injection

- Configure your container with several approach
  - chained method call (php native)
  - php array
  - ​json/yaml

### Todo

- [x] basic container implement
- [x] autowire (constructor injection)
- [x] method injection
- [ ] setter injection
- [ ] test coverage
- [ ] documentation
