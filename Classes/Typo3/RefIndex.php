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

require_once(PATH_t3lib.'class.t3lib_refindex.php');

/**
 * scheduler-task to update refindex of TYPO3
 * 
 * @package update_refindex
 * @subpackage Typo3
 */
class Tx_UpdateRefindex_Typo3_RefIndex {
	/**
	 * @var array
	 */
	private $existingTables;
	/**
	 * @var array
	 */
	private $selectedTables = array();

	/**
	 * @param	array $selectedTables
	 * @return	Tx_UpdateRefindex_Typo3_RefIndex
	 */
	public function setSelectedTables(array $selectedTables) {
		$this->selectedTables = $selectedTables;
		return $this;
	}
	/**
	 * update refIndex of selected tables
	 */
	public function update() {
		foreach($this->getSelectedTables() as $selectedTable) {
			if(array_search($selectedTable, $this->getExistingTables()) !== FALSE) {
				$this->updateTable( $selectedTable );
			}
		}
	}

	/**
	 * @return t3lib_refindex
	 */
	protected function createT3libRefindex() {
		return t3lib_div::makeInstance('t3lib_refindex');
	}
	/**
	 * @return array
	 */
	protected function getExistingTables() {
		if($this->existingTables === NULL) {
			global $TCA;
			$this->existingTables = array_keys( $TCA );
			sort($this->existingTables);
		}
		return $this->existingTables;
	}
	/**
	 * @return array
	 */
	protected function getSelectedTables() {
		return $this->selectedTables;
	}
	/**
	 * @return t3lib_DB
	 */
	protected function getTypo3Db() {
		global $TYPO3_DB;
		return $TYPO3_DB;
	}
	/**
	 * update table
	 * this code is inspired by the code of method 'updateIndex' in class 't3lib_refindex'
	 * 
	 * @param string $tableName
	 */
	protected function updateTable($tableName) {
		// Traverse all records in table, including deleted records:
		$allRecs = $this->getTypo3Db()->exec_SELECTgetRows('uid',$tableName,'1=1');	//.t3lib_BEfunc::deleteClause($tableName)
		$uidList = array(0);
		foreach ($allRecs as $recdat)	{
			$result = $this->createT3libRefindex()->updateRefIndexTable($tableName,$recdat['uid'],FALSE);
			$uidList[]= $recdat['uid'];
		}

		// Searching lost indexes for this table:
		$where = 'tablename='.$this->getTypo3Db()->fullQuoteStr($tableName,'sys_refindex').' AND recuid NOT IN ('.implode(',',$uidList).')';
		$lostIndexes = $this->getTypo3Db()->exec_SELECTgetRows('hash','sys_refindex',$where);
		if (count($lostIndexes))	{
			$this->getTypo3Db()->exec_DELETEquery('sys_refindex',$where);
		}

		// Searching lost indexes for non-existing tables:
		$where = 'tablename NOT IN ('.implode(',',$this->getTypo3Db()->fullQuoteArray(array($tableName),'sys_refindex')).')';
		$lostTables = $this->getTypo3Db()->exec_SELECTgetRows('hash','sys_refindex',$where);
		if (count($lostTables))	{
			$this->getTypo3Db()->exec_DELETEquery('sys_refindex',$where);
		}
	}
}