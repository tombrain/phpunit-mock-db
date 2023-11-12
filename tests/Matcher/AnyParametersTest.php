<?php declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Matcher;

use Cz\PHPUnit\MockDB\Invocation as BaseInvocation,
    Cz\PHPUnit\MockDB\Testcase;

/**
 * AnyParametersTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class AnyParametersTest extends Testcase
{
    public function testToString(): void
    {
        $object = $this->createObject();
        $actual = $object->toString();
        static::assertSame('with any parameters', $actual);
    }

    /**
     * @dataProvider  provideMatches
     */
    public function testMatches(array $parameters, bool $expected): void
    {
        $object = $this->createObject();
        $invocation = $this->createMock(BaseInvocation::class);
        $invocation->expects(static::once())
            ->method('getParameters')
            ->willReturn($parameters);
        $actual = $object->matches($invocation);
        static::assertSame($expected, $actual);
    }

    public static function provideMatches(): array
    {
        return [
            [[], FALSE],
            [[1], TRUE],
        ];
    }

    public function testInvoked(): void
    {
        $object = $this->createObject();
        $invocation = $this->createMock(BaseInvocation::class);
        $actual = $object->invoked($invocation);
        static::assertNull($actual);
    }

    public function testVerify(): void
    {
        $object = $this->createObject();
        $actual = $object->verify();
        static::assertNull($actual);
    }

    private function createObject(): AnyParameters
    {
        return new AnyParameters;
    }
}
