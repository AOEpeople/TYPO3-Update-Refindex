<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
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
 * class to define additional fields
 *
 * @package update_refindex
 * @subpackage Scheduler
 */
class tx_UpdateRefindex_Scheduler_UpdateRefIndexAdditionalFields implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Field name constants
     */
    const FIELD_ALL_TABLES = 'updateRefindexAllTables';
    const FIELD_SELECTED_TABLES = 'updateRefindexSelectedTables';

    /** Locallang reference */
    const LL_REFERENCE = 'LLL:EXT:update_refindex/Resources/Private/Language/locallang_db.xml';

    /**
     * @param array &$taskInfo
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
     * @return array
     */
    public function getAdditionalFields(
        array &$taskInfo,
        $task,
        \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
    ) {
        /** @var Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask $task */

        // define value for fields
        if ($parentObject->CMD == 'add') {
            $taskInfo[self::FIELD_ALL_TABLES] = false;
            $taskInfo[self::FIELD_SELECTED_TABLES] = array();
        } elseif ($parentObject->CMD == 'edit') {
            $taskInfo[self::FIELD_ALL_TABLES] = $task->isUpdateAllTables();
            $taskInfo[self::FIELD_SELECTED_TABLES] = $task->getSelectedTables();
        } else {
            $taskInfo[self::FIELD_ALL_TABLES] = false;
            $taskInfo[self::FIELD_SELECTED_TABLES] = array();
        }

        // Get configuration (markup & labels) for additional fields
        $additionalFields = array(
            self::FIELD_ALL_TABLES => array(
                'code' => $this->getCheckbox($taskInfo[self::FIELD_ALL_TABLES]),
                'label' => $GLOBALS['LANG']->sL(self::LL_REFERENCE . ':scheduler_task.updateRefindex.fieldUpdateAllTables.label')
            ),
            self::FIELD_SELECTED_TABLES => array(
                'code' => $this->getSelectBox($taskInfo[self::FIELD_SELECTED_TABLES]),
                'label' => $GLOBALS['LANG']->sL(self::LL_REFERENCE . ':scheduler_task.updateRefindex.fieldSelectedTables.label')
            ),
        );

        return $additionalFields;
    }

    /**
     * @param array $submittedData
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        /** @var Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask $task */
        $task->setUpdateAllTables((boolean)$submittedData[self::FIELD_ALL_TABLES]);
        $task->setSelectedTables((array)$submittedData[self::FIELD_SELECTED_TABLES]);
    }

    /**
     * @param array &$submittedData
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
     * @return boolean
     */
    public function validateAdditionalFields(
        array &$submittedData,
        \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject
    ) {
        $isValid = true;

        if (!isset($submittedData[self::FIELD_ALL_TABLES])
            || !\TYPO3\CMS\Core\Utility\MathUtility::isIntegerInRange((integer)$submittedData[self::FIELD_ALL_TABLES], 0, 1)
        ) {
            $isValid = false;
        }

        if (isset($submittedData[self::FIELD_SELECTED_TABLES])
            && count($submittedData[self::FIELD_SELECTED_TABLES]) === 0
        ) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Gets the HTML markup of a checkbox input field
     *
     * @param boolean $isChecked
     * @return string
     */
    private function getCheckbox($isChecked)
    {
        $checked = true === $isChecked ? 'checked="checked" ' : '';
        $content = '<input type="hidden" name="tx_scheduler[' . self::FIELD_ALL_TABLES . ']" value="0" />';
        $content .= '<input type="checkbox" ' . $checked . 'value="1"'
            . ' name="tx_scheduler[' . self::FIELD_ALL_TABLES . ']"'
            . ' id="task_' . self::FIELD_ALL_TABLES . '" />';

        return $content;
    }

    /**
     * Gets array with tables, which can be selected as options
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
     * Generates HTML selectbox for field 'selectedTables'
     *
     * @param array $selected
     * @return string
     */
    private function getSelectBox(array $selected)
    {
        $contentArray = array('<select id="task_' . self::FIELD_SELECTED_TABLES . '" name="tx_scheduler[' . self::FIELD_SELECTED_TABLES . '][]" size="20" multiple="multiple" class="form-control">');

        foreach ($this->getOptionsForSelectBox() as $value => $label) {
            $selectAttribute = in_array($value, $selected) ? ' selected="selected"' : '';
            $contentArray[] = '<option value="' . $value . '"' . $selectAttribute . '>' . $label . '</option>';
        }

        $contentArray[] = '</select>';
        $content = implode("\n", $contentArray);

        return $content;
    }
}
