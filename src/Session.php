<?php declare(strict_types=1);

namespace Ellipse;

class Session
{
    private $data;

    public function __construct(array &$data)
    {
        $this->data = $data;
    }
}
