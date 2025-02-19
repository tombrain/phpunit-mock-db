<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use Cz\PHPUnit\MockDB\Matcher\Invocation as MatcherInvocation,
    Cz\PHPUnit\MockDB\Matcher\ParametersMatcher,
    Cz\PHPUnit\MockDB\Matcher\QueryMatcher,
    Cz\PHPUnit\MockDB\Matcher\RecordedInvocation,
    PHPUnit\Framework\ExpectationFailedException,
    RuntimeException;

/**
 * Matcher
 * 
 * @author   czukowski
 * @license  MIT License
 */
class Matcher implements MatcherInvocation
{
    /**
     * @var  RecordedInvocation
     */
    private $invocationMatcher;
    /**
     * @var  QueryMatcher
     */
    private $queryMatcher;
    /**
     * @var  ParametersMatcher
     */
    private $parametersMatcher;
    /**
     * @var  Stub
     */
    private $stub;

    /**
     * @param  RecordedInvocation  $invocationMatcher
     */
    public function __construct(RecordedInvocation $invocationMatcher)
    {
        $this->invocationMatcher = $invocationMatcher;
    }

    /**
     * @return  boolean
     */
    public function hasMatchers(): bool
    {
        return !$this->invocationMatcher->isAnyInvokedCount();
    }

    /**
     * @return  QueryMatcher
     */
    public function getQueryMatcher(): QueryMatcher
    {
        return $this->queryMatcher;
    }

    /**
     * @return  boolean
     */
    public function hasQueryMatcher(): bool
    {
        return $this->queryMatcher !== null;
    }

    /**
     * @param   QueryMatcher  $matcher
     * @throws  RuntimeException
     */
    public function setQueryMatcher(QueryMatcher $matcher): void
    {
        if ($this->hasQueryMatcher())
        {
            throw new RuntimeException('Query matcher is already defined, cannot redefine');
        }
        $this->queryMatcher = $matcher;
    }

    /**
     * @return  ParametersMatcher
     */
    public function getParametersMatcher(): ParametersMatcher
    {
        return $this->parametersMatcher;
    }

    /**
     * @return  boolean
     */
    public function hasParametersMatcher(): bool
    {
        return $this->parametersMatcher !== null;
    }

    /**
     * @param   ParametersMatcher  $matcher
     * @throws  RuntimeException
     */
    public function setParametersMatcher(ParametersMatcher $matcher): void
    {
        if ($this->hasParametersMatcher())
        {
            throw new RuntimeException('Parameters rule is already defined, cannot redefine');
        }
        $this->parametersMatcher = $matcher;
    }

    /**
     * @param  Stub  $stub
     */
    public function setStub($stub): void
    {
        $this->stub = $stub;
    }

    /**
     * @param  Invocation  $invocation
     */
    public function invoked(Invocation $invocation): void
    {
        $this->invocationMatcher->invoked($invocation);
        if ($this->stub)
        {
            $this->stub->invoke($invocation);
        }
    }

    /**
     * @param   Invocation  $invocation
     * @return  boolean
     */
    public function matches(Invocation $invocation): bool
    {
        if (!$this->invocationMatcher->matches($invocation))
        {
            return false;
        }
        elseif ($this->hasQueryMatcher() && !$this->queryMatcher->matches($invocation))
        {
            return false;
        }
        elseif ($this->hasParametersMatcher() && !$this->parametersMatcher->matches($invocation))
        {
            return false;
        }
        return true;
    }

    /**
     * @throws  ExpectationFailedException
     */
    public function verify(): void
    {
        try
        {
            $this->invocationMatcher->verify();

            $invocationIsAny = $this->invocationMatcher->isAnyInvokedCount();
            $invocationIsNever = $this->invocationMatcher->isNeverInvokedCount();

            if ($this->hasQueryMatcher() && !$invocationIsAny && !$invocationIsNever)
            {
                $this->queryMatcher->verify();
            }
            if ($this->hasParametersMatcher() && !$invocationIsAny && !$invocationIsNever)
            {
                $this->parametersMatcher->verify();
            }
        }
        catch (ExpectationFailedException $e)
        {
            throw new ExpectationFailedException(
                sprintf(
                    "Expectation failed when %s.\n%s",
                    $this->invocationMatcher->toString(),
                    TestFailure::exceptionToString($e)
                )
            );
        }
    }

    /**
     * @return  string
     */
    public function toString(): string
    {
        $list = [];
        $list[] = $this->invocationMatcher->toString();
        if ($this->hasQueryMatcher())
        {
            $list[] = 'where ' . $this->queryMatcher->toString();
        }
        if ($this->hasParametersMatcher())
        {
            $list[] = $this->parametersMatcher->toString();
        }
        return implode(' ', $list);
    }
}
