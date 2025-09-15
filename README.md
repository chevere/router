# Router

![Chevere](chevere.svg)

[![Build](https://img.shields.io/github/actions/workflow/status/chevere/router/test.yml?branch=0.9&style=flat-square)](https://github.com/chevere/router/actions)
![Code size](https://img.shields.io/github/languages/code-size/chevere/router?style=flat-square)
[![Apache-2.0](https://img.shields.io/github/license/chevere/router?style=flat-square)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-blueviolet?style=flat-square)](https://phpstan.org/)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fchevere%2Frouter%2F0.9)](https://dashboard.stryker-mutator.io/reports/github.com/chevere/router/0.9)

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=chevere_router&metric=alert_status)](https://sonarcloud.io/dashboard?id=chevere_router)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=chevere_router&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=chevere_router)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=chevere_router&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=chevere_router)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=chevere_router&metric=security_rating)](https://sonarcloud.io/dashboard?id=chevere_router)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=chevere_router&metric=coverage)](https://sonarcloud.io/dashboard?id=chevere_router)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=chevere_router&metric=sqale_index)](https://sonarcloud.io/dashboard?id=chevere_router)
[![CodeFactor](https://www.codefactor.io/repository/github/chevere/router/badge)](https://www.codefactor.io/repository/github/chevere/router)

## Summary

Router is a library for creating routing systems for [chevere/http](https://chevere.org/packages/http). It is compatible with the following [PHP-FIG](https://www.php-fig.org) PSR:

- PSR-7: HTTP message interfaces
- PSR-11: Container interface
- PSR-15: HTTP Server Request Handlers
- PSR-17: HTTP Factories

## Installing

Router is available through [Packagist](https://packagist.org/packages/chevere/router) and the repository source is at [chevere/router](https://github.com/chevere/router).

```sh
composer require chevere/router
```

## Quick start

```php
// Define routes
$routes = routes(
    route('/hello', GET: 'hello.twig'),
    route('/api/users',
        GET: UserListController::class,
        POST: headless(UserCreateController::class, CsrfMiddleware::class)
    ),
    route('/products/{id}',
        GET: bind('product.twig', ProductGetController::class),
        PUT: ProductUpdateController::class,
        DELETE: ProductDeleteController::class
    )
);
// Create router
$router = router($routes);
// Handle request
$routed = $router->routed($serverRequest, $responseFactory, $container);
$response = $routed->response();
$return = $routed->return(); // Controller return value
```

## What it does

The Router library allows you to bind paths to HTTP methods and logic. It maps paths to their corresponding HTTP controller actions, views, and middleware pipelines. It also collects and validates the views and dependencies used in the routing process.

For example, to resolve this:

```plain
GET /product/123
    -> ProductGet->__invoke(123):context
    -> product.twig

DELETE /product/123
    -> ProductDelete->__invoke(123)
```

You need to write the following route code:

```php
$route = route(
    '/product/{id}',
    GET: bind('product.twig', ProductGet::class),
    DELETE: ProductDelete::class,
);
```

And the HTTP controllers may look like this:

```php
class ProductGet extends Controller
{
    public function __invoke(string $id): array
    {
        // ...
        return $context;
    }
}

class ProductDelete extends Controller
{
    public function __invoke(string $id): void
    {
        // ...
    }
}
```

## Bind

A Bind is the conjunction of a controller, its middleware pipeline and a view. Use helper function `bind($view, $controller, ...$middleware)` to explicitly create a binding.

```php
$bind = bind(
    view: 'product.twig',
    controller: ProductGet::class,
    ...$middleware // PSR-15
)
```

Use method `controllerName()` to access the ControllerName API.

```php
$bind->controllerName(); // ProductGet
```

Use method `view()` to access the view string.

```php
$bind->view(); // product.twig
```

Use method `middlewares()` to access the Middlewares collection API.

```php
$bind->middlewares();
```

## Headless

Use helper function `headless($controller, ...$middleware)` to define a [bind](#bind) without a view, only the controller and middleware.

```php
headless(ProductGet::class, ...$middleware)
```

## Route

Use the helper function `route($path, ...)` to define path endpoints. An endpoint is defined as the conjunction of a path, an HTTP method  and a controller.

```php
route('/hello-world', GET: ..., POST: ...)
```

### View route

When no controller is needed you can bind the HTTP method to the target view, without indicating a controller.

```php
route('/', GET: 'home.twig')
```

### Headless route

When no view is needed you can bind the HTTP method to the target controller, without indicating a view.

```php
route(
    '/product/{id}',
    DELETE: ProductDelete::class,
)
```

### Dynamic route

Dynamic routes use variable wildcards (`{variable}` syntax) to denote variable path components.

```php
route('/products/{id}', GET: MyController::class);
```

Where `MyController` class method `__invoke()` parameters must match the defined wildcards:

```php
public function __invoke(string $id) {...}
```

Path variables implicit match against `[^/]+`. To customize use `StringAttr` on main’s function parameters.

```php
use Chevere\Parameter\Attributes\StringAttr;

public function __invoke(
    #[StringAttr('/\d+/')]
    string $id
) {
    // $id is digits only
}
```

### The `view` argument

Use the `view` argument to define the same view for all endpoints.

```php
route(
    '/login',
    view: 'login.twig',
    GET: LoginGet::class,
    POST: LoginPost::class,
)
```

Use helpers like `bind()` or `headless()` to override the base `view` argument definition.

### The `middleware` argument

Use the `middleware` argument to define the same middleware pipeline for all endpoints.

```php
route(
    '/signup',
    view: 'signup.twig',
    middleware: middlewares(
        RedirectIfLogged::class, // PSR-15
        TurnstileVerify::class, // PSR-15
    ),
    GET: SignUpGet::class,
    POST: SignUpPost::class,
)
```

Use helpers like `bind()` or `headless()` to override the base `middleware` argument definition.

### The `exclude` argument

The `exclude` argument enables to define middleware names that should be excluded from the pipeline.

```php
route(
    '/logout',
    exclude: middlewares(
        TurnstileVerify::class, // PSR-15
        ChallengeTwoFactor::class, // PSR-15
    ),
    POST: LogoutPost::class,
)
```

## Middlewares

The Middlewares API enables you to organize PSR-15 middleware for your routes. Use the helper function `middlewares(...$middleware)` to create a Middlewares collection.

```php
$middlewares = middlewares(
    SessionMiddleware::class,
    AuthMiddleware::class
);
```

You can use this collection in route definitions to apply middleware to specific routes.

## Routes

The Routes API enables to collect, assert, inspect and organize Route objects.

Use helper function `routes(...$route|$routes)` to create a Routes object.

```php
$routes = routes(
    route(...),
    routes(...),
);
```

### Managing routes

Use method `withRoute(...$route)` to add Route objects, use method `withRoutes(...$routes)` to add Routes objects.

```php
$routes = $routes->withRoute($route);
$routes = $routes->withRoutes($moreRoutes);
```

Use method `has(...$path)` to tell if the path is already routed. Use `get($path)` to retrieve the route for a given path.

```php
$routes = $routes->has('/pricing'); // bool
$home = $routes->get('/pricing'); // RouteInterface
```

### Middleware pipelines

Use method `withPrependMiddleware($midlewares)` to prepend middleware to the begin of the pipeline. Use this for middleware that must resolve early in execution order.

```php
$routes = $routes->withPrependMiddleware(
    middlewares(
        SessionSetUpCSRFToken::class, // PSR-15
    )
)
```

Use method `withAppendMiddleware($middlewares)` to append middleware to the end of the pipeline. Use this for middleware that must resolve last in execution order.

```php
$routes = $routes->withAppendMiddleware(
    middlewares(
        SessionCheckCSRFToken::class, // PSR-15
    )
)
```

## Views

The Views API enables to assert the views collected for all routes. Use `assert($viewsDir)` method to assert that the views directory contains the view names defined in routes.

```php
$router->views()->assert($viewsDir);
```

## Container

Use `new Container(...)` to create the dependency container by passing entries you may need to manually create.

```php
$container = new Container(
    database: $database,
    // other entries
);
```

### Adding entries

Use method `with(...$entries)` to add one or more named entries to the container.

```php
$container = $container->with(
    status: 'challenged',
    challenge: '2fa',
);
```

### Accessing entries

Use method `has($name)` to tell if the container has an entry by name. Use method `get($name)` to retrieve the entry value.

```php
$container->has('session'); // bool true
$session = $container->get('session'); // SessionInterface
```

### Automatic dependency injection

Use method `withAutoInject($deps, ...$ignore)` to automatically inject missing dependencies recursively.

```php
$container = $container->withAutoInject($deps, ...$ignore);
```

The `ignore` argument allows you to define dependencies that should be ignored, which is useful for dependencies that must be [late injected](#late-dependency-injection) after the middleware pipeline resolves.

## Dependencies

The Dependencies API enables to interact with the dependencies detected for all routing participants. The Router will collect every `__construct` on both controllers and middleware, to provide a collection where you can use your own dependency injection logic.

```php
$deps = $router->dependencies();
```

### Assert dependencies

Use method `assert($container)` to assert that the PSR-11 `$container` meets the required dependencies.

```php
$deps->assert($container);
```

This is an additional guard that can be used to static detect missing dependencies. On runtime, the system will throw an exception before even reach the middleware layer.

## Routed

The Routed API enables to interact with the outcome result of the routing process. Use method `routed(...)` to resolve routing.

```php
$routed = $router->routed(
    $serverRequest,   // PSR-7
    $responseFactory, // PSR-17
    $container,       // PSR-11
    $callback
);
```

### Late dependency injection

The `$callback` argument enables to pass logic that will resolve after the middleware pipeline and before the controller layer.

```php
use Chevere\Router\Interfaces\ContainerInterface;

$callback = function (ContainerInterface $container): ContainerInterface {
    $session = $container->get('sessionFactory')->newSession(
        $container->get('requestUser')->sessionId
    );

    return $container->with(
        session: $session,
        user: $session->getOrDefault('user')
    );
};
```

### Routed outcome

Use method `hasThrowable()` to tell if routing throws an exception. Use method `throwable()` to access the exception (if any).

```php
$hasThrowable = $routed->hasThrowable(); // bool
$throwable = $routed->throwable();
```

The system is flexible as it enables to define catch-all strategies. For example, you may want to catch `ControllerException` objects and pass-by everything else.

```php
if ($routed->hasThrowable()
    && ! ($routed->throwable() instanceof ControllerException)
) {
    throw $routed->throwable();
}
```

Use method `response()` to access the routed PSR-7 response object.

```php
$psr7Response = $routed->response();
```

Use method `bind()` to access the routed [Bind](#bind) API, which enables to tell the controller, view and middleware.

```php
$bind = $routed->bind();
```

Use method `return()` to access to the controller return value. This is the value after Action I/O guard layer.

```php
$controllerReturn = $routed->return();
```

## Documentation

Documentation is available at [chevere.org](https://chevere.org/packages/router).

## License

Copyright [Rodolfo Berrios A.](https://rodolfoberrios.com/)

Chevere is licensed under the Apache License, Version 2.0. See [LICENSE](LICENSE) for the full license text.

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
