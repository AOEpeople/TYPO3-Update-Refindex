<?php

declare(strict_types=1);

namespace Aoe\UpdateRefindex\Scheduler;

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

use InvalidArgumentException;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * class to define additional fields
 */
class UpdateRefIndexAdditionalFields implements AdditionalFieldProviderInterface
{
    /**
     * Field name constants
     * @var string
     */
    public const FIELD_ALL_TABLES = 'updateRefindexAllTables';

    /**
     * Field name constants
     * @var string
     */
    public const FIELD_SELECTED_TABLES = 'updateRefindexSelectedTables';

    /**
     * Locallang reference
     * @var string
     */
    public const LL_REFERENCE = 'LLL:EXT:update_refindex/Resources/Private/Language/locallang.xlf';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array                     $taskInfo        Values of the fields from the add/edit task form
     * @param AbstractTask             $task            The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     *
     * @return array A two dimensional array: array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(
        array &$taskInfo,
        $task,
        SchedulerModuleController $schedulerModule
    ): array {
        if ($task !== null && !$task instanceof UpdateRefIndexTask) {
            throw new InvalidArgumentException('Task not of type UpdateRefIndexTask', 1622561874);
        }

        // define value for fields
        if ($schedulerModule->getCurrentAction()->equals(Action::ADD)) {
            $taskInfo[self::FIELD_ALL_TABLES] = false;
            $taskInfo[self::FIELD_SELECTED_TABLES] = [];
        } elseif ($schedulerModule->getCurrentAction()->equals(Action::EDIT)) {
            $taskInfo[self::FIELD_ALL_TABLES] = $task->isUpdateAllTables();
            $taskInfo[self::FIELD_SELECTED_TABLES] = $task->getSelectedTables();
        } else {
            $taskInfo[self::FIELD_ALL_TABLES] = false;
            $taskInfo[self::FIELD_SELECTED_TABLES] = [];
        }

        // Get configuration (markup & labels) for additional fields
        $additionalFields = [
            self::FIELD_ALL_TABLES => [
                'code' => $this->getCheckbox($taskInfo[self::FIELD_ALL_TABLES]),
                'label' => $GLOBALS['LANG']->sL(self::LL_REFERENCE . ':scheduler_task.updateRefindex.fieldUpdateAllTables.label'),
            ],
            self::FIELD_SELECTED_TABLES => [
                'code' => $this->getSelectBox($taskInfo[self::FIELD_SELECTED_TABLES]),
                'label' => $GLOBALS['LANG']->sL(self::LL_REFERENCE . ':scheduler_task.updateRefindex.fieldSelectedTables.label'),
            ],
        ];

        return $additionalFields;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array        $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task          Reference to the scheduler backend module
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        if (!$task instanceof UpdateRefIndexTask) {
            throw new InvalidArgumentException('Task not of type UpdateRefIndexTask', 1622562115);
        }

        /** @var UpdateRefIndexTask $task */
        $task->setUpdateAllTables((bool) $submittedData[self::FIELD_ALL_TABLES]);
        $task->setSelectedTables((array) $submittedData[self::FIELD_SELECTED_TABLES]);
    }

    /**
     * Validates the additional fields' values
     *
     * @param array                     $submittedData   An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     *
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(
        array &$submittedData,
        SchedulerModuleController $schedulerModule
    ): bool {
        if (!isset($submittedData[self::FIELD_ALL_TABLES])
            || !MathUtility::isIntegerInRange((int) $submittedData[self::FIELD_ALL_TABLES], 0, 1)
        ) {
            return false;
        }

        return !(isset($submittedData[self::FIELD_SELECTED_TABLES])
            && count($submittedData[self::FIELD_SELECTED_TABLES]) === 0);
    }

    /**
     * Gets the HTML markup of a checkbox input field
     *
     * @param boolean $isChecked
     */
    private function getCheckbox($isChecked): string
    {
        $checked = $isChecked ? 'checked="checked" ' : '';
        $content = '<input type="hidden" name="tx_scheduler[' . self::FIELD_ALL_TABLES . ']" value="0" />';
        return $content . '<input type="checkbox" ' . $checked . 'value="1"'
            . ' name="tx_scheduler[' . self::FIELD_ALL_TABLES . ']"'
            . ' id="task_' . self::FIELD_ALL_TABLES . '" />';
    }

    /**
     * Gets array with tables, which can be selected as options
     */
    private function getOptionsForSelectBox(): array
    {
        $existingTables = array_keys($GLOBALS['TCA']);
        sort($existingTables);

        $optionsSelectedTables = [];
        foreach ($existingTables as $existingTable) {
            $optionsSelectedTables[$existingTable] = $existingTable;
        }

        return $optionsSelectedTables;
    }

    /**
     * Generates HTML selectbox for field 'selectedTables'
     */
    private function getSelectBox(array $selected): string
    {
        $contentArray = [
            '<select id="task_' . self::FIELD_SELECTED_TABLES . '" name="tx_scheduler[' . self::FIELD_SELECTED_TABLES . '][]" size="20" multiple="multiple" class="form-control">',
        ];

        foreach ($this->getOptionsForSelectBox() as $value => $label) {
            $selectAttribute = in_array($value, $selected, true) ? ' selected="selected"' : '';
            $contentArray[] = '<option value="' . $value . '"' . $selectAttribute . '>' . $label . '</option>';
        }

        $contentArray[] = '</select>';
        return implode("\n", $contentArray);
    }
}
