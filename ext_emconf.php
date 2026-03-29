<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Mai Mjml',
    'description' => 'MJML integration for TYPO3. Converts MJML templates to responsive HTML emails by invoking the local MJML CLI binary. Used exclusively as a suggested dependency of `mai_mail` — feature extensions must not depend on this directly.',
    'category' => 'module',
    'author' => 'Maispace',
    'author_email' => '',
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
