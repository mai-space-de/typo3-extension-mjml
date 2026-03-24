<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'MJML',
    'description' => 'MJML integration for TYPO3 – convert MJML templates to responsive HTML emails using the MJML binary',
    'category' => 'misc',
    'author' => 'Joel Maximilian Mai',
    'author_email' => '',
    'author_company' => 'mai.space',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
