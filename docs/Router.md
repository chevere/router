# Router

`🚧 OUTDATED DOCS`


The [Router](../reference/Chevere/Components/Router/Router.md) component is in charge of collecting [Routables](../reference/Chevere/Components/Router/Routables.md).

The router component is built on top of [FastRoute](https://github.com/nikic/FastRoute) with added context for the Chevere realm.

::: tip Learn by Example
Check the Router [examples](https://github.com/chevere/examples/tree/main/03.Http#00router-makephp) to learn playing with code.
:::

## Routing

Routing works by defining routes and HTTP method to [Controller](Action.md#controller) matching using file system based conventions. Routes are defined in a folder-based structure.

Tree below shows how a routing directory looks like.

```sh
/var/routing
├── articles <- route /articles/
│   ├── {id} <- route /articles/{id}/
│   │   └── GET.php
│   └── GET.php
└── post <- route /post/
    └── POST.php
```

File-system folder paths will reflect HTTP route paths.

Table below shows how system paths are interpreted as HTTP route paths for the [tree](#routing-filesystem-structure).

| Path                        | HTTP route     | HTTP method |
| --------------------------- | -------------- | ----------- |
| /var/routing/articles/      | /articles/     | GET         |
| /var/routing/articles/{id}/ | /articles/123/ | GET         |
| /var/routing/post/          | /post/         | POST        |

<!-- Each folder may define many [<methodName>.php](#methodnamephp) for each applicable HTTP method. Variables in the form of `{var}` are used to define dynamic route parameters known as [wildcards](#wildcards). -->

### `<methodName>.php`

HTTP endpoints are defined by using `<methodName>.php` naming convention, where `<methodName>` is the HTTP method name according to [RFC 7231](https://tools.ietf.org/html/rfc7231) and it must return a [Controller](Action.md#controller).

Accepted HTTP methods are `CONNECT, DELETE, GET, HEAD, OPTIONS, PATCH, POST, PUT, TRACE`.

The `/var/routing/post/POST.php` file below binds HTTP request `POST /post/` to `PostController`.

```php
# /var/routing/post/POST.php
use App\Controllers\PostController;

return new PostController;
```

::: tip
It is recommended to create a _different_ [Controller](Action.md#controller) for each HTTP endpoint. A controller resolving multiple HTTP endpoints is a bad practice.
:::

Note: Method `HEAD` is automatically added when adding `GET`.

### Wildcards

Wildcards are expressed as `{var}`  for folder-names as `{id}` in `/articles/{id}/`.

Wildcards are used to define route parameters which will be automatically configured to reflect the [Controller](Action.md#controller) parameters defined for the given route.

Controllers in the alleged route must define the same base wildcard parameters.

## Generating Router

The Router can be easily generated using the built-in tooling.

### Descriptors Maker

The [RoutingDescriptorsMaker](../reference/Chevere/Components/Router/Routing/RoutingDescriptorsMaker.md) component is in charge of creating the routing descriptors, which is the collection of routes interpreted from the filesystem.

```php
use Chevere\Router\Routing\RoutingDescriptorsMaker;
use function Chevere\Filesystem\dirForPath;

$routingDescriptorsMaker = new RoutingDescriptorsMaker(
    repository: 'app-routes'
);
$routingDescriptors = $routingDescriptorsMaker
    ->withDescriptorsFor(dir: dirForPath('/var/routing/'))
    ->descriptors();
```

### Router for Descriptors

The function `routerForRoutingDescriptors` allows to generate a router from RoutingDescriptors (see [RoutingDescriptorsMaker](#descriptors-maker)).

In the code below, `$router` is generated from `$routingDescriptors` and bound to `my-group`.

```php
use function Chevere\Router\Routing\routerForRoutingDescriptors;

$router = routerForRoutingDescriptors(descriptors: $routingDescriptors);
```

## Using Router

::: tip Learn by Example
Head over to the [Router resolve](https://github.com/chevere/examples/tree/main/03.Http#01router-resolvephp) example to see use-cases for the Router.
:::
