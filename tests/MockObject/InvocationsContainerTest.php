<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\MockObject;

use Cz\PHPUnit\MockDB\Invocation as BaseInvocation,
    Cz\PHPUnit\MockDB\Testcase,
    PHPUnit\Framework\MockObject\Invocation as MockObjectInvocation;

/**
 * InvocationsContainerTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class InvocationsContainerTest extends Testcase
{
    /**
     * @dataProvider  provideGetMockObjectInvocation
     */
    public function testGetMockObjectInvocation(callable $callable): void
    {
        $invocations = $callable($this);
        $invocations = $invocations[0];
        
        $object = new InvocationsContainer;
        $previousInvocations = [];
        foreach ($invocations as $invocation) {
            $wrappedInvocation = $object->getMockObjectInvocation($invocation);
            static::assertInstanceOf(MockObjectInvocation::class, $wrappedInvocation);
            $sameWrappedInvocation = $object->getMockObjectInvocation($invocation);
            static::assertSame($wrappedInvocation, $sameWrappedInvocation);
            foreach ($previousInvocations as $previousWrappedInvocation) {
                static::assertNotSame($wrappedInvocation, $previousWrappedInvocation);
            }
            $previousInvocations[] = $wrappedInvocation;
        }
    }

    public static function provideGetMockObjectInvocation(): array
    {
        return [
            [ fn (self $testCase) => $testCase->createGetMockObjectInvocationTestCase(1) ],
            [ fn (self $testCase) => $testCase->createGetMockObjectInvocationTestCase(5) ],
        ];
    }

    private function createGetMockObjectInvocationTestCase(int $invocationsCount): array
    {
        return [
            array_map(
                function () {
                    return $this->createMock(BaseInvocation::class);
                },
                array_fill(0, $invocationsCount, NULL)
            ),
        ];
    }
}
