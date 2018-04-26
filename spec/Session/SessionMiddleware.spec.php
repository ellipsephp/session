<?php

use function Eloquent\Phony\Kahlan\mock;
use function Eloquent\Phony\Kahlan\anInstanceOf;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Zend\Diactoros\Response\TextResponse;

use Ellipse\Session;
use Ellipse\Session\SessionMiddleware;
use Ellipse\Session\Exceptions\SessionStartException;
use Ellipse\Session\Exceptions\SessionDisabledException;
use Ellipse\Session\Exceptions\SessionAlreadyStartedException;

describe('SessionMiddleware', function () {

    beforeEach(function () {

        $this->middleware = new SessionMiddleware;

    });

    it('should implement MiddlewareInterface', function () {

        expect($this->middleware)->toBeAnInstanceOf(MiddlewareInterface::class);

    });

    describe('->withSaveHandler()', function () {

        it('should return a new SessionMiddleware using the given session save handler', function () {

            $save_handler = mock(SessionHandlerInterface::class)->get();

            $test = $this->middleware->withSaveHandler($save_handler);

            $middleware = new SessionMiddleware($save_handler);

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withName()', function () {

        it('should return a new SessionMiddleware using the given session name', function () {

            $test = $this->middleware->withName('name');

            $middleware = new SessionMiddleware(null, 'name');

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withSavePath()', function () {

        it('should return a new SessionMiddleware using the given session save path', function () {

            $test = $this->middleware->withSavePath('save_path');

            $middleware = new SessionMiddleware(null, null, 'save_path');

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withCacheLimiter()', function () {

        it('should return a new SessionMiddleware using the given session cache limiter', function () {

            $test = $this->middleware->withCacheLimiter('cache_limiter');

            $middleware = new SessionMiddleware(null, null, null, 'cache_limiter');

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withCacheExpire()', function () {

        it('should return a new SessionMiddleware using the given session cache expire', function () {

            $test = $this->middleware->withCacheExpire(3600);

            $middleware = new SessionMiddleware(null, null, null, null, 3600);

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withCookieParams()', function () {

        it('should return a new SessionMiddleware using the given session cookie params', function () {

            $test = $this->middleware->withCookieParams(['key' => 'value']);

            $middleware = new SessionMiddleware(null, null, null, null, null, ['key' => 'value']);

            expect($test)->toEqual($middleware);

        });

    });

    describe('->process()', function () {

        context('when the session is disabled', function () {

            it('should throw a SessionDisabledException', function () {

                allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_DISABLED);

                $request = mock(ServerRequestInterface::class)->get();
                $handler = mock(RequestHandlerInterface::class)->get();

                $test = function () use ($request, $handler) {

                    $this->middleware->process($request, $handler);

                };

                expect($test)->toThrow(new SessionDisabledException);

            });

        });

        context('when the session is active', function () {

            it('should throw a SessionAlreadyStartedException', function () {

                allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_ACTIVE);

                $request = mock(ServerRequestInterface::class)->get();
                $handler = mock(RequestHandlerInterface::class)->get();

                $test = function () use ($request, $handler) {

                    $this->middleware->process($request, $handler);

                };

                expect($test)->toThrow(new SessionAlreadyStartedException);

            });

        });

        context('when the session is not disabled and not active', function () {

            beforeEach(function () {

                allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_NONE);

                $this->request1 = mock(ServerRequestInterface::class);
                $this->handler = mock(RequestHandlerInterface::class);

            });

            context('when session_start() returns false', function () {

                beforeEach(function () {

                    allow('session_start')->toBeCalled()
                        ->with(SessionMiddleware::SESSION_START_OPTIONS)
                        ->andReturn(false);

                });

                it('should throw a SessionStartException', function () {

                    $test = function () {

                        $this->middleware->process($this->request1->get(), $this->handler->get());

                    };

                    expect($test)->toThrow(new SessionStartException);

                });

            });

            context('when session_start returns true', function () {

                beforeEach(function () {

                    session_id('session_id');
                    session_name('session_name');
                    session_cache_limiter('');
                    session_cache_expire(0);
                    session_set_cookie_params(3600, '/path', 'domain.com', true, true);

                    allow('session_start')->toBeCalled()
                        ->with(SessionMiddleware::SESSION_START_OPTIONS)
                        ->andReturn(true);

                    allow('time')->toBeCalled()->andReturn(strtotime('Tue, 20-Mar-2018 12:00:00 GMT'));

                    $_SESSION = ['key' => 'value'];

                    $this->request2 = mock(ServerRequestInterface::class)->get();
                    $this->response = new TextResponse('body', 404, ['header' => 'value', 'set-cookie' => 'cookie=value']);

                    $this->request1->withAttribute
                        ->with(Session::class, new Session($_SESSION))
                        ->returns($this->request2);

                    $this->handler->handle->with($this->request2)->returns($this->response);

                });

                it('should call the given request handler handle method with the new request', function () {

                    $this->middleware->process($this->request1->get(), $this->handler->get());

                    $this->handler->handle->calledWith($this->request2);

                });

                it('should keep the status code of the response produced by the given request handler', function () {

                    $test = $this->middleware->process($this->request1->get(), $this->handler->get())
                        ->getStatusCode();

                    expect($test)->toEqual(404);

                });

                it('should keep the body of the response produced by the given request handler', function () {

                    $test = $this->middleware->process($this->request1->get(), $this->handler->get())
                        ->getBody()
                        ->getContents();

                    expect($test)->toEqual('body');

                });

                it('should keep the headers of the response produced by the given request handler', function () {

                    $test = $this->middleware->process($this->request1->get(), $this->handler->get())
                        ->getHeaderLine('header');

                    expect($test)->toEqual('value');

                });

                it('should keep the cookies of the response produced by the given request handler', function () {

                    $test = $this->middleware->process($this->request1->get(), $this->handler->get())
                        ->getHeader('set-cookie');

                    expect($test[0])->toEqual('cookie=value');

                });

                context('when the session_cache_limiter param is not set', function () {

                    it('should return a response with no cache limiter related header', function () {

                        $response = $this->middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('expires');
                        $test2 = $response->getHeaderLine('cache-control');
                        $test3 = $response->getHeaderLine('last-modified');
                        $test4 = $response->getHeaderLine('Pragma');

                        expect($test1)->toEqual('');
                        expect($test2)->toEqual('');
                        expect($test3)->toEqual('');
                        expect($test4)->toEqual('');

                    });

                });

                context('when the session_cache_limiter param is set to public', function () {

                    beforeEach(function () {

                        $this->middleware = $this->middleware->withCacheLimiter('public');

                    });

                    it('should return a response with appropriate headers for zero cache expire configuration', function () {

                        $response = $this->middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('expires');
                        $test2 = $response->getHeaderLine('cache-control');
                        $test3 = $response->getHeaderLine('last-modified');

                        expect($test1)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');
                        expect($test2)->toEqual('public, max-age=0');
                        expect($test3)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');

                    });

                    it('should return a response with appropriate headers for non zero cache expire configuration', function () {

                        $middleware = $this->middleware->withCacheExpire(60);

                        $response = $middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('expires');
                        $test2 = $response->getHeaderLine('cache-control');
                        $test3 = $response->getHeaderLine('last-modified');

                        expect($test1)->toEqual('Tue, 20-Mar-2018 13:00:00 GMT');
                        expect($test2)->toEqual('public, max-age=3600');
                        expect($test3)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');

                    });

                });

                context('when the session_cache_limiter param is set to private', function () {

                    beforeEach(function () {

                        $this->middleware = $this->middleware->withCacheLimiter('private');

                    });

                    it('should return a response with appropriate headers for zero cache expire configuration', function () {

                        $response = $this->middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('expires');
                        $test2 = $response->getHeaderLine('cache-control');
                        $test3 = $response->getHeaderLine('last-modified');

                        expect($test1)->toEqual(SessionMiddleware::EXPIRED);
                        expect($test2)->toEqual('private, max-age=0');
                        expect($test3)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');

                    });

                    it('should return a response with appropriate headers for non zero cache expire configuration', function () {

                        $middleware = $this->middleware->withCacheExpire(60);

                        $response = $middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('expires');
                        $test2 = $response->getHeaderLine('cache-control');
                        $test3 = $response->getHeaderLine('last-modified');

                        expect($test1)->toEqual(SessionMiddleware::EXPIRED);
                        expect($test2)->toEqual('private, max-age=3600');
                        expect($test3)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');

                    });

                });

                context('when the session_cache_limiter param is set to private_no_expire', function () {

                    beforeEach(function () {

                        $this->middleware = $this->middleware->withCacheLimiter('private_no_expire');

                    });

                    it('should return a response with appropriate headers for zero cache expire configuration', function () {

                        $response = $this->middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('cache-control');
                        $test2 = $response->getHeaderLine('last-modified');

                        expect($test1)->toEqual('private, max-age=0');
                        expect($test2)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');

                    });

                    it('should return a response with appropriate headers for non zero cache expire configuration', function () {

                        $middleware = $this->middleware->withCacheExpire(60);

                        $response = $middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('cache-control');
                        $test2 = $response->getHeaderLine('last-modified');

                        expect($test1)->toEqual('private, max-age=3600');
                        expect($test2)->toEqual('Tue, 20-Mar-2018 12:00:00 GMT');

                    });

                });

                context('when the session_cache_limiter param is set to nocache', function () {

                    it('should return a response with appropriate headers', function () {

                        $middleware = $this->middleware->withCacheLimiter('nocache');

                        $response = $middleware->process($this->request1->get(), $this->handler->get());

                        $test1 = $response->getHeaderLine('expires');
                        $test2 = $response->getHeaderLine('cache-control');
                        $test3 = $response->getHeaderLine('Pragma');

                        expect($test1)->toEqual(SessionMiddleware::EXPIRED);
                        expect($test2)->toEqual('no-store, no-cache, must-revalidate');
                        expect($test3)->toEqual('no-cache');

                    });

                });

                context('when the request do not have a cookie matching the session name', function () {

                    it('should attach a cookie with a new session id to the response', function () {

                        allow('session_id')->toBeCalled()->andReturn('', 'new_session_id');

                        $test = $this->middleware->process($this->request1->get(), $this->handler->get())
                            ->getHeader('set-cookie');

                        expect($test[1])->toEqual(implode('; ', [
                            'session_name=new_session_id',
                            'expires=Tue, 20-Mar-2018 13:00:00 GMT',
                            'max-age=3600',
                            'path=/path',
                            'domain=domain.com',
                            'secure',
                            'httponly',
                        ]));

                    });

                });

                context('when the request have a cookie matching the session name', function () {

                    beforeEach(function () {

                        $this->request1->getCookieParams->returns(['session_name' => 'session_id']);

                    });

                    context('when a session name is set', function () {

                        it('should attach a cookie with this name to the response', function () {

                            $this->request1->getCookieParams->returns(['new_session_name' => 'session_id']);

                            $middleware = $this->middleware->withName('new_session_name');

                            $test = $middleware->process($this->request1->get(), $this->handler->get())
                                ->getHeader('set-cookie');

                            expect($test[1])->toEqual(implode('; ', [
                                'new_session_name=session_id',
                                'expires=Tue, 20-Mar-2018 13:00:00 GMT',
                                'max-age=3600',
                                'path=/path',
                                'domain=domain.com',
                                'secure',
                                'httponly',
                            ]));

                        });

                    });

                    context('when the lifetime cookie param is set', function () {

                        it('should attach a cookie with this lifetime to the response', function () {

                            $middleware = $this->middleware->withCookieParams(['lifetime' => 7200]);

                            $test = $middleware->process($this->request1->get(), $this->handler->get())
                                ->getHeader('set-cookie');

                            expect($test[1])->toEqual(implode('; ', [
                                'session_name=session_id',
                                'expires=Tue, 20-Mar-2018 14:00:00 GMT',
                                'max-age=7200',
                                'path=/path',
                                'domain=domain.com',
                                'secure',
                                'httponly',
                            ]));

                        });

                    });

                    context('when the path cookie param is set', function () {

                        it('should attach a cookie with this path to the response', function () {

                            $middleware = $this->middleware->withCookieParams(['path' => '/new/path']);

                            $test = $middleware->process($this->request1->get(), $this->handler->get())
                                ->getHeader('set-cookie');

                            expect($test[1])->toEqual(implode('; ', [
                                'session_name=session_id',
                                'expires=Tue, 20-Mar-2018 13:00:00 GMT',
                                'max-age=3600',
                                'path=/new/path',
                                'domain=domain.com',
                                'secure',
                                'httponly',
                            ]));

                        });

                    });

                    context('when the domain cookie param is set', function () {

                        it('should attach a cookie with this domain to the response', function () {

                            $middleware = $this->middleware->withCookieParams(['domain' => 'new.domain.com']);

                            $test = $middleware->process($this->request1->get(), $this->handler->get())
                                ->getHeader('set-cookie');

                            expect($test[1])->toEqual(implode('; ', [
                                'session_name=session_id',
                                'expires=Tue, 20-Mar-2018 13:00:00 GMT',
                                'max-age=3600',
                                'path=/path',
                                'domain=new.domain.com',
                                'secure',
                                'httponly',
                            ]));

                        });

                    });

                    context('when the secure cookie param is set to false', function () {

                        it('should attach a cookie without the secure option to the response', function () {

                            $middleware = $this->middleware->withCookieParams(['secure' => false]);

                            $test = $middleware->process($this->request1->get(), $this->handler->get())
                                ->getHeader('set-cookie');

                            expect($test[1])->toEqual(implode('; ', [
                                'session_name=session_id',
                                'expires=Tue, 20-Mar-2018 13:00:00 GMT',
                                'max-age=3600',
                                'path=/path',
                                'domain=domain.com',
                                'httponly',
                            ]));

                        });

                    });

                    context('when the httponly cookie param is set to false', function () {

                        it('should attach a cookie without the httponly option to the response', function () {

                            $middleware = $this->middleware->withCookieParams(['httponly' => false]);

                            $test = $middleware->process($this->request1->get(), $this->handler->get())
                                ->getHeader('set-cookie');

                            expect($test[1])->toEqual(implode('; ', [
                                'session_name=session_id',
                                'expires=Tue, 20-Mar-2018 13:00:00 GMT',
                                'max-age=3600',
                                'path=/path',
                                'domain=domain.com',
                                'secure',
                            ]));

                        });

                    });

                });

            });

        });

    });

});
