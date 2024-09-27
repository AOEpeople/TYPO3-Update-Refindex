<?php

declare(strict_types=1);

namespace Aoe\UpdateRefindex\Typo3;

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

use Doctrine\DBAL\ArrayParameterType;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RefIndex
{
    /**
     * @var int
     */
    public const ARRAY_CHUNK_SIZE = 100;

    private ?ConnectionPool $connectionPool = null;

    private array $existingTables = [];

    private array $selectedTables = [];

    private ?ReferenceIndex $referenceIndex = null;

    public function setSelectedTables(array $selectedTables): self
    {
        $this->selectedTables = $selectedTables;

        return $this;
    }

    public function getSelectedTables(): array
    {
        return $this->selectedTables;
    }

    public function getExistingTables(): array
    {
        if ($this->existingTables === []) {
            $this->existingTables = array_keys($GLOBALS['TCA']);
            sort($this->existingTables);
        }

        return $this->existingTables;
    }

    /**
     * update refIndex of selected tables
     */
    public function update(): void
    {
        // update index of selected tables
        foreach ($this->selectedTables as $selectedTable) {
            if (in_array($selectedTable, $this->getExistingTables(), true)) {
                $this->updateTable($selectedTable);
            }
        }

        // delete lost indexes ONLY if index of ALL tables where updated
        if (count($this->getExistingTables()) === count($this->selectedTables)) {
            $this->deleteLostIndexes();
        }
    }

    /**
     * Searching lost indexes for non-existing tables
     * this code is inspired by the code of method 'updateIndex' in class '\TYPO3\CMS\Core\Database\ReferenceIndex'
     */
    protected function deleteLostIndexes(): void
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_refindex');
        $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()
                    ->notIn(
                        'tablename',
                        $queryBuilder->createNamedParameter($this->getExistingTables(), ArrayParameterType::INTEGER)
                    )
            );
        $queryBuilder->executeStatement();
    }

    protected function updateTable(string $tableName): void
    {
        // Select all records from table, including deleted records
        $queryBuilder = $this->getQueryBuilderForTable($tableName);
        $allRecs = $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->executeQuery()
            ->fetchAllAssociative();

        // Update refindex table for all records in table
        foreach ($allRecs as $recdat) {
            $this->getReferenceIndex()
                ->updateRefIndexTable($tableName, $recdat['uid']);
        }

        $recUidList = $this->getDeletableRecUidListFromTable($tableName);
        if ($recUidList !== []) {
            // Searching lost indexes for this table:
            $queryBuilder = $this->getQueryBuilderForTable('sys_refindex');
            foreach (array_chunk($recUidList, self::ARRAY_CHUNK_SIZE) as $recUidChunk) {
                $queryBuilder
                    ->delete('sys_refindex')
                    ->where(
                        $queryBuilder->expr()
                            ->eq(
                                'tablename',
                                $queryBuilder->createNamedParameter($tableName, PDO::PARAM_STR)
                            )
                    )
                    ->andWhere(
                        $queryBuilder->expr()
                            ->in(
                                'recuid',
                                $queryBuilder->createNamedParameter($recUidChunk, ArrayParameterType::INTEGER)
                            )
                    );
                $queryBuilder->executeStatement();
            }
        }
    }

    protected function getDeletableRecUidListFromTable(string $tableName): array
    {
        // Select all records from table, including deleted records
        $subQueryBuilder = $this->getQueryBuilderForTable($tableName);
        $subQueryBuilder
            ->select('uid')
            ->from($tableName);

        // Select all records from sys_refindex which are not in $tableName, including deleted records
        $queryBuilder = $this->getQueryBuilderForTable('sys_refindex');
        $queryBuilder
            ->select('recuid')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()
                    ->eq('tablename', $queryBuilder->createNamedParameter($tableName, PDO::PARAM_STR))
            )
            ->andWhere($queryBuilder->expr()->notIn('recuid', $subQueryBuilder->getSQL()))
            ->groupBy('recuid');

        $allRecs = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        $recUidList = [0];
        foreach ($allRecs as $recdat) {
            $recUidList[] = (int) $recdat['recuid'];
        }

        return $recUidList;
    }

    protected function getReferenceIndex(): ReferenceIndex
    {
        if ($this->referenceIndex === null) {
            $this->referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        }

        return $this->referenceIndex;
    }

    private function getQueryBuilderForTable(string $table, bool $useEnableFields = false): QueryBuilder
    {
        $connectionPool = $this->getConnectionPool();
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);

        if (!$useEnableFields) {
            $queryBuilder->getRestrictions()
                ->removeAll();
        }

        return $queryBuilder;
    }

    private function getConnectionPool(): ConnectionPool
    {
        if ($this->connectionPool === null) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }

        return $this->connectionPool;
    }
}
