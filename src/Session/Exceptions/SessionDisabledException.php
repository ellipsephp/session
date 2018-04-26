<?php declare(strict_types=1);

namespace Ellipse\Session\Exceptions;

use LogicException;

class SessionDisabledException extends LogicException implements SessionExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Session is disabled: session must not be disabled when using SessionMiddleware');
    }
}
