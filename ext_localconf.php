<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	// register scheduler-task to update refindex of TYPO3
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Aoe\UpdateRefindex\Scheduler\UpdateRefIndexTask::class] = [
		'extension'        => 'update_refindex',
		'title'            => 'LLL:EXT:update_refindex/Resources/Private/Language/locallang.xlf:scheduler_task_updateRefindex.name',
		'description'      => 'LLL:EXT:update_refindex/Resources/Private/Language/locallang.xlf:scheduler_task_updateRefindex.description',
		'additionalFields' => \Aoe\UpdateRefindex\Scheduler\UpdateRefIndexAdditionalFields::class,
    ];
}
