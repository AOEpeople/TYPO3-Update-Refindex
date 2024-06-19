<?php

defined('TYPO3') or die();

use Aoe\UpdateRefindex\Scheduler\UpdateRefIndexAdditionalFields;
use Aoe\UpdateRefindex\Scheduler\UpdateRefIndexTask;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][UpdateRefIndexTask::class] = [
    'extension' => 'update_refindex',
    'title' => 'LLL:EXT:update_refindex/Resources/Private/Language/locallang.xlf:scheduler_task_updateRefindex.name',
    'description' => 'LLL:EXT:update_refindex/Resources/Private/Language/locallang.xlf:scheduler_task_updateRefindex.description',
    'additionalFields' => UpdateRefIndexAdditionalFields::class,
];
