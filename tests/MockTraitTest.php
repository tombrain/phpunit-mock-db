<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB;

use Cz\PHPUnit\MockDB\MockObject\MockWrapper;

/**
 * MockTraitTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class MockTraitTest extends Testcase
{
    /**
     * @test
     */
    public function testCreateDatabaseMock(): void
    {
        $setMockObject = null;
        $registerMockObject = null;
        $db = $this->createMock(DatabaseDriverInterface::class);
        $db->expects(static::once())
            ->method('setMockObject')
            ->with(static::callback(
                function ($mock) use (&$setMockObject)
                {
                    static::assertInstanceOf(Mock::class, $mock);
                    $setMockObject = $mock;
                    return true;
                }
            ));
        $methods = ['getDatabaseDriver'];
        $object = $this->getMockForTrait(MockTrait::class, [], '', true, true, true, $methods);
        $object->expects(static::once())
            ->method('getDatabaseDriver')
            ->will($this->returnValue($db));
        $object->expects(static::once())
            ->method('registerMockObject')
            ->with(static::callback(
                function ($mockObject) use (&$registerMockObject)
                {
                    static::assertInstanceOf(MockWrapper::class, $mockObject);
                    $mock = static::getObjectPropertyValue($mockObject, 'object');
                    static::assertInstanceOf(Mock::class, $mock);
                    $registerMockObject = $mock;
                    return true;
                }
            ));
        $actual = $object->createDatabaseMock();
        static::assertSame($actual, $setMockObject);
        static::assertSame($actual, $registerMockObject);
    }
}
