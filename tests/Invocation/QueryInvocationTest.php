<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Invocation;

use Cz\PHPUnit\MockDB\Testcase,
    ArrayObject;

/**
 * QueryInvocationTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class QueryInvocationTest extends Testcase
{
    /**
     * @dataProvider  provideGetQuery
     */
    public function testGetQuery(string $query): void
    {
        $object = $this->createObject($query);
        static::assertSame($query, $object->getQuery());
    }

    public static function provideGetQuery(): array
    {
        return [
            ['SELECT * FROM `t`'],
        ];
    }

    /**
     * @dataProvider  providerParameters
     */
    public function testParameters(string $query, array $parameters): void
    {
        $object = $this->createObject($query);
        $object->setParameters($parameters);
        static::assertSame($parameters, $object->getParameters());
    }

    public static function providerParameters(): array
    {
        return [
            ['SELECT * FROM `t1` WHERE `c` = ?', [1]],
        ];
    }

    /**
     * @dataProvider  provideAffectedRows
     */
    public function testAffectedRows(string $query, int $value): void
    {
        $object = $this->createObject($query);
        $object->setAffectedRows($value);
        static::assertSame($value, $object->getAffectedRows());
    }

    public static function provideAffectedRows(): array
    {
        return [
            ['UPDATE `t` SET `a` = 1 WHERE `b` = 2', 100],
        ];
    }

    /**
     * @dataProvider  provideLastInsertId
     */
    public function testLastInsertId(string $query, $value): void
    {
        $object = $this->createObject($query);
        $object->setLastInsertId($value);
        static::assertSame($value, $object->getLastInsertId());
    }

    public static function provideLastInsertId(): array
    {
        return [
            ['INSERT INTO `t` (`a`, `b`) VALUES (1, 2)', 505],
        ];
    }

    /**
     * @dataProvider  provideResultSet
     */
    public function testResultSet(string $query, iterable $results): void
    {
        $object = $this->createObject($query);
        $object->setResultSet($results);
        static::assertSame($results, $object->getResultSet());
    }

    public static function provideResultSet(): array
    {
        return [
            [
                'SELECT * FROM `t1`',
                [],
            ],
            [
                'SELECT * FROM `t2`',
                [
                    ['id' => 1, 'name' => 'foo'],
                    ['id' => 2, 'name' => 'bar'],
                ],
            ],
            [
                'SELECT * FROM `t3`',
                new ArrayObject([
                    ['id' => 1, 'name' => 'foo'],
                    ['id' => 2, 'name' => 'bar'],
                ]),
            ],
        ];
    }

    private function createObject(string $query): QueryInvocation
    {
        return new QueryInvocation($query);
    }
}
