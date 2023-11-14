<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use Cz\PHPUnit\MockDB\Builder\InvocationMocker as InvocationMockerBuilder,
    Cz\PHPUnit\MockDB\Matcher\Invocation as MatcherInvocation,
    Cz\PHPUnit\MockDB\Matcher\RecordedInvocation,
    PHPUnit\Framework\ExpectationFailedException,
    ReflectionProperty;

/**
 * InvocationMockerTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class InvocationMockerTest extends Testcase
{
    /**
     * @dataProvider  provideAddMatcher
     */
    public function testAddMatcher(array $matchers): void
    {
        $object = $this->createObject();

        $expArr = array();
        foreach ($matchers as $callable) {
            $matcher = $callable($this);
            $expArr[] = $matcher;
            $object->addMatcher($matcher);
        }
        $actual = static::getObjectPropertyValue($object, 'matchers');
        static::assertSame($expArr, $actual);
    }

    public static function provideAddMatcher(): array
    {
        return [
            [
                [
                    fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
                ],
            ],
            [
                [
                    fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
                    fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
                ],
            ],
        ];
    }

    /**
     * @dataProvider  provideHasMatchers
     */
    public function testHasMatchers(array $matchers, bool $expected): void
    {
        $object = $this->createObject();
        foreach ($matchers as $callable) {
            $matcher = $callable($this);
            $object->addMatcher($matcher);
        }
        $actual = $object->hasMatchers();
        static::assertSame($expected, $actual);
    }

    public static function provideHasMatchers(): array
    {
        return [
            [
                [],
                FALSE,
            ],
            [
                [
                    fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
                ],
                TRUE,
            ],
            [
                [
                    fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
                    fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
                ],
                TRUE,
            ],
        ];
    }

    /**
     * @dataProvider  provideRequireMatch
     */
    public function testGetRequireMatch(bool $value): void
    {
        $object = $this->createObject();
        static::assertTrue($object->getRequireMatch());
        $requireMatch = new ReflectionProperty(InvocationMocker::class, 'requireMatch');
        $requireMatch->setAccessible(TRUE);
        $requireMatch->setValue($object, $value);
        $actual = $object->getRequireMatch();
        static::assertSame($value, $actual);
    }


    /**
     * @dataProvider  provideRequireMatch
     */
    public function testSetRequireMatch(bool $value): void
    {
        $object = $this->createObject();
        $object->setRequireMatch($value);
        $actual = static::getObjectPropertyValue($object, 'requireMatch');
        static::assertSame($value, $actual);
    }

    public static function provideRequireMatch(): array
    {
        return [
            [TRUE],
            [FALSE],
        ];
    }

    /**
     * @dataProvider  provideExpects
     */
    public function testExpects($callable): void
    {
        $matcher = $callable($this);
        $object = $this->createObject();
        $builder = $object->expects($matcher);
        static::assertInstanceOf(InvocationMockerBuilder::class, $builder);
        // Risky part, actually testing implementation of other classes...
        $matcherWrapper = static::getObjectPropertyValue($builder, 'matcher');
        static::assertInstanceOf(Matcher::class, $matcherWrapper);
        $actual = static::getObjectPropertyValue($matcherWrapper, 'invocationMatcher');
        static::assertSame($matcher, $actual);
    }

    public static function provideExpects(): array
    {
        return [
            [
                fn (self $testCase) => $testCase->createMock(RecordedInvocation::class),
            ],
        ];
    }

    /**
     * @dataProvider  provideInvoke
     */
    public function testInvoke(callable $callable): void
    {
        list($requireMatch, $matchers, $invocation, $expected) = $callable($this);

        $object = $this->createObject($matchers);
        $object->setRequireMatch($requireMatch);
        $this->expectExceptionFromArgument($expected);
        $actual = $object->invoke($invocation);
        static::assertSame($expected, $actual);
    }

    public static function provideInvoke(): array
    {
        return [
            [ fn (self $testCase) => $testCase->createInvokeTestCase(TRUE, [TRUE], NULL) ],
            [ fn (self $testCase) => $testCase->createInvokeTestCase(TRUE, [TRUE, TRUE], NULL) ],
            [ fn (self $testCase) => $testCase->createInvokeTestCase(TRUE, [FALSE, TRUE], NULL) ],
            [ fn (self $testCase) => $testCase->createInvokeTestCase(TRUE, [FALSE, FALSE], new ExpectationFailedException('')) ],
            [ fn (self $testCase) => $testCase->createInvokeTestCase(TRUE, [FALSE], new ExpectationFailedException('')) ],
            [ fn (self $testCase) => $testCase->createInvokeTestCase(FALSE, [FALSE, FALSE], NULL) ],
            [ fn (self $testCase) => $testCase->createInvokeTestCase(FALSE, [FALSE], NULL) ],
        ];
    }

    private function createInvokeTestCase(
        bool $requireMatch,
        array $matchersWillMatch,
        ?ExpectationFailedException $expected
    ): array
    {
        $invocation = $this->createMock(Invocation::class);
        return [
            $requireMatch,
            array_map(
                function ($willMatch) use ($invocation) {
                    $object = $this->createMock(MatcherInvocation::class);
                    $object->expects(static::once())
                        ->method('matches')
                        ->with($invocation)
                        ->willReturn($willMatch);
                    $object->expects($willMatch ? static::once() : static::never())
                        ->method('invoked')
                        ->with($invocation);
                    return $object;
                },
                $matchersWillMatch
            ),
            $invocation,
            $expected,
        ];
    }

    /**
     * @dataProvider  provideMatches
     */
    public function testMatches(callable $callable): void
    {
        list($matchers, $invocation, $expected) = $callable($this);

        $object = $this->createObject($matchers);
        $actual = $object->matches($invocation);
        static::assertSame($expected, $actual);
    }

    public static function provideMatches(): array
    {
        return [
            [ fn (self $testCase) => $testCase->createMatchesTestCase([TRUE], TRUE) ],
            [ fn (self $testCase) => $testCase->createMatchesTestCase([TRUE, TRUE], TRUE) ],
            [ fn (self $testCase) => $testCase->createMatchesTestCase([FALSE], FALSE) ],
            [ fn (self $testCase) => $testCase->createMatchesTestCase([TRUE, FALSE], FALSE) ],
            [ fn (self $testCase) => $testCase->createMatchesTestCase([FALSE, TRUE], FALSE) ],
            [ fn (self $testCase) => $testCase->createMatchesTestCase([FALSE, FALSE], FALSE) ],
        ];
    }

    private function createMatchesTestCase(array $matchersWillMatch, bool $expected): array
    {
        $invocation = $this->createMock(Invocation::class);
        $willSoFar = TRUE;
        return [
            array_map(
                function ($willMatch) use ($invocation, & $willSoFar) {
                    $object = $this->createMock(MatcherInvocation::class);
                    $object->expects($willSoFar ? static::once() : static::never())
                        ->method('matches')
                        ->with($invocation)
                        ->willReturn($willMatch);
                    $willSoFar &= $willMatch;  // Set to FALSE after first non-match.
                    return $object;
                },
                $matchersWillMatch
            ),
            $invocation,
            $expected,
        ];
    }


    private function createObject(array $matchers = []): InvocationMocker
    {
        $object = new InvocationMocker;
        foreach ($matchers as $matcher) {
            $object->addMatcher($matcher);
        }
        return $object;
    }
}
