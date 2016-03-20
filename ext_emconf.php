<?php
########################################################################
# Extension Manager/Repository config file for ext "update_refindex".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'update_refindex',
    'description' => 'extension contains scheduler-task to update refindex of TYPO3',
    'category' => '',
    'author' => '',
    'author_email' => 'dev@aoe.com',
    'author_company' => 'AOE GmbH',
    'shy' => '',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'module' => '',
    'state' => 'alpha',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'version' => '0.2.3',
    'constraints' => array(
        'depends' => array(
            'php' => '5.2.0',
            'typo3' => '6.2.0-7.6.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
    '_md5_values_when_last_written' => '',
);
