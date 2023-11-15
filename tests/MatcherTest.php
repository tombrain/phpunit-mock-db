<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use Cz\PHPUnit\MockDB\Matcher\AnyParameters,
    Cz\PHPUnit\MockDB\Matcher\ParametersMatcher,
    Cz\PHPUnit\MockDB\Matcher\ParametersMatch,
    Cz\PHPUnit\MockDB\Matcher\QueryMatcher,
    Cz\PHPUnit\MockDB\Matcher\RecordedInvocation,
    Cz\PHPUnit\MockDB\MockObject\InvocationsContainer,
    Cz\PHPUnit\MockDB\MockObject\MatcherInvocationWrapper,
    Cz\PHPUnit\SQL\EqualsSQLQueriesConstraint,
    PHPUnit\Framework\Constraint\Constraint,
    PHPUnit\Framework\ExpectationFailedException,
    PHPUnit\Framework\MockObject\Rule\InvocationOrder,
    Throwable;

/**
 * MatcherTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class MatcherTest extends Testcase
{
    /**
     * @dataProvider  provideHasMatchers
     */
    public function testHasMatchers(callable $callable, bool $expected): void
    {
        $invocationMatcher = $callable($this);
        $object = new Matcher($invocationMatcher);
        $actual = $object->hasMatchers();
        static::assertSame($expected, $actual);
    }

    public static function provideHasMatchers(): array
    {
        return [
            [fn (self $testCase) => $testCase->createMatcherInvocationMock(false), true],
            [fn (self $testCase) => $testCase->createMatcherInvocationMock(true), false],
            [fn (self $testCase) => $testCase->createMatcherInvocationWrapper(static::any()), false],
            [fn (self $testCase) => $testCase->createMatcherInvocationWrapper(static::once()), true],
            [fn (self $testCase) => $testCase->createMatcherInvocationWrapper(static::never()), true],
        ];
    }

    private function createMatcherInvocationMock(bool $isAnyInvokedCount): RecordedInvocation
    {
        $mock = $this->createMock(RecordedInvocation::class);
        $mock->expects(static::once())
            ->method('isAnyInvokedCount')
            ->willReturn($isAnyInvokedCount);
        return $mock;
    }

    private function createMatcherInvocationWrapper(InvocationOrder $invocationMatcher): MatcherInvocationWrapper
    {
        return new MatcherInvocationWrapper($invocationMatcher, new InvocationsContainer);
    }

    /**
     * @dataProvider  provideQueryMatcher
     */
    public function testQueryMatcher(callable $callable): void
    {
        $constraint = $callable($this);
        $object = new Matcher($this->createMock(RecordedInvocation::class));
        static::assertFalse($object->hasQueryMatcher());
        $matcher = new QueryMatcher($constraint);
        $object->setQueryMatcher($matcher);
        static::assertSame($matcher, $object->getQueryMatcher());
        static::assertTrue($object->hasQueryMatcher());
        $this->expectException('RuntimeException');
        $object->setQueryMatcher($matcher);
    }

    public static function provideQueryMatcher(): array
    {
        return [
            [fn (self $testCase) => $testCase->createMock(Constraint::class)],
            [fn ($dummy) => static::stringStartsWith('SELECT')],
            [fn ($dummy) => new EqualsSQLQueriesConstraint('SELECT * FROM `t`')],
        ];
    }

    /**
     * @dataProvider  provideParametersMatcher
     */
    public function testParametersMatcher(callable $callable): void
    {
        $rule = $callable($this);
        $object = new Matcher($this->createMock(RecordedInvocation::class));
        static::assertFalse($object->hasParametersMatcher());
        $object->setParametersMatcher($rule);
        static::assertSame($rule, $object->getParametersMatcher());
        static::assertTrue($object->hasParametersMatcher());
        $this->expectException('RuntimeException');
        $object->setParametersMatcher($rule);
    }

    public static function provideParametersMatcher(): array
    {
        return [
            [fn (self $testCase) => $testCase->createMock(ParametersMatcher::class)],
            [fn ($dummy) => new ParametersMatch([1, 2])],
            [fn ($dummy) => new AnyParameters],
        ];
    }

    /**
     * @dataProvider  provideInvoked
     */
    public function testInvoked(callable $callable): void
    {
        list($invocation, $invocationMatcherSetup, $stubSetup) = $callable($this);
        $invocationMatcher = $this->createMock(RecordedInvocation::class);
        $this->setupMockObject($invocationMatcher, $invocationMatcherSetup);

        $object = new Matcher($invocationMatcher);
        if ($stubSetup !== null)
        {
            $stub = $this->createMock(Stub::class);
            $this->setupMockObject($stub, $stubSetup);
            $object->setStub($stub);
        }
        $actual = $object->invoked($invocation);
        static::assertNull($actual);
    }

    public static function provideInvoked(): array
    {
        return [
            [fn (self $testCase) => $testCase->createInvokedTestCase(false)],
            [fn (self $testCase) => $testCase->createInvokedTestCase(true)],
        ];
    }

    private function createInvokedTestCase(bool $withStub): array
    {
        $invocation = $this->createInvocationMock();
        return [
            $invocation,
            [
                'invoked' => [
                    [
                        'expects' => static::once(),
                        'with' => [$invocation],
                    ],
                ],
            ],
            $withStub
                ? null
                : [
                    'invoke' => [
                        [
                            'expects' => static::once(),
                            'with' => [$invocation],
                        ],
                    ],
                ],
        ];
    }

    /**
     * @dataProvider  provideMatches
     */
    public function testMatches(callable $callable): void
    {
        list($invocation, $invocationMatcherSetup, $queryMatcherSetup, $expected) = $callable($this);

        $invocationMatcher = $this->createMock(RecordedInvocation::class);
        $this->setupMockObject($invocationMatcher, $invocationMatcherSetup);

        $object = new Matcher($invocationMatcher);
        if ($queryMatcherSetup !== null)
        {
            $queryMatcher = $this->createMock(QueryMatcher::class);
            $this->setupMockObject($queryMatcher, $queryMatcherSetup);
            $object->setQueryMatcher($queryMatcher);
        }
        $actual = $object->matches($invocation);
        static::assertSame($expected, $actual);
    }

    public static function provideMatches(): array
    {
        return [
            [fn (self $testCase) => $testCase->createMatchesTestCase(true, null, true)],
            [fn (self $testCase) => $testCase->createMatchesTestCase(true, true, true)],
            [fn (self $testCase) => $testCase->createMatchesTestCase(true, false, false)],
            [fn (self $testCase) => $testCase->createMatchesTestCase(false, null, false)],
            [fn (self $testCase) => $testCase->createMatchesTestCase(false, true, false)],
            [fn (self $testCase) => $testCase->createMatchesTestCase(false, false, false)],
        ];
    }

    private function createMatchesTestCase(
        bool $matchesInvocationMatcher,
        ?bool $matchesQueryMatcher,
        bool $expected
    ): array
    {
        $invocation = $this->createInvocationMock();
        return [
            $invocation,
            [
                'matches' => [
                    [
                        'expects' => static::once(),
                        'with' => [$invocation],
                        'will' => static::returnValue($matchesInvocationMatcher),
                    ]
                ],
            ],
            $matchesQueryMatcher === null
                ? null
                : [
                    'matches' => [
                        [
                            'expects' => $matchesInvocationMatcher ? static::once() : static::never(),
                            'with' => [$invocation],
                            'will' => static::returnValue($matchesQueryMatcher),
                        ],
                    ],
                ],
            $expected,
        ];
    }

    /**
     * @dataProvider  provideToString
     */
    public function testToString(
        ?QueryMatcher $queryMatcher,
        ?ParametersMatcher $parametersRule,
        string $expected
    ): void
    {
        $invocationMatcher = $this->createMock(RecordedInvocation::class);
        $this->setupMockObject(
            $invocationMatcher,
            [
                'toString' => [
                    [
                        'expects' => static::once(),
                        'will' => static::returnValue('an invocation'),
                    ]
                ],
            ]
        );

        $object = new Matcher($invocationMatcher);
        if ($queryMatcher !== null)
        {
            $object->setQueryMatcher($queryMatcher);
        }
        if ($parametersRule !== null)
        {
            $object->setParametersMatcher($parametersRule);
        }

        $actual = $object->toString();
        static::assertSame($expected, $actual);
    }

    public static function provideToString(): array
    {
        return [
            [
                null,
                null,
                'an invocation',
            ],
            [
                new QueryMatcher(static::anything()),
                null,
                'an invocation where query is anything',
            ],
            [
                new QueryMatcher(static::equalTo('SELECT * FROM `t1`')),
                null,
                "an invocation where query is equal to 'SELECT * FROM `t1`'",
            ],
            [
                null,
                new AnyParameters,
                "an invocation with any parameters",
            ],
            [
                new QueryMatcher(static::equalTo('SELECT * FROM `t1` WHERE `c` = ?')),
                new ParametersMatch([1]),
                "an invocation where query is equal to 'SELECT * FROM `t1` WHERE `c` = ?' with parameter 1 is identical to 1",
            ],
        ];
    }

    private function createInvocationMock(): Invocation
    {
        return $this->createMock(Invocation::class);
    }

    private function setupMockObject($object, array $setup): void
    {
        foreach ($setup as $method => $invocations)
        {
            foreach ($invocations as $invocation)
            {
                $im = $object->expects($invocation['expects'])
                    ->method($method);
                if (isset($invocation['with']))
                {
                    $im->with(...$invocation['with']);
                }
                if (isset($invocation['will']))
                {
                    $im->will($invocation['will']);
                }
            }
        }
    }
}
