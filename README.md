# EXT:mjml – MJML integration for TYPO3

[![CI](https://github.com/mai-space-de/typo3-extension-mjml/actions/workflows/ci.yml/badge.svg)](https://github.com/mai-space-de/typo3-extension-mjml/actions/workflows/ci.yml)

Integrate the [MJML](https://mjml.io) framework into TYPO3 v13 / v14 to
convert MJML markup into production-ready responsive HTML emails.

## Requirements

- PHP 8.2+
- TYPO3 13.4 LTS or 14.x
- Node.js 18+ and npm (for the MJML binary)

## Installation

```bash
composer require mai-space-de/mjml

# Install the MJML binary
cd vendor/mai-space-de/mjml && npm install --omit=dev
```

## Quick Start

```php
use MaiSpaceDe\Mjml\Exception\MjmlException;
use MaiSpaceDe\Mjml\Service\MjmlService;

final class MyMailer
{
    public function __construct(private readonly MjmlService $mjmlService) {}

    public function render(string $mjml): string
    {
        try {
            return $this->mjmlService->convert($mjml);
        } catch (MjmlException $e) {
            // handle error
        }
    }
}
```

## HTTP API (optional)

Enable the middleware in *Extension Manager → MJML → Enable Middleware*, then:

```bash
curl -X POST https://example.com/_mjml/convert \
     -H "Content-Type: text/plain" \
     --data '<mjml><mj-body>…</mj-body></mjml>'
```

## Documentation

Full documentation is available in the [`Documentation/`](Documentation/) directory.

## License

GPL-2.0-or-later – see [LICENSE](LICENSE)
