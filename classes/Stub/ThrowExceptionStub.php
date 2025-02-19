<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Stub;

use Cz\PHPUnit\MockDB\Invocation,
    Cz\PHPUnit\MockDB\Stub,
    Throwable;

/**
 * ThrowExceptionStub
 * 
 * @author   czukowski
 * @license  MIT License
 */
class ThrowExceptionStub implements Stub
{
    /**
     * @var  Throwable
     */
    private $exception;

    /**
     * @param  Throwable  $exception
     */
    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @param  Invocation  $invocation
     */
    public function invoke(Invocation $invocation): void
    {
        throw $this->exception;
    }

    /**
     * @return  string
     */
    public function toString(): string
    {
        return 'throw exception';
    }
}
