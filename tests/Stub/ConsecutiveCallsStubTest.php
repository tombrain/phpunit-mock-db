<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Stub;

use Cz\PHPUnit\MockDB\Invocation,
    Cz\PHPUnit\MockDB\Stub,
    PHPUnit\Framework\MockObject\RuntimeException,
    Exception;

/**
 * ConsecutiveCallsStubTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class ConsecutiveCallsStubTest extends Testcase
{
    /**
     * @dataProvider  provideAddStub
     */
    public function testAddStub(array $initialStackCallableArray, callable $stubCallable): void
    {
        $initialStack = array();
        foreach($initialStackCallableArray as $initialStackCallable)
        {
            $initialStack[] = $initialStackCallable($this);
        }
        $stub = $stubCallable($this);

        $object = new ConsecutiveCallsStub($initialStack);
        $initialStackCount = count($initialStack);
        $object->addStub($stub);
        $stack = static::getObjectPropertyValue($object, 'stack');
        $actual = $stack[$initialStackCount];
        static::assertSame($stub, $actual);
        static::assertCount($initialStackCount + 1, $stack);
    }

    public static function provideAddStub(): array
    {
        return [
            [
                [],
                fn (self $testCase) => $testCase->createMock(Stub::class),
            ],
            [
                [
                    fn (self $testCase) => $testCase->createMock(Stub::class),
                    fn (self $testCase) => $testCase->createMock(Stub::class),
                ],
                fn (self $testCase) => $testCase->createMock(Stub::class),
            ],
        ];
    }

    /**
     * @dataProvider  provideInvoke
     */
    public function testInvoke(array $stackCallable, array $invocations): void
    {
        $stack = array();
        foreach($stackCallable as $t)
        {
            $stack[] = $t($this);
        }
        $object = new ConsecutiveCallsStub($stack);
        foreach ($invocations as $invocationCallable)
        {
            if ($invocationCallable instanceof Exception)
            {
                $this->expectExceptionObject($invocationCallable);
                $invocation = $this->createMock(Invocation::class);
            }
            else
            {
                $invocation = $invocationCallable($this);
            }
            $object->invoke($invocation);
        }
    }

    public static function provideInvoke(): array
    {
        $resultSet1 = [
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
        ];
        return [
            [
                [
                    fn (self $testCase) => $testCase->createStubMock('setResultSet', $resultSet1),
                ],
                [
                    fn (self $testCase) => $testCase->createInvocationExpectMethod('setResultSet', $resultSet1),
                ],
            ],
            [
                [
                    fn (self $testCase) => $testCase->createStubMock('setLastInsertId', 1),
                    fn (self $testCase) => $testCase->createStubMock('setLastInsertId', 2),
                ],
                [
                    fn (self $testCase) => $testCase->createInvocationExpectMethod('setLastInsertId', 1),
                    fn (self $testCase) => $testCase->createInvocationExpectMethod('setLastInsertId', 2),
                    new RuntimeException('No more items left in stack'),
                ],
            ],
        ];
    }

    private function createStubMock(string $method, $argument): Stub
    {
        $stub = $this->createMock(Stub::class);
        $stub->expects(static::once())
            ->method('invoke')
            ->willReturnCallback(function (Invocation $invocation) use ($method, $argument) {
                $invocation->$method($argument);
            });
        return $stub;
    }
}
