<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\MockObject;

use Cz\PHPUnit\MockDB\Invocation as BaseInvocation,
    Cz\PHPUnit\MockDB\Testcase,
    PHPUnit\Framework\MockObject\Invocation as MockObjectInvocation,
    PHPUnit\Framework\MockObject\Rule\InvocationOrder;

/**
 * MatcherInvocationWrapperTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class MatcherInvocationWrapperTest extends Testcase
{
    /**
     * @dataProvider  provideInvoked
     */
    public function testInvoked(callable $baseInvocationCallable, callable $wrappedInvocationCallable): void
    {
        $baseInvocation = $baseInvocationCallable($this);
        $wrappedInvocation = $wrappedInvocationCallable($this);

        $invocation = $this->createInvocationOrder();
        $object = $this->createObject(
            $invocation,
            $this->createContainer($baseInvocation, $wrappedInvocation)
        );
        $object->invoked($baseInvocation);
        static::assertSame(1, $invocation->numberOfInvocations());
    }

    public static function provideInvoked(): array
    {
        return [
            [
                fn (self $testCase) => $testCase->createMock(BaseInvocation::class),
                fn (self $testCase) => $testCase->createMockObjectInvocation(),
            ],
        ];
    }

    /**
     * @dataProvider  provideMatches
     */
    public function testMatches(callable $baseInvocationCallable, callable $wrappedInvocationCallable, bool $expected): void
    {
        $baseInvocation = $baseInvocationCallable($this);
        $wrappedInvocation = $wrappedInvocationCallable($this);

        $invocation = $this->createInvocationOrder();
        $invocation->expects(static::once())
            ->method('matches')
            ->with($wrappedInvocation)
            ->willReturn($expected);
        $object = $this->createObject(
            $invocation,
            $this->createContainer($baseInvocation, $wrappedInvocation)
        );
        $actual = $object->matches($baseInvocation);
        static::assertSame($expected, $actual);
    }

    public static function provideMatches(): array
    {
        return [
            [
                fn (self $testCase) => $testCase->createMock(BaseInvocation::class),
                fn (self $testCase) => $testCase->createMockObjectInvocation(),
                TRUE,
            ],
            [
                fn (self $testCase) => $testCase->createMock(BaseInvocation::class),
                fn (self $testCase) => $testCase->createMockObjectInvocation(),
                FALSE,
            ],
        ];
    }

    /**
     * @test
     */
    public function testVerify(): void
    {
        $invocation = $this->createInvocationOrder();
        $invocation->expects(static::once())
            ->method('verify');
        $object = $this->createObject($invocation);
        $object->verify();
    }

    /**
     * @dataProvider  provideIsAnyInvokedCount
     */
    public function testIsAnyInvokedCount(callable $invocationCallable, bool $expected): void
    {
        $invocation = $invocationCallable($this);
        $object = $this->createObject($invocation);
        $actual = $object->isAnyInvokedCount();
        static::assertSame($expected, $actual);
    }

    public static function provideIsAnyInvokedCount(): array
    {
        return [
            [
                fn (self $testCase) => $testCase->createMock(InvocationOrder::class),
                FALSE,
            ],
            [
                fn (self $testCase) => static::once(),
                FALSE,
            ],
            [
                fn (self $testCase) => static::never(),
                FALSE,
            ],
            [
                fn (self $testCase) => static::any(),
                TRUE,
            ],
        ];
    }

    /**
     * @dataProvider  provideIsNeverInvokedCount
     */
    public function testIsNeverInvokedCount(callable $invocationCallable, bool $expected): void
    {
        $invocation = $invocationCallable($this);
        $object = $this->createObject($invocation);
        $actual = $object->isNeverInvokedCount();
        static::assertSame($expected, $actual);
    }

    public static function provideIsNeverInvokedCount(): array
    {
        return [
            [
                fn (self $testCase) => $testCase->createMock(InvocationOrder::class),
                FALSE,
            ],
            [
                fn (self $testCase) => static::once(),
                FALSE,
            ],
            [
                fn (self $testCase) => static::any(),
                FALSE,
            ],
            [
                fn (self $testCase) => static::never(),
                TRUE,
            ],
            [
                fn (self $testCase) => static::exactly(0),
                TRUE,
            ],
        ];
    }

    private function createContainer(
        BaseInvocation $baseInvocation,
        MockObjectInvocation $wrappedInvocation
    ): InvocationsContainer
    {
        $object = $this->createMock(InvocationsContainer::class);
        $object->expects(static::once())
            ->method('getMockObjectInvocation')
            ->with($baseInvocation)
            ->willReturn($wrappedInvocation);
        return $object;
    }

    private function createInvocationOrder(): InvocationOrder
    {
        return $this->getMockForAbstractClass(InvocationOrder::class);
    }

    private function createMockObjectInvocation(): MockObjectInvocation
    {
        return new MockObjectInvocation('', '', [], '', $this);
    }

    private function createObject(
        InvocationOrder $invocation,
        InvocationsContainer $container = NULL
    ): MatcherInvocationWrapper
    {
        if ($container === NULL) {
            $container = $this->createMock(InvocationsContainer::class);
        }
        return new MatcherInvocationWrapper($invocation, $container);
    }
}
