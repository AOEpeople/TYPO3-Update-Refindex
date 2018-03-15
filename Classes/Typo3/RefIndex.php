<?php
namespace Aoe\UpdateRefindex\Typo3;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * scheduler-task to update refindex of TYPO3
 *
 * @package update_refindex
 * @subpackage Typo3
 */
class RefIndex
{
    /**
     * @var array
     */
    private $existingTables;

    /**
     * @var array
     */
    private $selectedTables = array();

    /**
     * @param array $selectedTables
     * @return RefIndex
     */
    public function setSelectedTables(array $selectedTables)
    {
        $this->selectedTables = $selectedTables;

        return $this;
    }

    /**
     * @return array
     */
    public function getSelectedTables()
    {
        return $this->selectedTables;
    }

    /**
     * @return array
     */
    public function getExistingTables()
    {
        if ($this->existingTables === null) {
            $this->existingTables = array_keys($GLOBALS['TCA']);
            sort($this->existingTables);
        }

        return $this->existingTables;
    }

    /**
     * update refIndex of selected tables
     */
    public function update()
    {
        // update index of selected tables
        foreach ($this->getSelectedTables() as $selectedTable) {
            if (array_search($selectedTable, $this->getExistingTables()) !== false) {
                $this->updateTable($selectedTable);
            }
        }

        // delete lost indexes ONLY if index of ALL tables where updated
        if (count($this->getExistingTables()) === count($this->getSelectedTables())) {
            $this->deleteLostIndexes();
        }
    }

    /**
     * @return ReferenceIndex
     */
    protected function getReferenceIndex()
    {
        return GeneralUtility::makeInstance(ReferenceIndex::class);
    }

    /**
     * Searching lost indexes for non-existing tables
     * this code is inspired by the code of method 'updateIndex' in class '\TYPO3\CMS\Core\Database\ReferenceIndex'
     */
    protected function deleteLostIndexes()
    {
        $where = 'tablename NOT IN (' . implode(',',
                $this->getDatabaseConnection()->fullQuoteArray($this->getExistingTables(), 'sys_refindex')) . ')';
        $this->getDatabaseConnection()->exec_DELETEquery('sys_refindex', $where);
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * update table
     * this code is inspired by the code of method 'updateIndex' in class '\TYPO3\CMS\Core\Database\ReferenceIndex'
     *
     * @param string $tableName
     */
    protected function updateTable($tableName)
    {
        // Traverse all records in table, including deleted records:
        $allRecs = $this->getDatabaseConnection()
            ->exec_SELECTgetRows('uid', $tableName, '1=1'); //.TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName)
        $uidList = array(0);
        foreach ($allRecs as $recdat) {
            $this->getReferenceIndex()->updateRefIndexTable($tableName, $recdat['uid']);
            $uidList[] = $recdat['uid'];
        }

        // Searching lost indexes for this table:
        $where = 'tablename=' . $this->getDatabaseConnection()
                ->fullQuoteStr($tableName, 'sys_refindex') . ' AND recuid NOT IN (' . implode(',', $uidList) . ')';
        $this->getDatabaseConnection()->exec_DELETEquery('sys_refindex', $where);
    }
}
