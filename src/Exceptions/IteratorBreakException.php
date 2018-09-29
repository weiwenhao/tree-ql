<?php

namespace Weiwenhao\Including\Exceptions;

use Throwable;

class IteratorBreakException extends \Exception
{
    private $data;

    public function __construct($data, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
