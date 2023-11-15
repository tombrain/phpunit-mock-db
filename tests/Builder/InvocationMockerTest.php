<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Builder;

use Cz\PHPUnit\MockDB\Matcher,
    Cz\PHPUnit\MockDB\Matcher\AnyParameters,
    Cz\PHPUnit\MockDB\Matcher\ParametersMatch,
    Cz\PHPUnit\MockDB\Matcher\QueryMatcher,
    Cz\PHPUnit\MockDB\Matcher\RecordedInvocation,
    Cz\PHPUnit\MockDB\Stub,
    Cz\PHPUnit\MockDB\Stub\ConsecutiveCallsStub,
    Cz\PHPUnit\SQL\EqualsSQLQueriesConstraint,
    ArrayObject,
    PHPUnit\Framework\Constraint\Constraint,
    PHPUnit\Framework\Constraint\StringStartsWith,
    RuntimeException,
    Throwable;

/**
 * InvocationMockerTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class InvocationMockerTest extends Testcase
{
    /**
     * @dataProvider  provideOnConsecutiveCalls
     */
    public function testOnConsecutiveCalls(callable $dateCallback): void
    {
        $object = $dateCallback($this);
        $actual = $object->onConsecutiveCalls();
        static::assertInstanceOf(ConsecutiveCallsBuilder::class, $actual);
        static::assertSame($object, static::getObjectPropertyValue($actual, 'builder'));
        $stub = static::getObjectPropertyValue($actual, 'stub');
        static::assertInstanceOf(ConsecutiveCallsStub::class, $stub);
        static::assertEmpty(static::getObjectPropertyValue($stub, 'stack'));
    }

    public static function provideOnConsecutiveCalls(): array
    {
        return [
            [fn (self $testCase) => $testCase->createObject()]
        ];
    }

    /**
     * @dataProvider  provideQuery
     */
    public function testQuery($constraint, string $expected): void
    {
        $object = $this->createObject();
        $self = $object->query($constraint);
        static::assertSame($object, $self);
        $queryMatcher = $this->getObjectMatcher($object)
            ->getQueryMatcher();
        static::assertInstanceOf(QueryMatcher::class, $queryMatcher);
        $actual = static::getObjectPropertyValue($queryMatcher, 'constraint');
        static::assertInstanceOf($expected, $actual);
        if ($constraint instanceof Constraint)
        {
            static::assertSame($constraint, $actual);
        }
    }

    public static function provideQuery(): array
    {
        return [
            ['SELECT * FROM `t1`', EqualsSQLQueriesConstraint::class],
            [new EqualsSQLQueriesConstraint('SELECT * FROM `t1`'), EqualsSQLQueriesConstraint::class],
            [static::stringStartsWith('SELECT'), StringStartsWith::class],
        ];
    }

    /**
     * @dataProvider  provideWith
     */
    public function testWith(array $parameters, array $expected): void
    {
        $object = $this->createObject();
        $self = $object->with($parameters);
        static::assertSame($object, $self);
        $parametersMatch = $this->getObjectMatcher($object)
            ->getParametersMatcher();
        static::assertInstanceOf(ParametersMatch::class, $parametersMatch);
        $actual = static::getObjectPropertyValue($parametersMatch, 'parameters');
        static::assertCount(count($expected), $actual);
        for ($i = 0; $i < count($expected); $i++)
        {
            if ($expected[$i] instanceof Constraint)
            {
                static::assertSame($expected[$i], $actual[$i]);
            }
            else
            {
                static::assertInstanceOf(Constraint::class, $actual[$i]);
                $actual[$i]->evaluate($expected[$i]);
            }
        }
    }

    public static function provideWith(): array
    {
        $anyMatcher = static::anything();
        return [
            [[1, 2], [1, 2]],
            [[3.14, $anyMatcher], [3.14, $anyMatcher]],
        ];
    }

    public function testWithAnyParameters(): void
    {
        $object = $this->createObject();
        $self = $object->withAnyParameters();
        static::assertSame($object, $self);
        $parametersMatch = $this->getObjectMatcher($object)
            ->getParametersMatcher();
        static::assertInstanceOf(AnyParameters::class, $parametersMatch);
    }

    /**
     * @dataProvider  provideWill
     */
    public function testWill(Stub $stub, string $expected): void
    {
        $object = $this->createObject();
        $self = $object->will($stub);
        static::assertSame($object, $self);
        $actual = static::getObjectPropertyValue($this->getObjectMatcher($object), 'stub');
        static::assertInstanceOf($expected, $actual);
        if ($stub instanceof Stub)
        {
            static::assertSame($stub, $actual);
        }
    }

    public static function provideWill(): array
    {
        return [
            //[$this->createMock(Stub::class), Stub::class],
            [new Stub\ReturnResultSetStub([]), Stub\ReturnResultSetStub::class],
            [new Stub\SetAffectedRowsStub(0), Stub\SetAffectedRowsStub::class],
            [new Stub\SetLastInsertIdStub(1), Stub\SetLastInsertIdStub::class],
        ];
    }

    /**
     * @dataProvider  provideWillInvokeCallback
     */
    public function testWillInvokeCallback(array $arguments, callable $callback): void
    {
        $object = $this->createMockObjectForWillTest($callback);
        $actual = $object->willInvokeCallback(...$arguments);
        static::assertSame($object, $actual);
    }

    public static function provideWillInvokeCallback(): array
    {
        return [
            static::createWillInvokeCallbackTestCaseSingleCall(function ()
            {
            }),
            static::createWillInvokeCallbackTestCaseConsecutiveCalls([function ()
            {
            }, function ()
            {
            }]),
        ];
    }

    private static function createWillInvokeCallbackTestCaseSingleCall(callable $callback): array
    {
        return [
            [$callback],
            function ($stub) use ($callback)
            {
                static::assertStub($stub, Stub\InvokeCallbackStub::class, 'callback', $callback);
                return true;
            },
        ];
    }

    private static function createWillInvokeCallbackTestCaseConsecutiveCalls(array $callbacks): array
    {
        return [
            $callbacks,
            function ($stub) use ($callbacks)
            {
                static::assertConsecutiveStubs($stub, $callbacks, Stub\InvokeCallbackStub::class, 'callback');
                return true;
            },
        ];
    }

    /**
     * @dataProvider  provideWillReturnResultSet
     */
    public function testWillReturnResultSet(array $arguments, callable $callback): void
    {
        $object = $this->createMockObjectForWillTest($callback);
        $actual = $object->willReturnResultSet(...$arguments);
        static::assertSame($object, $actual);
    }

    public static function provideWillReturnResultSet(): array
    {
        $resultSet1 = [];
        $resultSet2 = [
            ['id' => 1],
            ['id' => 2],
        ];
        $resultSet3 = [
            ['id' => 2],
            ['id' => 3],
        ];
        return [
            static::createWillReturnResultSetTestCaseSingleCall(null),
            static::createWillReturnResultSetTestCaseSingleCall($resultSet1),
            static::createWillReturnResultSetTestCaseSingleCall($resultSet2),
            static::createWillReturnResultSetTestCaseSingleCall(new ArrayObject($resultSet2)),
            static::createWillReturnResultSetTestCaseConsecutiveCalls([$resultSet1, $resultSet2]),
            static::createWillReturnResultSetTestCaseConsecutiveCalls([new ArrayObject($resultSet2), new ArrayObject($resultSet3)]),
        ];
    }

    private static function createWillReturnResultSetTestCaseSingleCall(?iterable $value): array
    {
        return [
            [$value],
            function ($stub) use ($value)
            {
                static::assertStub($stub, Stub\ReturnResultSetStub::class, 'value', $value);
                return true;
            },
        ];
    }

    private static function createWillReturnResultSetTestCaseConsecutiveCalls(array $values): array
    {
        return [
            $values,
            function ($stub) use ($values)
            {
                static::assertConsecutiveStubs($stub, $values, Stub\ReturnResultSetStub::class, 'value');
                return true;
            }
        ];
    }

    /**
     * @dataProvider  provideWillSetAffectedRows
     */
    public function testWillSetAffectedRows(array $arguments, callable $callback): void
    {
        $object = $this->createMockObjectForWillTest($callback);
        $actual = $object->willSetAffectedRows(...$arguments);
        static::assertSame($object, $actual);
    }

    public static function provideWillSetAffectedRows(): array
    {
        return [
            static::createWillSetAffectedRowsTestCaseSingleCall(0),
            static::createWillSetAffectedRowsTestCaseSingleCall(100),
            static::createWillSetAffectedRowsTestCaseConsecutiveCalls([1, 2, 3]),
        ];
    }

    private static function createWillSetAffectedRowsTestCaseSingleCall(int $value): array
    {
        return [
            [$value],
            function ($stub) use ($value)
            {
                static::assertStub($stub, Stub\SetAffectedRowsStub::class, 'value', $value);
                return true;
            },
        ];
    }

    private static function createWillSetAffectedRowsTestCaseConsecutiveCalls(array $values): array
    {
        return [
            $values,
            function ($stub) use ($values)
            {
                static::assertConsecutiveStubs($stub, $values, Stub\SetAffectedRowsStub::class, 'value');
                return true;
            }
        ];
    }

    /**
     * @dataProvider  provideWillSetLastInsertId
     */
    public function testWillSetLastInsertId(array $arguments, callable $callback): void
    {
        $object = $this->createMockObjectForWillTest($callback);
        $actual = $object->willSetLastInsertId(...$arguments);
        static::assertSame($object, $actual);
    }

    public static function provideWillSetLastInsertId(): array
    {
        return [
            static::createWillSetLastInsertIdTestCaseSingleCall(null),
            static::createWillSetLastInsertIdTestCaseSingleCall(123),
            static::createWillSetLastInsertIdTestCaseSingleCall('456'),
            static::createWillSetLastInsertIdTestCaseConsecutiveCalls([null, 1, 2]),
        ];
    }

    private static function createWillSetLastInsertIdTestCaseSingleCall($value): array
    {
        return [
            [$value],
            function ($stub) use ($value)
            {
                static::assertStub($stub, Stub\SetLastInsertIdStub::class, 'value', $value);
                return true;
            },
        ];
    }

    private static function createWillSetLastInsertIdTestCaseConsecutiveCalls(array $values): array
    {
        return [
            $values,
            function ($stub) use ($values)
            {
                static::assertConsecutiveStubs($stub, $values, Stub\SetLastInsertIdStub::class, 'value');
                return true;
            }
        ];
    }

    /**
     * @dataProvider  provideWillThrowException
     */
    public function testWillThrowException(array $arguments, callable $callback): void
    {
        $object = static::createMockObjectForWillTest($callback);
        $actual = $object->willThrowException(...$arguments);
        static::assertSame($object, $actual);
    }

    public static function provideWillThrowException(): array
    {
        return [
            static::createWillThrowExceptionTestCaseSingleCall(new RuntimeException),
            static::createWillThrowExceptionTestCaseConsecutiveCalls([new RuntimeException, new RuntimeException]),
        ];
    }

    private static function createWillThrowExceptionTestCaseSingleCall(Throwable $value): array
    {
        return [
            [$value],
            function ($stub) use ($value)
            {
                static::assertStub($stub, Stub\ThrowExceptionStub::class, 'exception', $value);
                return true;
            },
        ];
    }

    private static function createWillThrowExceptionTestCaseConsecutiveCalls(array $values): array
    {
        return [
            $values,
            function ($stub) use ($values)
            {
                static::assertConsecutiveStubs($stub, $values, Stub\ThrowExceptionStub::class, 'exception');
                return true;
            }
        ];
    }

    /**
     * @param   callable  $checkArgument
     * @return  InvocationMocker
     */
    private function createMockObjectForWillTest(callable $checkArgument): InvocationMocker
    {
        $object = $this->getMockBuilder(InvocationMocker::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['will'])
            ->getMock();
        $object->expects(static::once())
            ->method('will')
            ->with(static::callback($checkArgument))
            ->willReturn($object);
        return $object;
    }

    /**
     * @return  InvocationMocker
     */
    private function createObject(): InvocationMocker
    {
        return new InvocationMocker(
            $this->createMock(Stub\MatcherCollection::class),
            $this->createMock(RecordedInvocation::class)
        );
    }

    /**
     * @param   InvocationMocker  $object
     * @return  Matcher
     */
    private function getObjectMatcher(InvocationMocker $object): Matcher
    {
        $matcher = static::getObjectPropertyValue($object, 'matcher');
        static::assertInstanceOf(Matcher::class, $matcher);
        return $matcher;
    }
}
