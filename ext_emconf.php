<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà! Plus - Classic Edition for 10.x',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3. Replacement for old TemplaVoilà!.',
    'category' => 'misc',
    'version' => '7.3.3w6',
    'state' => 'excludeFromUpdates',	// pre-beta
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoilaplus/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz, A. w010 Wolski',
    'author_email' => 'opitz@extrameile-gehen.de',
    'author_company' => 'Extrameile GmbH',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.3.99',
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [
            'templavoila' => '',
        ],
        'suggests' => [
            'typo3db_legacy' => '1.1.1-1.99.99',
        ],
    ],
];
