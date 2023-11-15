<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Doubles;

use Cz\PHPUnit\MockDB\InvocationMocker,
    Cz\PHPUnit\MockDB\Mock,
    Cz\PHPUnit\MockDB\MockObject\InvocationsContainer;

/**
 * MockDouble
 * 
 * @author   czukowski
 * @license  MIT License
 */
class MockDouble extends Mock
{
    private $invocationMocker;
    private $invocationsContainer;

    public function __construct(
        InvocationMocker $invocationMocker,
        InvocationsContainer $invocationsContainer = null
    )
    {
        $this->invocationMocker = $invocationMocker;
        $this->invocationsContainer = $invocationsContainer;
    }

    public function getInvocationMocker(): InvocationMocker
    {
        return $this->invocationMocker;
    }

    protected function getInvocationsContainer(): InvocationsContainer
    {
        return $this->invocationsContainer !== null
            ? $this->invocationsContainer
            : parent::getInvocationMocker();
    }
}
