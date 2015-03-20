<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

define ( 'PATH_tx_update_refindex', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath ( $_EXTKEY ) );

if (TYPO3_MODE == 'BE') {
	// register scheduler-task to update refindex of TYPO3
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_UpdateRefindex_Scheduler_UpdateRefIndexTask'] = array (
		'extension'        => $_EXTKEY,
		'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xml:scheduler_task_updateRefindex.name',
		'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xml:scheduler_task_updateRefindex.description',
		'additionalFields' => 'tx_UpdateRefindex_Scheduler_UpdateRefIndexAdditionalFields',
	);
}