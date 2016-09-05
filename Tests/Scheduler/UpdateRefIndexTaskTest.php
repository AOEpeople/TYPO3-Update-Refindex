<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE media GmbH <dev@aoemedia.de>
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

/**
 * Tests class Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask
 *
 * @package update_refindex
 * @subpackage Tests
 */
class Tx_UpdateRefindex_Scheduler_UpdateRefIndexTaskTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var Tx_UpdateRefindex_Typo3_RefIndex
     */
    protected $refIndex;

    /**
     * @var Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask
     */
    protected $task;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        // Store TYPO3_DB in a local variable, as it will be substituted with a mock in this test
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];

        $GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', false);

        $this->refIndex = $this->getMock('Tx_UpdateRefindex_Typo3_RefIndex', array(), array(), '', false);

        $this->task = $this->getMock('Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask', array('getRefIndex'));
        $this->task->expects($this->any())->method('getRefIndex')->will($this->returnValue($this->refIndex));
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
            ->will($this->returnValue($this->refIndex));
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
            ->will($this->returnValue($allTables));
        $this->refIndex
            ->expects($this->once())
            ->method('setSelectedTables')
            ->with($allTables)
            ->will($this->returnValue($this->refIndex));
        $this->refIndex
            ->expects($this->once())->method('update');

        $this->task->setUpdateAllTables(true);
        $this->task->execute();
    }
}
