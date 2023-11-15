<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Builder;

use Cz\PHPUnit\MockDB,
    Cz\PHPUnit\MockDB\Stub;

/**
 * Testcase
 * 
 * @author   czukowski
 * @license  MIT License
 */
abstract class Testcase extends MockDB\Testcase
{
    /**
     * @param  Stub    $stub
     * @param  array   $expectedItems
     * @param  string  $expectedInstanceOf
     * @param  string  $attribute
     */
    protected static function assertConsecutiveStubs(
        Stub $stub,
        array $expectedItems,
        string $expectedInstanceOf,
        string $attribute
    ): void
    {
        static::assertInstanceOf(Stub\ConsecutiveCallsStub::class, $stub);
        $stack = static::getObjectPropertyValue($stub, 'stack');
        static::assertIsArray($stack);
        static::assertCount(count($expectedItems), $stack);
        for ($i = 0; $i < count($expectedItems); $i++)
        {
            static::assertStub($stack[$i], $expectedInstanceOf, $attribute, $expectedItems[$i]);
        }
    }

    /**
     * @param  Stub    $stub
     * @param  string  $expectedInstanceOf
     * @param  string  $attribute
     * @param  mixed   $expectedAttribute
     */
    protected static function assertStub(
        Stub $stub,
        string $expectedInstanceOf,
        string $attribute,
        $expectedAttribute
    ): void
    {
        static::assertInstanceOf($expectedInstanceOf, $stub);
        $actual = static::getObjectPropertyValue($stub, $attribute);
        static::assertSame($expectedAttribute, $actual);
    }
}
