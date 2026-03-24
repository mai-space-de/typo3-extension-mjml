<?php

/**
 * TYPO3 Frontend Request Middlewares for EXT:mai_mjml.
 *
 * The MjmlMiddleware is registered in the frontend stack so that
 * POST /_mjml/convert is reachable on any TYPO3 frontend site.
 * It is disabled by default and must be explicitly enabled in the
 * extension configuration.
 */
return [
    'frontend' => [
        'maispace/mai-mjml/mjml-api' => [
            'target' => \Maispace\MaiMjml\Middleware\MjmlMiddleware::class,
            'before' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
