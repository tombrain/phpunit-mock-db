<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use Exception,
    PHPUnit\Framework\TestCase as FrameworkTestCase,
    ReflectionProperty;

use PHPUnit\Framework\MockObject\Rule\InvokedAtIndex as InvokedAtIndexMatcher;

/**
 * Testcase
 * 
 * @author   czukowski
 * @license  MIT License
 */
abstract class Testcase extends FrameworkTestCase
{
    /**
     * @param  mixed  $expected
     */
    public function expectExceptionFromArgument($expected): void
    {
        if ($expected instanceof Exception) {
            $this->expectException(get_class($expected));
        }
    }

    /**
     * @param   object  $object
     * @param   string  $name
     * @return  mixed
     */
    protected static function getObjectPropertyValue($object, string $name)
    {
        $property = new ReflectionProperty($object, $name);
        $property->setAccessible(TRUE);
        return $property->getValue($object);
    }

    public static function at_hidingDeprecatedWarning(int $index): InvokedAtIndexMatcher
    {
        $stack = debug_backtrace();

        while (!empty($stack)) {
            $frame = array_pop($stack);
        }

        return new InvokedAtIndexMatcher($index);
    }
}
