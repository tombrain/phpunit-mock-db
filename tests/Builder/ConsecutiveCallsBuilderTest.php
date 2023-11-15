<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Builder;

use Cz\PHPUnit\MockDB\Stub,
    Cz\PHPUnit\MockDB\Stub\ConsecutiveCallsStub,
    Cz\PHPUnit\MockDB\Stub\InvokeCallbackStub,
    Cz\PHPUnit\MockDB\Stub\ReturnResultSetStub,
    Cz\PHPUnit\MockDB\Stub\SetAffectedRowsStub,
    Cz\PHPUnit\MockDB\Stub\SetLastInsertIdStub,
    Cz\PHPUnit\MockDB\Stub\ThrowExceptionStub,
    ArrayObject,
    RuntimeException,
    Throwable;

/**
 * ConsecutiveCallsBuilderTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class ConsecutiveCallsBuilderTest extends Testcase
{
    public function testDone(): void
    {
        $builder = $this->createMock(InvocationMocker::class);
        $object = new ConsecutiveCallsBuilder(
            $builder,
            $this->createMock(ConsecutiveCallsStub::class)
        );
        $actual = $object->done();
        static::assertSame($builder, $actual);
    }

    public function testWillMock(): void
    {
        $this->_testWill($this->createMock(Stub::class));
    }

    /**
     * @dataProvider  provideWill
     */
    public function testWill(Stub $argument): void
    {
        $this->_testWill($argument);
    }

    public static function provideWill(): array
    {
        return [
            [new ReturnResultSetStub([])],
            [new SetAffectedRowsStub(0)],
            [new SetLastInsertIdStub(1)],
            [new ThrowExceptionStub(new RuntimeException)],
        ];
    }

    private function _testWill(Stub $argument): void
    {
        $stub = $this->createMock(ConsecutiveCallsStub::class);
        $stub->expects(static::once())
            ->method('addStub')
            ->with($argument);
        $object = new ConsecutiveCallsBuilder(
            $this->createMock(InvocationMocker::class),
            $stub
        );
        $actual = $object->will($argument);
        static::assertSame($object, $actual);
    }
    /**
     * @dataProvider  provideWillInvokeCallback
     */
    public function testWillInvokeCallback(callable $callback): void
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($callback)
        {
            static::assertStub($stub, InvokeCallbackStub::class, 'callback', $callback);
            return TRUE;
        });
        $actual = $object->willInvokeCallback($callback);
        static::assertSame($object, $actual);
    }

    public static function provideWillInvokeCallback(): array
    {
        return [
            [function ()
            {
            }],
        ];
    }

    /**
     * @dataProvider  provideWillReturnResultSet
     */
    public function testWillReturnResultSet(iterable $resultSet): void
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($resultSet)
        {
            static::assertStub($stub, ReturnResultSetStub::class, 'value', $resultSet);
            return TRUE;
        });
        $actual = $object->willReturnResultSet($resultSet);
        static::assertSame($object, $actual);
    }

    public static function provideWillReturnResultSet(): array
    {
        $resultSet1 = [];
        $resultSet2 = [
            ['id' => 1],
            ['id' => 2],
        ];
        return [
            [$resultSet1],
            [new ArrayObject($resultSet2)],
        ];
    }

    /**
     * @dataProvider  provideWillSetAffectedRows
     */
    public function testWillSetAffectedRows(?int $count): void
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($count)
        {
            static::assertStub($stub, SetAffectedRowsStub::class, 'value', $count);
            return TRUE;
        });
        $actual = $object->willSetAffectedRows($count);
        static::assertSame($object, $actual);
    }

    public static function provideWillSetAffectedRows(): array
    {
        return [
            [NULL],
            [0],
            [10],
        ];
    }

    /**
     * @dataProvider  provideWillSetLastInsertId
     */
    public function testWillSetLastInsertId($value): void
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($value)
        {
            static::assertStub($stub, SetLastInsertIdStub::class, 'value', $value);
            return TRUE;
        });
        $actual = $object->willSetLastInsertId($value);
        static::assertSame($object, $actual);
    }

    public static function provideWillSetLastInsertId(): array
    {
        return [
            [NULL],
            [1],
            ['2'],
        ];
    }

    /**
     * @dataProvider  provideWillThrowException
     */
    public function testWillThrowException(Throwable $value): void
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($value)
        {
            static::assertStub($stub, ThrowExceptionStub::class, 'exception', $value);
            return TRUE;
        });
        $actual = $object->willThrowException($value);
        static::assertSame($object, $actual);
    }

    public static function provideWillThrowException(): array
    {
        return [
            [new RuntimeException],
        ];
    }

    /**
     * @param   callable  $checkArgument
     * @return  ConsecutiveCallsBuilder
     */
    private function createMockObjectForWillTest(callable $checkArgument): ConsecutiveCallsBuilder
    {
        $object = $this->getMockBuilder(ConsecutiveCallsBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['will'])
            ->getMock();
        $object->expects(static::once())
            ->method('will')
            ->with(static::callback($checkArgument))
            ->willReturn($object);
        return $object;
    }
}
