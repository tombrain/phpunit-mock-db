<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

/**
 * Invokable
 * 
 * @author   czukowski
 * @license  MIT License
 */
interface Invokable
{
    /**
     * @param  Invocation  $invocation
     */
    function invoke(Invocation $invocation): void;

    /**
     * @param   Invocation  $invocation
     * @return  boolean
     */
    function matches(Invocation $invocation): bool;
}
