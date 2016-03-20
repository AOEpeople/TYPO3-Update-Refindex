<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 AOE media GmbH <dev@aoemedia.de>
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
class Tx_UpdateRefindex_Scheduler_UpdateRefIndexTaskTest extends tx_phpunit_testcase
{
    /**
     * @var Tx_UpdateRefindex_Typo3_RefIndex
     */
    private $refIndex;
    /**
     * @var Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask
     */
    private $task;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->refIndex = $this->getMock('Tx_UpdateRefindex_Typo3_RefIndex', array(), array(), '', false);
        $this->task = $this->getMock('Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask', array('getRefIndex'));
        $this->task->expects($this->any())->method('getRefIndex')->will($this->returnValue($this->refIndex));
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
     * Test method execute
     * @test
     */
    public function execute()
    {
        $selectedTables = array('tablename1','tablename2');

        $this->refIndex->expects($this->once())->method('setSelectedTables')->with($selectedTables)->will($this->returnValue($this->refIndex));
        $this->refIndex->expects($this->once())->method('update');

        $this->task->setSelectedTables($selectedTables);
        $this->task->execute();
    }
}
