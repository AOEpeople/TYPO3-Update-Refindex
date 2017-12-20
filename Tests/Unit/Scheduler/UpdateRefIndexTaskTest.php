<?php
namespace Aoe\UpdateRefindex\Tests\Unit\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 AOE GmbH <dev@aoe.com>
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

use Aoe\UpdateRefindex\Scheduler\UpdateRefIndexTask;
use Aoe\UpdateRefindex\Typo3\RefIndex;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Tests class UpdateRefIndexTask
 *
 * @package update_refindex
 * @subpackage Tests
 */
class UpdateRefIndexTaskTest extends UnitTestCase
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var RefIndex
     */
    protected $refIndex;

    /**
     * @var UpdateRefIndexTask
     */
    protected $task;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        // Store TYPO3_DB in a local variable, as it will be substituted with a mock in this test
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];

        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class, array(), array(), '', false);

        $this->refIndex = $this->getMock(RefIndex::class, array(), array(), '', false);

        $this->task = $this->getMock(UpdateRefIndexTask::class, array('getRefIndex'));
        $this->task->expects($this->any())->method('getRefIndex')->willReturn($this->refIndex);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // Restore TYPO3_DB
        $GLOBALS['TYPO3_DB'] = $this->databaseConnection;

        unset($this->databaseConnection);
        unset($this->refIndex);
        unset($this->task);
    }

    /**
     * @test
     */
    public function executeWithSelectedTablesWillHandleSelectedTables()
    {
        $selectedTables = array('table1', 'table2');

        $this->refIndex
            ->expects($this->once())
            ->method('setSelectedTables')
            ->with($selectedTables)
            ->willReturn($this->refIndex);
        $this->refIndex
            ->expects($this->once())->method('update');

        $this->task->setSelectedTables($selectedTables);
        $this->task->execute();
    }

    /**
     * @test
     */
    public function executeWithUpdateAllTablesWillHandleAllExistingTables()
    {
        $allTables = array('table1', 'table2', 'table3', 'table4', 'table5');

        $this->refIndex
            ->expects($this->any())
            ->method('getExistingTables')
            ->willReturn($allTables);
        $this->refIndex
            ->expects($this->once())
            ->method('setSelectedTables')
            ->with($allTables)
            ->willReturn($this->refIndex);
        $this->refIndex
            ->expects($this->once())->method('update');

        $this->task->setUpdateAllTables(true);
        $this->task->execute();
    }
}
