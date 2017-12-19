<?php
namespace Aoe\UpdateRefindex\Scheduler;

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

use Aoe\UpdateRefindex\Typo3\RefIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * scheduler-task to update refindex of TYPO3
 *
 * @package update_refindex
 * @subpackage Scheduler
 */
class UpdateRefIndexTask extends AbstractTask
{
    /**
     * Boolean flag indicating if all existing tables should be processed
     *
     * @var boolean
     */
    public $updateAllTables = false;

    /**
     * Comma separated list of tables
     *
     * @var string
     */
    public $updateRefindexSelectedTables;

    /**
     * @var RefIndex
     */
    private $refIndex;

    /**
     * execute the task
     *
     * @return boolean
     */
    public function execute()
    {
        $shellExitCode = true;
        try {
            $selectedTables = $this->isUpdateAllTables() ? $this->getRefIndex()->getExistingTables() : $this->getSelectedTables();
            $this->getRefIndex()->setSelectedTables($selectedTables)->update();
        } catch (\Exception $e) {
            $shellExitCode = false;
        }

        return $shellExitCode;
    }

    /**
     * @return boolean
     */
    public function isUpdateAllTables()
    {
        return $this->updateAllTables;
    }

    /**
     * @param boolean $updateAllTables
     */
    public function setUpdateAllTables($updateAllTables)
    {
        $this->updateAllTables = $updateAllTables;
    }

    /**
     * @return array
     */
    public function getSelectedTables()
    {
        return explode(',', $this->updateRefindexSelectedTables);
    }

    /**
     * @param array $selectedTables
     */
    public function setSelectedTables(array $selectedTables)
    {
        $this->updateRefindexSelectedTables = implode(',', $selectedTables);
    }

    /**
     * @return RefIndex
     */
    protected function getRefIndex()
    {
        if ($this->refIndex === null) {
            $this->refIndex = GeneralUtility::makeInstance(RefIndex::class);
        }

        return $this->refIndex;
    }
}
