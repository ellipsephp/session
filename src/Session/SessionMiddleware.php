<?php declare(strict_types=1);

namespace Ellipse\Session;

use SessionHandlerInterface;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Ellipse\Session;
use Ellipse\Session\Exceptions\SessionStartException;
use Ellipse\Session\Exceptions\SessionDisabledException;
use Ellipse\Session\Exceptions\SessionAlreadyStartedException;

class SessionMiddleware implements MiddlewareInterface
{
    /**
     * Session options disabling automatic php session cookie management when
     * calling session_start().
     *
     * @var array
     */
    const SESSION_START_OPTIONS = [
        'use_trans_sid' => false,
        'use_cookies' => false,
        'use_only_cookies' => true,
        'cache_limiter' => '',
    ];

    /**
     * The date format for the session headers.
     *
     * @var string
     */
    const DATE_FORMAT = 'D, d-M-Y H:i:s T';

    /**
     * The date for the expired value of cache limiter header.
     *
     * @var string
     */
    const EXPIRED = 'Thu, 19 Nov 1981 08:52:00 GMT';

    /**
     * The user defined session save handler.
     *
     * @var \SessionHandlerInterface|null
     */
    private $save_handler;

    /**
     * The user defined session name.
     *
     * @var string|null
     */
    private $name;

    /**
     * The user defined session save path.
     *
     * @var string|null
     */
    private $save_path;

    /**
     * The user defined session cache limiter.
     *
     * @var string|null
     */
    private $cache_limiter;

    /**
     * The user defined session cache expire.
     *
     * @var int|null
     */
    private $cache_expire;

    /**
     * The user defined session cookie params.
     *
     * @var array
     */
    private $cookie_params;

    /**
     * Set up a session middleware with optional session save handler, name,
     * save path, cache limiter, cache expire and cookie params.
     *
     * @param \SessionHandlerInterface  $save_handler
     * @param string                    $name
     * @param string                    $save_path
     * @param string                    $cache_limiter
     * @param int                       $cache_expire
     * @param array                     $cookie_params
     */
    public function __construct(
        SessionHandlerInterface $save_handler = null,
        string $name = null,
        string $save_path = null,
        string $cache_limiter = null,
        int $cache_expire = null,
        array $cookie_params = []
    ) {
        $this->save_handler = $save_handler;
        $this->name = $name;
        $this->save_path = $save_path;
        $this->cache_limiter = $cache_limiter;
        $this->cache_expire = $cache_expire;
        $this->cookie_params = $cookie_params;
    }

    /**
     * Return a new session middleware using the given session save handler.
     *
     * @param \SessionHandlerInterface $save_handler
     * @return \Ellipse\Session\SessionMiddleware
     */
    public function withSaveHandler(SessionHandlerInterface $save_handler): SessionMiddleware
    {
        return new SessionMiddleware(
            $save_handler,
            $this->name,
            $this->save_path,
            $this->cache_limiter,
            $this->cache_expire,
            $this->cookie_params
        );
    }

    /**
     * Return a new session middleware using the given session name.
     *
     * @param string $name
     * @return \Ellipse\Session\SessionMiddleware
     */
    public function withName(string $name): SessionMiddleware
    {
        return new SessionMiddleware(
            $this->save_handler,
            $name,
            $this->save_path,
            $this->cache_limiter,
            $this->cache_expire,
            $this->cookie_params
        );
    }

    /**
     * Return a new session middleware using the given session save path.
     *
     * @param string $save_path
     * @return \Ellipse\Session\SessionMiddleware
     */
    public function withSavePath(string $save_path): SessionMiddleware
    {
        return new SessionMiddleware(
            $this->save_handler,
            $this->name,
            $save_path,
            $this->cache_limiter,
            $this->cache_expire,
            $this->cookie_params
        );
    }

    /**
     * Return a new session middleware using the given session cache limiter.
     *
     * @param string $cache_limiter
     * @return \Ellipse\Session\SessionMiddleware
     */
    public function withCacheLimiter(string $cache_limiter): SessionMiddleware
    {
        return new SessionMiddleware(
            $this->save_handler,
            $this->name,
            $this->save_path,
            $cache_limiter,
            $this->cache_expire,
            $this->cookie_params
        );
    }

    /**
     * Return a new session middleware using the given session cache expire.
     *
     * @param int $cache_expire
     * @return \Ellipse\Session\SessionMiddleware
     */
    public function withCacheExpire(int $cache_expire): SessionMiddleware
    {
        return new SessionMiddleware(
            $this->save_handler,
            $this->name,
            $this->save_path,
            $this->cache_limiter,
            $cache_expire,
            $this->cookie_params
        );
    }

    /**
     * Return a new session middleware using the given session cookie params.
     *
     * @param array $cookie_params
     * @return \Ellipse\Session\SessionMiddleware
     */
    public function withCookieParams(array $cookie_params): SessionMiddleware
    {
        return new SessionMiddleware(
            $this->save_handler,
            $this->name,
            $this->save_path,
            $this->cache_limiter,
            $this->cache_expire,
            $cookie_params
        );
    }

    /**
     * Enable session for the given request handler.
     *
     * @param \Psr\Http\Message\ServerRequestInterface  $request
     * @param \Psr\Http\Server\RequestHandlerInterface  $handler
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Ellipse\Session\Exceptions\SessionStartException
     * @throws \Ellipse\Session\Exceptions\SessionDisabledException
     * @throws \Ellipse\Session\Exceptions\SessionAlreadyStartedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Fail when session is disabled.
        if (session_status() === PHP_SESSION_DISABLED) {

            throw new SessionDisabledException;

        }

        // Fail when session is already started.
        if (session_status() === PHP_SESSION_ACTIVE) {

            throw new SessionAlreadyStartedException;

        }

        // Set the user defined session parameters.
        if (! is_null($this->save_handler)) session_set_save_handler($this->save_handler);
        if (! is_null($this->name)) session_name($this->name);
        if (! is_null($this->save_path)) session_save_path($this->save_path);
        if (! is_null($this->cache_limiter)) session_cache_limiter($this->cache_limiter);
        if (! is_null($this->cache_expire)) session_cache_expire($this->cache_expire);
        if ($this->cookie_params != []) $this->setCookieParams($this->cookie_params);

        // Set the session id.
        $name = session_name();

        $id = $request->getCookieParams()[$name] ?? '';

        session_id($id);

        // Start the session with options disabling cookies.
        if (session_start(self::SESSION_START_OPTIONS)) {

            // Add the session to the request.
            $request = $request->withAttribute(Session::class, new Session($_SESSION));

            // Get a response from the request handler.
            $response = $handler->handle($request);

            // Save the session data and close the session.
            session_write_close();

            // Return a response with session headers attached.
            return $this->attachSessionHeaders($response);

        }

        throw new SessionStartException;
    }

    /**
     * Set the given session cookie params.
     *
     * @param array $cookie_params
     * @return void
     */
    private function setCookieParams(array $cookie_params)
    {
        $params = array_merge(session_get_cookie_params(), array_change_key_case($cookie_params));

        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    /**
     * Attach the session headers to the given response.
     *
     * Trying to emulate the default php 7.0 headers generations. Adapted from
     * Relay.Middleware SessionHeadersHandler.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @see https://github.com/relayphp/Relay.Middleware/blob/1.x/src/SessionHeadersHandler.php
     */
    private function attachSessionHeaders(ResponseInterface $response): ResponseInterface
    {
        $time = time();

        $response = $this->attachCacheLimiterHeader($response, $time);
        $response = $this->attachSessionCookie($response, $time);

        return $response;
    }

    /**
     * Attach a session cache limiter header to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param int                                   $time
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function attachCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $cache_limiter = session_cache_limiter();

        switch ($cache_limiter) {
            case 'public':
                return $this->attachPublicCacheLimiterHeader($response, $time);
            case 'private':
                return $this->attachPrivateCacheLimiterHeader($response, $time);
            case 'private_no_expire':
                return $this->attachPrivateNoExpireCacheLimiterHeader($response, $time);
            case 'nocache':
                return $this->attachNocacheCacheLimiterHeader($response);
            default:
                return $response;
        }
    }

    /**
     * Attach a public cache limiter header to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param int                                   $time
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-7.0/ext/session/session.c#L1267-L1284
     */
    private function attachPublicCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $cache_expire = session_cache_expire();

        $max_age = $cache_expire * 60;
        $expires = gmdate(self::DATE_FORMAT, $time + $max_age);
        $cache_control = "public, max-age={$max_age}";
        $last_modified = gmdate(self::DATE_FORMAT, $time);

        return $response
            ->withAddedHeader('Expires', $expires)
            ->withAddedHeader('Cache-Control', $cache_control)
            ->withAddedHeader('Last-Modified', $last_modified);
    }

    /**
     * Attach a private cache limiter header to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param int                                   $time
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-7.0/ext/session/session.c#L1297-L1302
     */
    private function attachPrivateCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $response = $response->withAddedHeader('Expires', self::EXPIRED);

        return $this->attachPrivateNoExpireCacheLimiterHeader($response, $time);
    }

    /**
     * Attach a private_no_expire cache limiter header to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param int                                   $time
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-7.0/ext/session/session.c#L1286-L1295
     */
    private function attachPrivateNoExpireCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $cache_expire = session_cache_expire();

        $max_age = $cache_expire * 60;
        $cache_control = "private, max-age={$max_age}";
        $last_modified = gmdate(self::DATE_FORMAT, $time);

        return $response
            ->withAddedHeader('Cache-Control', $cache_control)
            ->withAddedHeader('Last-Modified', $last_modified);
    }

    /**
     * Attach a nocache cache limiter header to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-7.0/ext/session/session.c#L1304-L1314
     */
    private function attachNocacheCacheLimiterHeader(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withAddedHeader('Expires', self::EXPIRED)
            ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withAddedHeader('Pragma', 'no-cache');
    }

    /**
     * Attach a session cookie to the given response.
     *
     * @param \Psr\Http\Message\ResponseInterface   $response
     * @param int                                   $time
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @see https://github.com/php/php-src/blob/PHP-7.0/ext/session/session.c#L1402-L1476
     */
    private function attachSessionCookie(ResponseInterface $response, int $time): ResponseInterface
    {
        // Get the session id, name and the cookie options.
        $id = session_id();
        $name = session_name();
        $options = session_get_cookie_params();

        // Create a cookie header.
        $header = urlencode($name) . '=' . urlencode($id);

        if ($options['lifetime'] > 0) {

            $expires = gmdate(self::DATE_FORMAT, $time + $options['lifetime']);

            $header .= "; expires={$expires}; max-age={$options['lifetime']}";

        }

        if ($options['path']) $header .= "; path={$options['path']}";
        if ($options['domain']) $header .= "; domain={$options['domain']}";
        if ($options['secure']) $header .= '; secure';
        if ($options['httponly']) $header .= '; httponly';

        // Return a new response with the cookie header.
        return $response->withAddedHeader('set-cookie', $header);
    }
}
