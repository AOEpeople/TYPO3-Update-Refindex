<?php
namespace Aoe\UpdateRefindex\Tests\Unit\Scheduler;

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

use Aoe\UpdateRefindex\Scheduler\UpdateRefIndexTask;
use Aoe\UpdateRefindex\Typo3\RefIndex;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Tests class UpdateRefIndexTask
 *
 * @package update_refindex
 * @subpackage Tests
 */
class UpdateRefIndexTaskTest extends UnitTestCase
{
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
        $this->refIndex = $this->getMockBuilder(RefIndex::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExistingTables', 'setSelectedTables', 'update'])
            ->getMock();

        $this->task = $this->getMockBuilder(UpdateRefIndexTask::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRefIndex'])
            ->getMock();

        $this->task->expects(self::any())->method('getRefIndex')->willReturn($this->refIndex);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        unset($this->refIndex);
        unset($this->task);
    }

    /**
     * @test
     */
    public function executeWithSelectedTablesWillHandleSelectedTables()
    {
        $selectedTables = ['table1', 'table2'];

        $this->refIndex
            ->expects(self::once())
            ->method('setSelectedTables')
            ->with($selectedTables)
            ->willReturn($this->refIndex);
        $this->refIndex
            ->expects(self::once())->method('update');

        $this->task->setSelectedTables($selectedTables);
        $this->task->execute();
    }

    /**
     * @test
     */
    public function executeWithUpdateAllTablesWillHandleAllExistingTables()
    {
        $allTables = ['table1', 'table2', 'table3', 'table4', 'table5'];

        $this->refIndex
            ->expects(self::any())
            ->method('getExistingTables')
            ->willReturn($allTables);
        $this->refIndex
            ->expects(self::once())
            ->method('setSelectedTables')
            ->with($allTables)
            ->willReturn($this->refIndex);
        $this->refIndex
            ->expects(self::once())->method('update');

        $this->task->setUpdateAllTables(true);
        $this->task->execute();
    }
}
