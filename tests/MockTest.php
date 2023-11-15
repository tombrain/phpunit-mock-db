<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use Cz\PHPUnit\MockDB\Builder\InvocationMocker as InvocationMockerBuilder,
    Cz\PHPUnit\MockDB\Invocation\QueryInvocation,
    Cz\PHPUnit\MockDB\Matcher\Invocation as MatcherInvocation,
    Cz\PHPUnit\MockDB\Matcher\RecordedInvocation,
    Cz\PHPUnit\MockDB\MockObject\InvocationsContainer,
    Cz\PHPUnit\MockDB\MockObject\MatcherInvocationWrapper,
    LogicException,
    PHPUnit\Framework\Exception as FrameworkException,
    PHPUnit\Framework\MockObject\Rule\InvocationOrder;

/**
 * MockTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class MockTest extends Testcase
{
    /**
     * @dataProvider  provideExpects
     */
    public function testExpects(callable $callable): void
    {
        list($argument, $expected) = $callable($this);

        // Using test double classes to avoid having to mock methods named 'expects'.
        $invocationMocker = new Doubles\InvocationMockerDouble;
        $container = new InvocationsContainer;
        $object = new Doubles\MockDouble($invocationMocker, $container);

        $this->expectExceptionFromArgument($expected);
        $builder = $object->expects($argument);

        static::assertInstanceOf(InvocationMockerBuilder::class, $builder);
        $actual = $invocationMocker->matcher;
        if (is_callable($expected))
        {
            call_user_func($expected, $actual, $container);
        }
        else
        {
            static::assertSame($expected, $actual);
        }
    }

    public static function provideExpects(): array
    {
        return [
            [fn (self $testCase) => static::createExpectsTestCaseException(NULL)],
            [fn (self $testCase) => static::createExpectsTestCaseException(3.14)],
            [fn (self $testCase) => static::createExpectsTestCaseException('foo')],
            [fn (self $testCase) => static::createExpectsTestCaseMockDbInvocationMatcher($testCase->createMock(RecordedInvocation::class))],
            [fn (self $testCase) => static::createExpectsTestCaseWrappedInvocationMatcher(static::any())],
            [fn (self $testCase) => static::createExpectsTestCaseWrappedInvocationMatcher(static::once())],
            [fn (self $testCase) => static::createExpectsTestCaseWrappedInvocationMatcher(static::never())],
        ];
    }

    private static function createExpectsTestCaseException($value): array
    {
        return [$value, new FrameworkException];
    }

    private static function createExpectsTestCaseMockDbInvocationMatcher(MatcherInvocation $matcher): array
    {
        return [$matcher, $matcher];
    }

    private static function createExpectsTestCaseWrappedInvocationMatcher(InvocationOrder $matcher): array
    {
        return [
            $matcher,
            function ($actual, $container) use ($matcher)
            {
                static::assertInstanceOf(MatcherInvocationWrapper::class, $actual);
                static::assertSame($container, static::getObjectPropertyValue($actual, 'container'));
                static::assertSame($matcher, static::getObjectPropertyValue($actual, 'invocation'));
            }
        ];
    }

    /**
     * @dataProvider  provideInvoke
     */
    public function testInvoke(array $arguments, $expected): void
    {
        $invocationMocker = new Doubles\InvocationMockerDouble;
        $invocationMocker->setRequireMatch(FALSE);
        $object = new Doubles\MockDouble($invocationMocker);

        $this->expectExceptionFromArgument($expected);
        $actual = $object->invoke(...$arguments);
        static::assertSame($invocationMocker->invoked, $actual);
        if (is_callable($expected))
        {
            call_user_func($expected, $actual);
        }
        else
        {
            static::assertSame($expected, $actual);
        }
    }

    public static function provideInvoke(): array
    {
        return [
            static::createInvokeTestCaseString('SELECT * FROM `t`', []),
            static::createInvokeTestCaseString('SELECT * FROM `t` WHERE `c` = ?', [1]),
            static::createInvokeTestCaseInvocationInstance('SELECT * FROM `t`'),
            static::createInvokeTestCaseInvocationInstanceAnd2ndArgument('SELECT * FROM `t`', []),
        ];
    }

    private static function createInvokeTestCaseString(string $query, array $parameters): array
    {
        return [
            [$query, $parameters],
            function ($actual) use ($query, $parameters)
            {
                static::assertInstanceOf(QueryInvocation::class, $actual);
                static::assertSame($query, $actual->getQuery());
                static::assertSame($parameters, $actual->getParameters());
            }
        ];
    }

    private static function createInvokeTestCaseInvocationInstance(string $query): array
    {
        $invocation = new QueryInvocation($query);
        return [[$invocation], $invocation];
    }

    private static function createInvokeTestCaseInvocationInstanceAnd2ndArgument(string $query, array $parameters): array
    {
        return [
            [new QueryInvocation($query), $parameters],
            new LogicException,
        ];
    }

    /**
     * @dataProvider  provideRequireMatch
     */
    public function testGetRequireMatch(bool $value): void
    {
        $invocationMocker = new Doubles\InvocationMockerDouble;
        $invocationMocker->setRequireMatch($value);
        $object = new Doubles\MockDouble($invocationMocker);

        $actual = $object->getRequireMatch();
        static::assertSame($value, $actual);
    }

    /**
     * @dataProvider  provideRequireMatch
     */
    public static function testSetRequireMatch(bool $value): void
    {
        $invocationMocker = new Doubles\InvocationMockerDouble;
        $object = new Doubles\MockDouble($invocationMocker);

        $actual = $object->setRequireMatch($value);
        static::assertSame($object, $actual);
        static::assertSame($value, $invocationMocker->getRequireMatch());
    }

    public static function provideRequireMatch(): array
    {
        return [
            [TRUE],
            [FALSE],
        ];
    }
}
