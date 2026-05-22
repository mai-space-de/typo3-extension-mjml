<?php

declare(strict_types=1);

namespace Maispace\MaiMjml\Exception;

use RuntimeException;

/**
 * Thrown when the MJML renderer fails to convert a template to HTML.
 *
 * Common causes:
 *   - Binary path not configured in Extension Manager → mai_mjml
 *   - MJML binary not found or not executable at the configured path
 *   - Malformed MJML template causing a non-zero exit code
 */
final class MjmlRenderException extends RuntimeException {}
