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
    Cz\PHPUnit\MockDB\Stub\InvokeCallbackStub,
    Cz\PHPUnit\MockDB\Stub\MatcherCollection,
    Cz\PHPUnit\MockDB\Stub\ReturnResultSetStub,
    Cz\PHPUnit\MockDB\Stub\SetAffectedRowsStub,
    Cz\PHPUnit\MockDB\Stub\SetLastInsertIdStub,
    Cz\PHPUnit\MockDB\Stub\ThrowExceptionStub,
    Cz\PHPUnit\SQL\EqualsSQLQueriesConstraint,
    PHPUnit\Framework\Constraint\Constraint,
    Throwable;

/**
 * InvocationMocker
 * 
 * @author   czukowski
 * @license  MIT License
 */
class InvocationMocker
{
    /**
     * @var  MatcherCollection
     */
    private $collection;
    /**
     * @var  Matcher
     */
    protected $matcher;

    /**
     * @param  MatcherCollection   $collection
     * @param  RecordedInvocation  $invocationMatcher
     */
    public function __construct(MatcherCollection $collection, RecordedInvocation $invocationMatcher)
    {
        $this->collection = $collection;
        $this->matcher = new Matcher($invocationMatcher);
        $this->collection->addMatcher($this->matcher);
    }

    /**
     * @return  ConsecutiveCallsBuilder
     */
    public function onConsecutiveCalls(): ConsecutiveCallsBuilder
    {
        $stub = new ConsecutiveCallsStub;
        $this->will($stub);
        return new ConsecutiveCallsBuilder($this, $stub);
    }

    /**
     * @param   Constraint|string  $constraint
     * @return  $this
     */
    public function query($constraint): self
    {
        if (is_string($constraint))
        {
            $constraint = new EqualsSQLQueriesConstraint($constraint);
        }
        $this->matcher->setQueryMatcher(new QueryMatcher($constraint));
        return $this;
    }

    /**
     * @param   array  $parameters
     * @return  $this
     */
    public function with(array $parameters): self
    {
        $this->matcher->setParametersMatcher(new ParametersMatch($parameters));
        return $this;
    }

    /**
     * @return  $this
     */
    public function withAnyParameters(): self
    {
        $this->matcher->setParametersMatcher(new AnyParameters);
        return $this;
    }

    /**
     * @param   Stub  $stub
     * @return  $this
     */
    public function will(Stub $stub): self
    {
        $this->matcher->setStub($stub);
        return $this;
    }

    /**
     * @param   callable  $callback
     * @param   callable  $nextCallbacks ...
     * @return  $this
     */
    public function willInvokeCallback(callable $callback, ...$nextCallbacks): self
    {
        return $this->createStub(
            function ($argument)
            {
                return new InvokeCallbackStub($argument);
            },
            $callback,
            $nextCallbacks
        );
    }

    /**
     * @param   mixed  $resultSet
     * @param   mixed  $nextSets ...
     * @return  $this
     */
    public function willReturnResultSet($resultSet, ...$nextSets): self
    {
        return $this->createStub(
            function ($argument)
            {
                return new ReturnResultSetStub($argument);
            },
            $resultSet,
            $nextSets
        );
    }

    /**
     * @param   integer  $count
     * @param   integer  $nextCounts ...
     * @return  $this
     */
    public function willSetAffectedRows($count, ...$nextCounts): self
    {
        return $this->createStub(
            function ($argument)
            {
                return new SetAffectedRowsStub($argument);
            },
            $count,
            $nextCounts
        );
    }

    /**
     * @param   mixed  $value
     * @param   mixed  $nextValues ...
     * @return  $this
     */
    public function willSetLastInsertId($value, ...$nextValues): self
    {
        return $this->createStub(
            function ($argument)
            {
                return new SetLastInsertIdStub($argument);
            },
            $value,
            $nextValues
        );
    }

    /**
     * @param   Throwable  $exception
     * @param   Throwable  $nextExceptions ...
     * @return  $this
     */
    public function willThrowException(Throwable $exception, ...$nextExceptions): self
    {
        return $this->createStub(
            function ($argument)
            {
                return new ThrowExceptionStub($argument);
            },
            $exception,
            $nextExceptions
        );
    }

    /**
     * @param   callable  $callback
     * @param   mixed     $argument
     * @param   array     $nextArguments
     * @return  $this
     */
    private function createStub(callable $callback, $argument, array $nextArguments): self
    {
        if (!$nextArguments)
        {
            return $this->will($callback($argument));
        }
        return $this->will(
            new ConsecutiveCallsStub(
                array_map(
                    $callback,
                    array_merge([$argument], $nextArguments)
                )
            )
        );
    }
}
