<?php

declare(strict_types=1);

namespace Cz\PHPUnit\MockDB\Invocation;

use Cz\PHPUnit\MockDB\Invocation,
    Cz\PHPUnit\MockDB\Testcase,
    InvalidArgumentException;

/**
 * KeywordBasedQueryInvocationFactoryTest
 * 
 * @author   czukowski
 * @license  MIT License
 */
class KeywordBasedQueryInvocationFactoryTest extends Testcase
{
    /**
     * @dataProvider  provideCreateInvocation
     */
    public function testCreateInvocation(string $sql, $expected): void
    {
        $object = $this->createObject();
        $this->expectExceptionFromArgument($expected);
        $actual = $object->createInvocation($sql);
        static::assertInstanceOf(QueryInvocation::class, $actual);
        static::assertIsCallable($expected);
        call_user_func($expected, $actual);
    }

    public static function provideCreateInvocation(): array
    {
        return [
            static::createCreateInvocationTestCaseForUpdateKeyword('UPDATE `t1` SET `name` = "foo"'),
            static::createCreateInvocationTestCaseForUpdateKeyword('DELETE FROM `t1`'),
            static::createCreateInvocationTestCaseForInsertKeyword('INSERT INTO `t1` SELECT * FROM `t2`'),
            static::createCreateInvocationTestCaseForInsertKeyword('REPLACE INTO `t1` VALUES ("foo", "bar")'),
            static::createCreateInvocationTestCaseForSelectKeyword('SELECT * FROM `t`'),
            static::createCreateInvocationTestCaseForSelectKeyword('SHOW PROCESSLIST'),
            static::createCreateInvocationTestCaseForSelectKeyword('EXEC [sys].[sp_helpindex]'),
            static::createCreateInvocationTestCaseForUnknownKeyword('BEGIN'),
            static::createCreateInvocationTestCaseForUnknownKeyword('COMMIT'),
            static::createCreateInvocationTestCaseForUnknownKeyword('ROLLBACK'),
            static::createCreateInvocationTestCaseForException(''),
        ];
    }

    private static function createCreateInvocationTestCaseForUpdateKeyword(string $sql): array
    {
        return [
            $sql,
            function (Invocation $actual)
            {
                static::assertSame(0, $actual->getAffectedRows());
                static::assertNull($actual->getLastInsertId());
                static::assertNull($actual->getResultSet());
            }
        ];
    }

    private static function createCreateInvocationTestCaseForInsertKeyword(string $sql): array
    {
        return [
            $sql,
            function (Invocation $actual)
            {
                static::assertSame(0, $actual->getAffectedRows());
                static::assertSame(1, $actual->getLastInsertId());
                static::assertNull($actual->getResultSet());
            }
        ];
    }

    private static function createCreateInvocationTestCaseForSelectKeyword(string $sql): array
    {
        return [
            $sql,
            function (Invocation $actual)
            {
                static::assertSame([], $actual->getResultSet());
                static::assertNull($actual->getAffectedRows());
                static::assertNull($actual->getLastInsertId());
            }
        ];
    }

    private static function createCreateInvocationTestCaseForUnknownKeyword(string $sql): array
    {
        return [
            $sql,
            function (Invocation $actual)
            {
                static::assertNull($actual->getAffectedRows());
                static::assertNull($actual->getLastInsertId());
                static::assertNull($actual->getResultSet());
            }
        ];
    }

    private static function createCreateInvocationTestCaseForException(string $sql): array
    {
        return [$sql, new InvalidArgumentException];
    }

    private function createObject(): KeywordBasedQueryInvocationFactory
    {
        return new KeywordBasedQueryInvocationFactory;
    }
}
