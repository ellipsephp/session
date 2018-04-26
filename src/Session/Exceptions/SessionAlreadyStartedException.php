<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use LogicException;

class SessionAlreadyStartedException extends LogicException implements SessionExceptionInterface
{
    public function __construct()
    {
        parent::__construct('A session had already been started: session must not be manually started when using SessionMiddleware');
    }
}
