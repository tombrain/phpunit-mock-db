<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

/**
 * DatabaseDriverInterface
 * 
 * @author   czukowski
 * @license  MIT License
 */
interface DatabaseDriverInterface
{
    /**
     * @param  Mock  $mock
     */
    public function setMockObject(Mock $mock): void;

    public function query($query);
}
