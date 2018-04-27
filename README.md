# Session middleware

This package provides a [Psr-15](https://www.php-fig.org/psr/psr-15/) middleware managing session. It works out of the box using the session configuration values, yet it is suited to the [Psr-7](https://www.php-fig.org/psr/psr-7/) request/response abstraction.

**Require** php >= 7.0

**Installation** `composer require ellipse/session`

**Run tests** `./vendor/bin/kahlan`

- [Using the session middleware](#using-the-session-middleware)

## Using the session middleware

```php
<?php

use Ellipse\Dispatcher;
use Ellipse\Session\SessionMiddleware;

// By default SessionMiddleware uses php session configuration values.
$middleware = new SessionMiddleware

    // Will call php session_set_save_handler($handler) before starting the session.
    ->withSaveHandler($handler)

    // Will call php session_name('session_name') before starting the session.
    ->withName('session_name')

    // Will call php session_save_path('/session/save/path') before starting the session.
    ->withSavePath('/session/save/path')

    // Will call php session_cache_limiter('public') before starting the session.
    ->withCacheLimiter('public')

    // Will call php session_cache_expire(60) before starting the session.
    ->withCacheExpire(60)

    // Will call php session_set_cookie_params(3600, '/path', 'domain', true, true) before starting the session.
    ->withCookieParams([
        'lifetime' => 3600,
        'path' => '/path',
        'domain' => 'domain',
        'secure' => true,
        'httponly' => true,
    ]);

// Build a dispatcher using the session middleware.
$dispatcher = new Dispatcher([

    $middleware,

    // Next middleware have access to the request Ellipse\Session::class attribute.
    new class implements MiddlewareInterface
    {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
        {
            // Session data is attached to the Ellipse\Session::class request attribute.
            $session = $request->getAttribute(Ellipse\Session::class);

            // Return the session id (session_id())
            $session->id();

            // Regenerate the session id (session_regenerate_id(bool $delete_old_session = false))
            $session->regenerate_id();

            // Set a session value.
            $session->set('key', 'value');

            // Set a session value only for the next session.
            $session->flash('key', 'value');

            // Return whether a session value is set.
            $session->has('key');

            // Return an array of all the session data.
            $session->all();

            // Return the value associated to the given key.
            $session->get('key');

            // Return a default value when the given key is not set.
            $session->get('notset', 'default');

            // Unset a session value.
            $session->unset('key');

            // Unset all session value.
            $session->delete();
        }
    }
]);
```
