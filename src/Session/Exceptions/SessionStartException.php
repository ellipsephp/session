<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use RuntimeException;

class SessionStartException extends RuntimeException implements SessionExceptionInterface
{
    public function __construct()
    {
        parent::__construct('SessionMiddleware: session_start() returned false');
    }
}
