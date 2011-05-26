<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Max Beer <max.beer@aoemedia.de>, AOE media GmbH
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

require_once(t3lib_extMgm::extPath('scheduler').'interfaces/interface.tx_scheduler_additionalfieldprovider.php');

/**
 * class to define the additional field 'expirationDurationForDeletion'
 */
class tx_UpdateRefindex_Scheduler_UpdateRefIndexAdditionalFields implements tx_scheduler_AdditionalFieldProvider {
	/**
	 * Field name constants
	 */
	const FIELD_SELECTED_TABLES = 'updateRefindexSelectedTables';

	/** Locallang reference */
	const LL_REFERENCE = 'LLL:EXT:update_refindex/Resources/Private/Language/locallang_db.xml';

	/**
	 * @param array &$taskInfo
	 * @param unknown_type $task
	 * @param tx_scheduler_Module $parentObject
	 * @return unknown
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		if ($parentObject->CMD == 'add') {
			$taskInfo[self::FIELD_SELECTED_TABLES] = '';
		} elseif($parentObject->CMD == 'edit') {
			$taskInfo[self::FIELD_SELECTED_TABLES] = $task->getSelectedTables();
		} else {
			$taskInfo[self::FIELD_SELECTED_TABLES] = '';
		}

		# Get configuration (markup & labels) for additional fields
		$optionsSelectedTables = array();
		foreach( $this->getExistingTables() as $existingTable) {
			$optionsSelectedTables[$existingTable] = $existingTable;
		}

		$additionalFields = array(
			self::FIELD_SELECTED_TABLES => array(
				'code' => $this->getSelector(self::FIELD_SELECTED_TABLES, $optionsSelectedTables, $taskInfo[self::FIELD_SELECTED_TABLES]),
				'label' => $GLOBALS['LANG']->sL(self::LL_REFERENCE.':scheduler_task.updateRefindex.fieldSelectedTables.label')
			),
		);

		return $additionalFields;
	}
	
    /**
     * @param array $submittedData
     * @param tx_scheduler_Task $task
     */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->setSelectedTables( $submittedData[self::FIELD_SELECTED_TABLES] );
	}
	
	/**
	 * @param array &$submittedData
	 * @param tx_scheduler_Module $parentObject
	 * @return boolean
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		global $TCA;

		// transform array to string (because we must save the data as string)
		$submittedData[self::FIELD_SELECTED_TABLES] = implode(',', $submittedData[self::FIELD_SELECTED_TABLES]);

		$fieldSelectedTablesIsValid = TRUE;

		// check, if field 'selectedTables' is valid
		if(empty($submittedData[self::FIELD_SELECTED_TABLES])) {
			$fieldSelectedTablesIsValid = FALSE;
			$parentObject->addMessage($GLOBALS['LANG']->sL(self::LL_REFERENCE.':scheduler_task.updateRefindex.fieldSelectedTables.invalid.isEmpty'), t3lib_FlashMessage::ERROR);
		} else {
			$existingTables = $this->getExistingTables();
			$selectedTables = explode(',', $submittedData[self::FIELD_SELECTED_TABLES]);
			foreach($selectedTables as $selectedTable) {
				if(array_search($selectedTable, $existingTables) === FALSE) {
					$fieldSelectedTablesIsValid = FALSE;
					$errorMessage = $GLOBALS['LANG']->sL(self::LL_REFERENCE.':scheduler_task.updateRefindex.fieldSelectedTables.invalid.tableNotExists');
					$errorMessage = str_replace('###NOT_EXISTING_TABLE###', $selectedTable, $errorMessage);
					$parentObject->addMessage($errorMessage, t3lib_FlashMessage::ERROR);
				}
			}
		}

		return $fieldSelectedTablesIsValid;
	}

	/**
	 * @return array
	 */
	private function getExistingTables() {
		global $TCA;
		$existingTables = array_keys( $TCA );
		sort($existingTables);
		return $existingTables;
	}
	/**
	 * Generates HTML selector for provided options.
	 * 
	 * @param  string	$name		Selector name ( HTML name attribute ).
	 * @param  array	$options	Key value pair of option value & corresponding label.
	 * @param  string	$selected	Currently selected option value.
	 * @return string				Select tag HTML. 
	 */
	private function getSelector( $name, array $options, $selected) {
		// transform data from string to array
		$selected = explode(',', $selected);

		$contentArray = array( '<select id="task_'.$name.'" name="tx_scheduler['.$name.'][]" size="20" multiple="multiple">' );

		if ( 0 < count($options) ) {
			foreach ( $options as $value => $label ) {
				if(is_array($selected)) {
					$optionIsSelected = in_array($value, $selected);
				} else {
					$optionIsSelected = $value === $selected;
				}

				$selectAttribute = ($optionIsSelected) ? ' selected="selected"' : '';
				$contentArray[] = '<option value="'.$value.'"'.$selectAttribute.'>'.$label.'</option>';
			}
		} else {
			$contentArray[] = '<option value=""></option>';
		}

		$contentArray[] = '</select>';
		$content = implode( "\n", $contentArray );
		
		return $content;
	}
}