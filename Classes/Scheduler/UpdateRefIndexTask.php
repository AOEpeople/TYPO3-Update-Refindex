<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 AOE media GmbH <dev@aoemedia.de>
*  All rights reserved
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('scheduler') . 'class.tx_scheduler_task.php');

/**
 * scheduler-task to update refindex of TYPO3
 * 
 * @package update_refindex
 * @subpackage Typo3
 */
class Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask extends tx_scheduler_Task {
	/**
	 * execute the task
	 * @return boolean
	 */
	public function execute() {
		$shellExitCode = TRUE;
		try {
			// update refIndex
		} catch (Exception $e) {
			$shellExitCode = FALSE;
		}
		return $shellExitCode;
	}

	/**
	 * @return string
	 */
	public function getSelectedTables() {
		return $this->updateRefindexSelectedTables;
	}

	/**
	 * @param string $selectedTables
	 */
	public function setSelectedTables($selectedTables) {
		$this->updateRefindexSelectedTables = $selectedTables;
	}
}