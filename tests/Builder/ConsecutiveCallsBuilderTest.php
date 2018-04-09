<?php
namespace Cz\PHPUnit\MockDB\Builder;

use Cz\PHPUnit\MockDB\Stub,
    Cz\PHPUnit\MockDB\Stub\ConsecutiveCallsStub,
    Cz\PHPUnit\MockDB\Stub\InvokeCallbackStub,
    Cz\PHPUnit\MockDB\Stub\ReturnResultSetStub,
    Cz\PHPUnit\MockDB\Stub\SetAffectedRowsStub,
    Cz\PHPUnit\MockDB\Stub\SetLastInsertIdStub,
    Cz\PHPUnit\MockDB\Stub\ThrowExceptionStub,
    ArrayObject,
    RuntimeException;

/**
 * ConsecutiveCallsBuilderTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class ConsecutiveCallsBuilderTest extends Testcase
{
    /**
     * @dataProvider  provideDone
     */
    public function testDone($builder)
    {
        $object = new ConsecutiveCallsBuilder(
            $builder,
            $this->createMock(ConsecutiveCallsStub::class)
        );
        $actual = $object->done();
        $this->assertSame($builder, $actual);
    }

    public function provideDone()
    {
        return [
            [$this->createMock(InvocationMocker::class)],
        ];
    }

    /**
     * @dataProvider  provideWill
     */
    public function testWill($argument)
    {
        $stub = $this->createMock(ConsecutiveCallsStub::class);
        $stub->expects($this->once())
            ->method('addStub')
            ->with($argument);
        $object = new ConsecutiveCallsBuilder(
            $this->createMock(InvocationMocker::class),
            $stub
        );
        $actual = $object->will($argument);
        $this->assertSame($object, $actual);
    }

    public function provideWill()
    {
        return [
            [$this->createMock(Stub::class)],
            [new ReturnResultSetStub([])],
            [new SetAffectedRowsStub(0)],
            [new SetLastInsertIdStub(1)],
            [new ThrowExceptionStub(new RuntimeException)],
        ];
    }

    /**
     * @dataProvider  provideWillInvokeCallback
     */
    public function testWillInvokeCallback($callback)
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($callback) {
            $this->assertStub($stub, InvokeCallbackStub::class, 'callback', $callback);
            return TRUE;
        });
        $actual = $object->willInvokeCallback($callback);
        $this->assertSame($object, $actual);
    }

    public function provideWillInvokeCallback()
    {
        return [
            [function () {}],
        ];
    }

    /**
     * @dataProvider  provideWillReturnResultSet
     */
    public function testWillReturnResultSet($resultSet)
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($resultSet) {
            $this->assertStub($stub, ReturnResultSetStub::class, 'value', $resultSet);
            return TRUE;
        });
        $actual = $object->willReturnResultSet($resultSet);
        $this->assertSame($object, $actual);
    }

    public function provideWillReturnResultSet()
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
    public function testWillSetAffectedRows($count)
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($count) {
            $this->assertStub($stub, SetAffectedRowsStub::class, 'value', $count);
            return TRUE;
        });
        $actual = $object->willSetAffectedRows($count);
        $this->assertSame($object, $actual);
    }

    public function provideWillSetAffectedRows()
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
    public function testWillSetLastInsertId($value)
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($value) {
            $this->assertStub($stub, SetLastInsertIdStub::class, 'value', $value);
            return TRUE;
        });
        $actual = $object->willSetLastInsertId($value);
        $this->assertSame($object, $actual);
    }

    public function provideWillSetLastInsertId()
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
    public function testWillThrowException($value)
    {
        $object = $this->createMockObjectForWillTest(function ($stub) use ($value) {
            $this->assertStub($stub, ThrowExceptionStub::class, 'exception', $value);
            return TRUE;
        });
        $actual = $object->willThrowException($value);
        $this->assertSame($object, $actual);
    }

    public function provideWillThrowException()
    {
        return [
            [new RuntimeException],
        ];
    }

    /**
     * @param   callable  $checkArgument
     * @return  ConsecutiveCallsBuilder
     */
    private function createMockObjectForWillTest(callable $checkArgument)
    {
        $object = $this->getMockBuilder(ConsecutiveCallsBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['will'])
            ->getMock();
        $object->expects($this->once())
            ->method('will')
            ->with($this->callback($checkArgument))
            ->willReturn($object);
        return $object;
    }
}
