<?php
########################################################################
# Extension Manager/Repository config file for ext "update_refindex".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'update_refindex',
    'description' => 'extension contains scheduler-task to update refindex of TYPO3',
    'category' => 'be',
    'author' => 'AOE GmbH',
    'author_email' => 'dev@aoe.com',
    'author_company' => 'AOE GmbH',
    'state' => 'stable',
    'uploadfolder' => 0,
    'clearCacheOnLoad' => 1,
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    '_md5_values_when_last_written' => '',
];
