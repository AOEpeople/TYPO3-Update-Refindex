<?php
namespace AOE\UpdateRefindex\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 AOE GmbH <dev@aoe.com>
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
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * class to define additional fields
 *
 * @package update_refindex
 * @subpackage Scheduler
 */
class UpdateRefIndexAdditionalFields implements AdditionalFieldProviderInterface
{
    /**
     * Field name constants
     */
    const FIELD_SELECTED_TABLES = 'updateRefindexSelectedTables';

    /** Locallang reference */
    const LL_REFERENCE = 'LLL:EXT:update_refindex/Resources/Private/Language/locallang_db.xml';

    /**
     * @param array &$taskInfo
     * @param unknown_type $task
     * @param SchedulerModuleController $parentObject
     * @return unknown
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject)
    {
        // define value for fields
        if ($parentObject->CMD == 'add') {
            $taskInfo[self::FIELD_SELECTED_TABLES] = array();
        } elseif ($parentObject->CMD == 'edit') {
            $taskInfo[self::FIELD_SELECTED_TABLES] = $task->getSelectedTables();
        } else {
            $taskInfo[self::FIELD_SELECTED_TABLES] = array();
        }

        // Get configuration (markup & labels) for additional fields
        $additionalFields = array(
            self::FIELD_SELECTED_TABLES => array(
                'code' => $this->getSelectBox($taskInfo[self::FIELD_SELECTED_TABLES]),
                'label' => $GLOBALS['LANG']->sL(self::LL_REFERENCE . ':scheduler_task.updateRefindex.fieldSelectedTables.label')
            ),
        );

        return $additionalFields;
    }

    /**
     * @param array $submittedData
     * @param AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->setSelectedTables($submittedData[self::FIELD_SELECTED_TABLES]);
    }

    /**
     * @param array &$submittedData
     * @param SchedulerModuleController $parentObject
     * @return boolean
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject)
    {
        $fieldSelectedTablesIsValid = true;

        // check, if field 'selectedTables' is valid
        if (count($submittedData[self::FIELD_SELECTED_TABLES]) === 0) {
            $fieldSelectedTablesIsValid = false;
            $parentObject->addMessage(
                $GLOBALS['LANG']->sL(
                    self::LL_REFERENCE . ':scheduler_task.updateRefindex.fieldSelectedTables.invalid.isEmpty'
                ),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }

        return $fieldSelectedTablesIsValid;
    }

    /**
     * get array with tables, which can be selected as options
     *
     * @return array
     */
    private function getOptionsForSelectBox()
    {
        $existingTables = array_keys($GLOBALS['TCA']);
        sort($existingTables);

        $optionsSelectedTables = array();
        foreach ($existingTables as $existingTable) {
            $optionsSelectedTables[$existingTable] = $existingTable;
        }
        return $optionsSelectedTables;
    }

    /**
     * Generates HTML selectbox for field 'selectedTables'.
     *
     * @param  array $selected Currently selected option value.
     * @return string                Select tag HTML.
     */
    private function getSelectBox(array $selected)
    {
        $contentArray = array(
            '<select id="task_' . self::FIELD_SELECTED_TABLES
            . '" name="tx_scheduler[' . self::FIELD_SELECTED_TABLES . '][]" size="20" multiple="multiple">'
        );

        foreach ($this->getOptionsForSelectBox() as $value => $label) {
            $selectAttribute = in_array($value, $selected) ? ' selected="selected"' : '';
            $contentArray[] = '<option value="' . $value . '"' . $selectAttribute . '>' . $label . '</option>';
        }

        $contentArray[] = '</select>';
        $content = implode("\n", $contentArray);

        return $content;
    }
}
