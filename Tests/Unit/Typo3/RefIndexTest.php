<?php
namespace Aoe\UpdateRefindex\Tests\Unit\Typo3;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2021 AOE GmbH <dev@aoe.com>
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
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\FetchMode;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PDO;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests class RefIndex
 *
 * @package update_refindex
 * @subpackage Tests
 */
class RefIndexTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|ConnectionPool
     */
    private $connectionPoolProphet;

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($this->connectionPool);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function getExistingTables()
    {
        $GLOBALS['TCA'] = ['table_3' => [], 'table_0' => [], 'table_1' => []];
        $refIndex = new RefIndex();

        self::assertEquals(
            ['table_0', 'table_1', 'table_3'],
            $refIndex->getExistingTables()
        );
    }

    /**
     * @test
     */
    public function getReferenceIndex()
    {
        $referenceIndex = $this->getMockBuilder(ReferenceIndex::class)
            ->disableOriginalConstructor()
            ->getMock();
        GeneralUtility::addInstance(ReferenceIndex::class, $referenceIndex);

        $refIndex = new RefIndex();

        self::assertInstanceOf(
            ReferenceIndex::class,
            $this->callInaccessibleMethod($refIndex, 'getReferenceIndex')
        );
    }

    /**
     * @test
     */
    public function update()
    {
        $tableData = [
            'table_1' => [
                ['uid' => 10],
                ['uid' => 20]
            ],
            'table_2' => [
                ['uid' => 70],
                ['uid' => 80],
                ['uid' => 90]
            ],
        ];
        $selectedTables = array_keys($tableData);

        $refIndex = $this->getMockBuilder(RefIndex::class)
            ->setMethods(['getExistingTables', 'updateTable', 'deleteLostIndexes'])
            ->getMock();
        $refIndex->expects(self::any())->method('getExistingTables')->willReturn($selectedTables);
        $refIndex->expects(self::exactly(2))->method('updateTable')->withConsecutive([$selectedTables[0]], [$selectedTables[1]]);
        $refIndex->expects(self::once())->method('deleteLostIndexes');

        $refIndex->setSelectedTables($selectedTables);
        $refIndex->update();
    }

    /**
     * @test
     */
    public function updateDoesNothingWhenTableIsNotConfiguredInTCA()
    {
        $refIndex = $this->getMockBuilder(RefIndex::class)
            ->setMethods(['getExistingTables', 'updateTable', 'deleteLostIndexes'])
            ->getMock();
        $refIndex->expects(self::any())->method('getExistingTables')->willReturn(['table_1', 'table_2']);
        $refIndex->expects(self::never())->method('updateTable');
        $refIndex->expects(self::never())->method('deleteLostIndexes');

        $refIndex->setSelectedTables(['some_table_not_configured_in_tca']);
        $refIndex->update();
    }

    /**
     * @test
     */
    public function deleteLostIndexes()
    {
        $existingTables = ['table_1', 'table_2'];
        $refIndex = $this->getMockBuilder(RefIndex::class)
            ->setMethods(['getExistingTables'])
            ->getMock();
        $refIndex->expects(self::once())->method('getExistingTables')->willReturn($existingTables);

        $queryBuilderProphet = $this->getQueryBuilderProphet('sys_refindex');
        $queryBuilderMock = $queryBuilderProphet->reveal();

        $queryBuilderProphet->delete('sys_refindex')->shouldBeCalledOnce()->willReturn($queryBuilderMock);
        $queryBuilderProphet->where('`tablename` NOT IN (:dcValue1)')->shouldBeCalledOnce()->willReturn($queryBuilderMock);
        $queryBuilderProphet->execute()->shouldBeCalledOnce();

        $queryBuilderProphet->createNamedParameter($existingTables, Connection::PARAM_STR_ARRAY)->willReturn(':dcValue1');

        $this->callInaccessibleMethod($refIndex, 'deleteLostIndexes');
    }

    /**
     * @test
     */
    public function updateTable()
    {
        $table = 'test_table';
        $records = [['uid' => 1], ['uid' => 2]];
        $referenceIndexMock = $this->getMockBuilder(ReferenceIndex::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateRefIndexTable'])
            ->getMock();
        $referenceIndexMock->expects(self::exactly(2))->method('updateRefIndexTable')->withConsecutive(
            [$table, 1, false],
            [$table, 2, false]
        );

        $refIndex = $this->getMockBuilder(RefIndex::class)
            ->setMethods(['getReferenceIndex', 'getDeletableRecUidListFromTable'])
            ->getMock();
        $refIndex->method('getReferenceIndex')->willReturn($referenceIndexMock);
        $refIndex->method('getDeletableRecUidListFromTable')->willReturn([0]);

        $testTableQueryBuilderProphet = $this->getQueryBuilderProphet($table);
        $selectQueryBuilderMock = $testTableQueryBuilderProphet->reveal();

        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetchAll(FetchMode::ASSOCIATIVE)->shouldBeCalledOnce()->willReturn($records);

        $testTableQueryBuilderProphet->select('uid')->shouldBeCalledOnce()->willReturn($selectQueryBuilderMock);
        $testTableQueryBuilderProphet->from($table)->shouldBeCalledOnce()->willReturn($selectQueryBuilderMock);
        $testTableQueryBuilderProphet->execute()->shouldBeCalledOnce()->willReturn($statementProphet->reveal());

        $refTableQueryBuilderProphet = $this->getQueryBuilderProphet('sys_refindex');
        $refTableQueryBuilderMock = $refTableQueryBuilderProphet->reveal();

        $refTableQueryBuilderProphet->delete('sys_refindex')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->where('`tablename` = :dcValue1')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->andWhere('`recuid` IN (:dcValue2)')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->execute()->shouldBeCalledOnce();

        $refTableQueryBuilderProphet->createNamedParameter($table, PDO::PARAM_STR)->willReturn(':dcValue1');
        $refTableQueryBuilderProphet->createNamedParameter([0], Connection::PARAM_INT_ARRAY)->willReturn(':dcValue2');

        $this->callInaccessibleMethod($refIndex, 'updateTable', $table);
    }

    /**
     * @test
     */
    public function getDeletableRecUidListFromTable()
    {
        $table = 'test_table';

        $refIndex = $this->getMockBuilder(RefIndex::class)->getMock();

        $testTableQueryBuilderProphet = $this->getQueryBuilderProphet($table);
        $testTableQueryBuilderProphet->getSQL()->shouldBeCalledOnce()->willReturn('SELECT `uid` FROM `test_table`');

        $selectQueryBuilderMock = $testTableQueryBuilderProphet->reveal();

        $testTableQueryBuilderProphet->select('uid')->shouldBeCalledOnce()->willReturn($selectQueryBuilderMock);
        $testTableQueryBuilderProphet->from($table)->shouldBeCalledOnce()->willReturn($selectQueryBuilderMock);

        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetchAll(FetchMode::ASSOCIATIVE)->shouldBeCalledOnce()->willReturn([]);

        $refTableQueryBuilderProphet = $this->getQueryBuilderProphet('sys_refindex');
        $refTableQueryBuilderMock = $refTableQueryBuilderProphet->reveal();

        $refTableQueryBuilderProphet->select('recuid')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->from('sys_refindex')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->where('`tablename` = :dcValue1')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->andWhere('`recuid` NOT IN (SELECT `uid` FROM `test_table`)')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->groupBy('recuid')->shouldBeCalledOnce()->willReturn($refTableQueryBuilderMock);
        $refTableQueryBuilderProphet->execute()->shouldBeCalledOnce()->willReturn($statementProphet->reveal());

        $refTableQueryBuilderProphet->createNamedParameter($table, PDO::PARAM_STR)->willReturn(':dcValue1');

        self::assertSame([0], $this->callInaccessibleMethod($refIndex, 'getDeletableRecUidListFromTable', $table));
    }

    /**
     * @param string $table
     * @return ObjectProphecy|QueryBuilder
     */
    private function getQueryBuilderProphet(string $table)
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->will(function ($arguments) {
            return '`' . $arguments[0] . '`';
        });

        $queryRestrictionProphet = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionProphet->removeAll()->shouldBeCalled();

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->getRestrictions()->willReturn($queryRestrictionProphet->reveal());
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );

        $connectionPoolProphet = $this->getConnectionPoolProphet();
        $connectionPoolProphet->getQueryBuilderForTable($table)->willReturn($queryBuilderProphet->reveal());

        return $queryBuilderProphet;
    }

    /**
     * @return ObjectProphecy|ConnectionPool
     */
    private function getConnectionPoolProphet()
    {
        if (null === $this->connectionPoolProphet) {
            $this->connectionPoolProphet = $this->prophesize(ConnectionPool::class);
            GeneralUtility::addInstance(ConnectionPool::class, $this->connectionPoolProphet->reveal());
        }

        return $this->connectionPoolProphet;
    }
}
