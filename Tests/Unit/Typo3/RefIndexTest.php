<?php
namespace Aoe\UpdateRefindex\Tests\Unit\Typo3;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 AOE GmbH <dev@aoe.com>
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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\ReferenceIndex;

/**
 * Tests class RefIndex
 *
 * @package update_refindex
 * @subpackage Tests
 */
class RefIndexTest extends UnitTestCase
{
    /**
     * @var RefIndex
     */
    private $refIndex;
    /**
     * @var ReferenceIndex
     */
    private $referenceIndex;
    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->referenceIndex = $this->getMock(ReferenceIndex::class, array(), array(), '', false);
        $this->databaseConnection = $this->getMock(DatabaseConnection::class, array(), array(), '', false);
        $this->refIndex = $this->getMock(RefIndex::class, array('getReferenceIndex', 'getExistingTables', 'getDatabaseConnection'));
        $this->refIndex
            ->expects($this->any())
            ->method('getReferenceIndex')
            ->willReturn($this->referenceIndex);
        $this->refIndex
            ->expects($this->any())
            ->method('getDatabaseConnection')
            ->willReturn($this->databaseConnection);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        unset($this->refIndex);
        unset($this->referenceIndex);
        unset($this->databaseConnection);
    }

    /**
     * Test method update
     *
     * @test
     */
    public function update()
    {
        $selectedTables = array('tablename1', 'tablename2');
        $recordsOfTable1 = array(array('uid' => 10), array('uid' => 20));
        $recordsOfTable2 = array(array('uid' => 70), array('uid' => 80), array('uid' => 90));

        /**
         * define behaviour of object refIndex
         */
        $this->refIndex->expects($this->any())->method('getExistingTables')->willReturn($selectedTables);

        /**
         * define behaviour of object DatabaseConnection
         */
        // 1.1. find all records of table1
        $this->databaseConnection
            ->expects($this->at(0))
            ->method('exec_SELECTgetRows')
            ->with('uid', $selectedTables[0], '1=1')
            ->willReturn($recordsOfTable1);
        // 1.2. Searching lost indexes of table1
        $this->databaseConnection
            ->expects($this->at(1))
            ->method('fullQuoteStr')
            ->with($selectedTables[0], 'sys_refindex')
            ->willReturn($selectedTables[0]);
        $this->databaseConnection
            ->expects($this->at(2))
            ->method('exec_DELETEquery')
            ->with('sys_refindex', 'tablename=' . $selectedTables[0] . ' AND recuid NOT IN (0,' . $recordsOfTable1[0]['uid'] . ',' . $recordsOfTable1[1]['uid'] . ')');
        // 2.1. find all records of table2
        $this->databaseConnection
            ->expects($this->at(3))
            ->method('exec_SELECTgetRows')
            ->with('uid', $selectedTables[1], '1=1')
            ->willReturn($recordsOfTable2);
        // 2.2. Searching lost indexes of table2
        $this->databaseConnection
            ->expects($this->at(4))
            ->method('fullQuoteStr')
            ->with($selectedTables[1], 'sys_refindex')
            ->willReturn($selectedTables[1]);
        $this->databaseConnection
            ->expects($this->at(5))
            ->method('exec_DELETEquery')
            ->with('sys_refindex', 'tablename=' . $selectedTables[1] . ' AND recuid NOT IN (0,' . $recordsOfTable2[0]['uid'] . ',' . $recordsOfTable2[1]['uid'] . ',' . $recordsOfTable2[2]['uid'] . ')');
        // 3. delete lost indexes for non existing tables
        $this->databaseConnection
            ->expects($this->at(6))
            ->method('fullQuoteArray')
            ->with($selectedTables, 'sys_refindex')
            ->willReturn($selectedTables);
        $this->databaseConnection
            ->expects($this->at(7))
            ->method('exec_DELETEquery')
            ->with('sys_refindex', 'tablename NOT IN (' . implode(',', $selectedTables) . ')');

        /**
         * define behaviour of object ReferenceIndex
         */
        $this->referenceIndex
            ->expects($this->at(0))
            ->method('updateRefIndexTable')
            ->with($selectedTables[0], $recordsOfTable1[0]['uid'], false);
        $this->referenceIndex
            ->expects($this->at(1))
            ->method('updateRefIndexTable')
            ->with($selectedTables[0], $recordsOfTable1[1]['uid'], false);
        $this->referenceIndex
            ->expects($this->at(2))
            ->method('updateRefIndexTable')
            ->with($selectedTables[1], $recordsOfTable2[0]['uid'], false);
        $this->referenceIndex
            ->expects($this->at(3))
            ->method('updateRefIndexTable')
            ->with($selectedTables[1], $recordsOfTable2[1]['uid'], false);
        $this->referenceIndex
            ->expects($this->at(4))
            ->method('updateRefIndexTable')
            ->with($selectedTables[1], $recordsOfTable2[2]['uid'], false);

        // do test
        $this->refIndex->setSelectedTables($selectedTables);
        $this->refIndex->update();
    }
}
