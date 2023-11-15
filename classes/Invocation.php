<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

/**
 * Invocation
 * 
 * @author   czukowski
 * @license  MIT License
 */
interface Invocation
{
    /**
     * @return  string
     */
    function getQuery(): string;

    /**
     * @return  array
     */
    function getParameters(): array;

    /**
     * @param  array  $parameters
     */
    function setParameters(array $parameters): void;

    /**
     * @return  integer|null
     */
    function getAffectedRows(): ?int;

    /**
     * @param  integer  $count
     */
    function setAffectedRows(int $count): void;

    /**
     * @return  mixed|null
     */
    function getLastInsertId();

    /**
     * @param  mixed  $value
     */
    function setLastInsertId($value): void;

    /**
     * @return  iterable|null
     */
    function getResultSet(): ?iterable;

    /**
     * @param  iterable  $result
     */
    function setResultSet(iterable $result): void;
}
