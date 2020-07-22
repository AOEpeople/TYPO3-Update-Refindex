<?php
namespace Aoe\UpdateRefindex\Typo3;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 AOE GmbH <dev@aoe.com>
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

use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var array
     */
    private $existingTables;

    /**
     * @var array
     */
    private $selectedTables = [];

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
        $queryBuilder = $this->getQueryBuilderForTable('sys_refindex');
        $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn(
                    'tablename',
                    $queryBuilder->createNamedParameter($this->getExistingTables(), Connection::PARAM_STR_ARRAY)
                )
            )
            ->execute();
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
        $queryBuilder = $this->getQueryBuilderForTable($tableName);
        $allRecs = $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        $uidList = [0];
        foreach ($allRecs as $recdat) {
            $this->getReferenceIndex()->updateRefIndexTable($tableName, $recdat['uid']);
            $uidList[] = (int) $recdat['uid'];
        }

        // Searching lost indexes for this table:
        $queryBuilder = $this->getQueryBuilderForTable('sys_refindex');
        $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($tableName, PDO::PARAM_STR)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->notIn(
                    'recuid',
                    $queryBuilder->createNamedParameter($uidList, Connection::PARAM_INT_ARRAY)
                )
            )
            ->execute();
    }

    /**
     * @param string $table
     * @param bool   $useEnableFields
     * @return QueryBuilder
     */
    private function getQueryBuilderForTable(string $table, bool $useEnableFields = false): QueryBuilder
    {
        $connectionPool = $this->getConnectionPool();
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);

        if (false === $useEnableFields) {
            $queryBuilder->getRestrictions()->removeAll();
        }

        return $queryBuilder;
    }

    /**
     * @return ConnectionPool
     */
    private function getConnectionPool(): ConnectionPool
    {
        if (null === $this->connectionPool) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }

        return $this->connectionPool;
    }
}
