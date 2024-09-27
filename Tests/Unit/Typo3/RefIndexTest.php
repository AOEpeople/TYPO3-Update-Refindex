<?php

namespace Aoe\UpdateRefindex\Tests\Unit\Typo3;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2023 AOE GmbH <dev@aoe.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Aoe\UpdateRefindex\Typo3\RefIndex;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Result;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use ReflectionMethod;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RefIndexTest extends UnitTestCase
{
    public ConnectionPool $connectionPool;

    private Prophet $prophet;

    private ObjectProphecy|ConnectionPool|null $connectionPoolProphet = null;

    private ?ConnectionPool $connectionPoolMock = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prophet = new Prophet();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($this->connectionPoolMock);

        parent::tearDown();
    }

    public function testGetExistingTables(): void
    {
        $GLOBALS['TCA'] = [
            'table_3' => [],
            'table_0' => [],
            'table_1' => [],
        ];
        $refIndex = new RefIndex();

        $this->assertSame(['table_0', 'table_1', 'table_3'], $refIndex->getExistingTables());
    }

    public function testGetReferenceIndex(): void
    {
        $referenceIndex = $this->getMockBuilder(ReferenceIndex::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(ReferenceIndex::class, $referenceIndex);

        $refIndex = new RefIndex();
        $reflectionMethod = new ReflectionMethod($refIndex, 'getReferenceIndex');
        $reflectionMethod->invokeArgs($refIndex, []);
    }

    public function testUpdate(): void
    {
        $tableData = [
            'table_1' => [
                ['uid' => 10],
                ['uid' => 20],
            ],
            'table_2' => [
                ['uid' => 70],
                ['uid' => 80],
                ['uid' => 90],
            ],
        ];
        $selectedTables = array_keys($tableData);

        $refIndex = $this->getMockBuilder(RefIndex::class)
            ->onlyMethods(['getExistingTables', 'updateTable', 'deleteLostIndexes'])
            ->getMock();
        $refIndex
            ->expects(self::any())
            ->method('getExistingTables')
            ->willReturn($selectedTables);
        $matcher = $this->exactly(2);
        $refIndex
            ->expects($matcher)
            ->method('updateTable')
            ->willReturnCallback(function ($selectedTable) use ($matcher, $selectedTables): void {
                match ($this->matcherCount($matcher)) {
                    1 => $this->assertSame($selectedTables[0], $selectedTable),
                    2 => $this->assertSame($selectedTables[1], $selectedTable),
                };
            });
        $refIndex
            ->expects(self::once())
            ->method('deleteLostIndexes');

        $refIndex->setSelectedTables($selectedTables);
        $refIndex->update();
    }

    public function testUpdateDoesNothingWhenTableIsNotConfiguredInTCA(): void
    {
        $refIndex = $this
            ->getMockBuilder(RefIndex::class)
            ->onlyMethods(['getExistingTables', 'updateTable', 'deleteLostIndexes'])
            ->getMock();
        $refIndex->expects(self::any())
            ->method('getExistingTables')
            ->willReturn(['table_1', 'table_2']);
        $refIndex
            ->expects(self::never())
            ->method('updateTable');
        $refIndex
            ->expects(self::never())
            ->method('deleteLostIndexes');

        $refIndex->setSelectedTables(['some_table_not_configured_in_tca']);
        $refIndex->update();
    }

    public function testDeleteLostIndexes(): void
    {
        $existingTables = ['table_1', 'table_2'];
        $refIndexMock = $this
            ->getMockBuilder(RefIndex::class)
            ->onlyMethods(['getExistingTables', 'getQueryBuilderForTable'])
            ->getMock();
        $refIndexMock
            ->method('getExistingTables')
            ->willReturn($existingTables);

        $queryBuilderMock = $this
            ->getQueryBuilderMock('sys_refindex');

        /** @var MockObject|ExpressionBuilder $expressionBuilderMock */
        $expressionBuilderMock = $queryBuilderMock->expr();

        $expressionBuilderMock
            ->expects($this->once())
            ->method('notIn')
            ->with('tablename', ':dcValue1')
            ->willReturn('`tablename` NOT IN (:dcValue1)');

        $queryBuilderMock
            ->expects($this->once())
            ->method('createNamedParameter')
            ->with($existingTables, ArrayParameterType::INTEGER)
            ->willReturn(':dcValue1');

        $queryBuilderMock
            ->expects($this->once())
            ->method('where')
            ->with('`tablename` NOT IN (:dcValue1)')
            ->willReturnSelf();

        $queryBuilderMock
            ->expects($this->once())
            ->method('delete')
            ->with('sys_refindex')
            ->willReturnSelf();

        $queryBuilderMock
            ->expects($this->once())
            ->method('executeStatement')
            ->willReturn(1);

        $reflectionMethod = new ReflectionMethod($refIndexMock, 'deleteLostIndexes');
        $reflectionMethod->invokeArgs($refIndexMock, []);
    }

    public function testUpdateTable(): void
    {
        $table = 'test_table';
        $records = [['uid' => 1], ['uid' => 2]];
        $referenceIndexMock = $this
            ->getMockBuilder(ReferenceIndex::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateRefIndexTable'])
            ->getMock();

        $matcher = $this->exactly(2);
        $referenceIndexMock->expects($matcher)
            ->method('updateRefIndexTable')
            ->willReturnCallback(function ($actualTable, $actualParam1, $actualParam2) use ($matcher, $table): void {
                match ($this->matcherCount($matcher)) {
                    1 => $this->assertEquals([$table, 1, false], [$actualTable, $actualParam1, $actualParam2]),
                    2 => $this->assertEquals([$table, 2, false], [$actualTable, $actualParam1, $actualParam2]),
                };
            });

        $refIndexMock = $this->getMockBuilder(RefIndex::class)
            ->onlyMethods(['getReferenceIndex', 'getDeletableRecUidListFromTable'])
            ->getMock();
        $refIndexMock
            ->method('getReferenceIndex')
            ->willReturn($referenceIndexMock);
        $refIndexMock
            ->method('getDeletableRecUidListFromTable')
            ->willReturn([0]);

        $testTableQueryBuilderProphet = $this->getQueryBuilderProphet($table);
        $selectQueryBuilderMock = $testTableQueryBuilderProphet->reveal();

        $resultMock = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock
            ->method('fetchAllAssociative')
            ->willReturn($records);

        $testTableQueryBuilderProphet
            ->select('uid')
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryBuilderMock);
        $testTableQueryBuilderProphet
            ->from($table)
            ->shouldBeCalledOnce()
            ->willReturn($selectQueryBuilderMock);
        $testTableQueryBuilderProphet
            ->executeQuery()
            ->shouldBeCalledOnce()
            ->willReturn($resultMock);

        $refTableQueryBuilderProphet = $this->getQueryBuilderProphet('sys_refindex');
        $refTableQueryBuilderMock = $refTableQueryBuilderProphet->reveal();

        $refTableQueryBuilderProphet
            ->delete('sys_refindex')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->where('`tablename` = :dcValue1')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->andWhere('`recuid` IN (:dcValue2)')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->execute()
            ->shouldBeCalledOnce();

        $refTableQueryBuilderProphet->createNamedParameter($table, PDO::PARAM_STR)->willReturn(':dcValue1');
        $refTableQueryBuilderProphet->createNamedParameter([0], Connection::PARAM_INT_ARRAY)->willReturn(':dcValue2');

        $reflectionMethod = new ReflectionMethod($refIndexMock, 'updateTable');
        $reflectionMethod->invokeArgs($refIndexMock, [$table]);
    }

    public function testGetDeletableRecUidListFromTable(): void
    {
        $table = 'test_table';

        $refIndex = $this->getMockBuilder(RefIndex::class)->getMock();

        $testTableQueryBuilderProphet = $this->getQueryBuilderProphet($table);
        $selectQueryBuilderMock = $testTableQueryBuilderProphet->reveal();

        $testTableQueryBuilderProphet
            ->getSQL()
            ->willReturn('SELECT `uid` FROM `test_table`');

        $testTableQueryBuilderProphet
            ->select('uid')
            ->willReturn($selectQueryBuilderMock);
        $testTableQueryBuilderProphet
            ->from($table)
            ->willReturn($selectQueryBuilderMock);

        $resultMock = $this
            ->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $refTableQueryBuilderProphet = $this->getQueryBuilderProphet('sys_refindex');
        $refTableQueryBuilderMock = $refTableQueryBuilderProphet->reveal();

        $refTableQueryBuilderProphet
            ->select('recuid')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->from('sys_refindex')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->where('`tablename` = :dcValue1')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);

        $refTableQueryBuilderProphet
            ->andWhere('`recuid` NOT IN (SELECT `uid` FROM `test_table`)')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->groupBy('recuid')
            ->shouldBeCalledOnce()
            ->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet
            ->executeQuery()
            ->shouldBeCalledOnce()
            ->willReturn($resultMock);

        $refTableQueryBuilderProphet
            ->createNamedParameter($table, PDO::PARAM_STR)
            ->willReturn(':dcValue1');

        $reflectionMethod = new ReflectionMethod($refIndex, 'getDeletableRecUidListFromTable');
        $reflectionMethod->invokeArgs($refIndex, [$table]);
    }

    private function getQueryBuilderMock(string $table): MockObject|QueryBuilder
    {
        $connectionMock = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock
            ->method('quoteIdentifier')
            ->willReturnCallback(static fn ($arguments): string => '`' . $arguments[0] . '`');

        $queryRestrictionMock = $this
            ->getMockBuilder(QueryRestrictionContainerInterface::class)
            ->getMock();
        $queryRestrictionMock
            ->method('removeAll')
            ->willReturnSelf();

        $expressionBuilderMock = $this
            ->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilderMock = $this
            ->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilderMock
            ->method('getRestrictions')
            ->willReturn($queryRestrictionMock);
        $queryBuilderMock
            ->method('expr')
            ->willReturn($expressionBuilderMock);

        $connectionPoolMock = $this->getConnectionPoolMock();
        $connectionPoolMock
            ->method('getQueryBuilderForTable')
            ->with($table)
            ->willReturn($queryBuilderMock);

        return $queryBuilderMock;
    }

    private function getQueryBuilderProphet(string $table): MockObject|ObjectProphecy
    {
        $connectionProphet = $this->prophet->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->will(static fn ($arguments): string => '`' . $arguments[0] . '`');

        $queryRestrictionProphet = $this->prophet->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionProphet->removeAll()
            ->shouldBeCalled();

        $queryBuilderProphet = $this
            ->prophet
            ->prophesize(QueryBuilder::class);
        $queryBuilderProphet
            ->getRestrictions()
            ->willReturn($queryRestrictionProphet->reveal());
        $queryBuilderProphet
            ->expr()
            ->willReturn(
                GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
            );
        $queryBuilderProphet
            ->executeStatement()
            ->willReturn(2);

        /** @var ObjectProphecy|MockObject $connectionPoolProphet */
        $connectionPoolProphet = $this->getConnectionPoolProphet();
        $connectionPoolProphet->getQueryBuilderForTable($table)
            ->willReturn($queryBuilderProphet->reveal());

        return $queryBuilderProphet;
    }

    /**
     * @return MockObject|ConnectionPool
     */
    private function getConnectionPoolMock(): ?ConnectionPool
    {
        if ($this->connectionPoolMock === null) {
            $this->connectionPoolMock = $this
                ->getMockBuilder(ConnectionPool::class)
                ->getMock();
            GeneralUtility::addInstance(ConnectionPool::class, $this->connectionPoolMock);
        }

        return $this->connectionPoolMock;
    }

    /**
     * @return ObjectProphecy|ConnectionPool
     */
    private function getConnectionPoolProphet(): ?ObjectProphecy
    {
        if ($this->connectionPoolProphet === null) {
            $this->connectionPoolProphet = $this->prophet->prophesize(ConnectionPool::class);
            GeneralUtility::addInstance(ConnectionPool::class, $this->connectionPoolProphet->reveal());
        }

        return $this->connectionPoolProphet;
    }

    /**
     * This can be ignored in codestyles and removed after switching to php 8.3 fully
     */
    private function matcherCount($matcher): int
    {
        $requiredVersion = '8.1.0';

        if (version_compare(PHP_VERSION, $requiredVersion) > 0) {
            return $matcher->numberOfInvocations();
        }

        return $matcher->getInvocationCount();
    }
}
