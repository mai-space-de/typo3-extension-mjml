.. include:: /Includes.rst.txt

.. _usage:

=====
Usage
=====

Using MjmlService in PHP
=========================

Inject ``Maispace\MaiMjml\Service\MjmlService`` into any TYPO3
controller, service, or command:

.. code-block:: php

    use Maispace\MaiMjml\Exception\MjmlException;
    use Maispace\MaiMjml\Service\MjmlService;

    final class MyMailService
    {
        public function __construct(private readonly MjmlService $mjmlService) {}

        public function buildEmail(string $mjmlTemplate): string
        {
            try {
                return $this->mjmlService->convert($mjmlTemplate);
            } catch (MjmlException $e) {
                // Handle the error, e.g. log it and fall back to plain HTML
                throw $e;
            }
        }
    }

The ``convert()`` method accepts an optional array of MJML configuration
options (passed as ``--config.<key> <value>`` to the binary):

.. code-block:: php

    $html = $this->mjmlService->convert($mjmlTemplate, [
        'beautify' => 'true',
        'minify'   => 'false',
    ]);

Checking Binary Availability
=============================

.. code-block:: php

    if ($this->mjmlService->isAvailable()) {
        $html = $this->mjmlService->convert($mjml);
    }

    $version = $this->mjmlService->getVersion(); // e.g. "4.15.3"

Using the HTTP API Middleware
==============================

First enable the middleware in the extension configuration
(see :ref:`configuration`).

Then send a ``POST`` request to ``/_mjml/convert`` with the MJML markup
as the request body:

.. code-block:: bash

    curl -X POST https://example.com/_mjml/convert \
         -H "Content-Type: text/plain" \
         --data '<mjml><mj-body><mj-section><mj-column><mj-text>Hello!</mj-text></mj-column></mj-section></mj-body></mjml>'

Successful response (HTTP 200):

.. code-block:: json

    {
        "html": "<!doctype html><html>…</html>"
    }

Error response (HTTP 422):

.. code-block:: json

    {
        "error": "MJML conversion failed: …"
    }

Writing MJML Templates
======================

Refer to the official `MJML documentation <https://mjml.io/documentation/>`_
for a full reference of available components and attributes.

A minimal MJML template looks like this:

.. code-block:: xml

    <mjml>
      <mj-body>
        <mj-section>
          <mj-column>
            <mj-text font-size="20px" color="#333333">
              Hello from TYPO3!
            </mj-text>
          </mj-column>
        </mj-section>
      </mj-body>
    </mjml>

Future Enhancements
===================

The following features are planned for future releases:

-  **ViewHelper** – a TYPO3 Fluid ViewHelper so that MJML can be rendered
   directly inside Fluid templates.
-  **TypoScript content object** – a custom content object for rendering
   MJML from TypoScript.
-  **EXT:form integration** – use MJML templates for form confirmation
   and notification emails (similar to the ``saccas/mjml-typo3`` approach).
-  **Template caching** – cache the rendered HTML output to avoid calling
   the MJML binary on every request.
-  **MJML API server mode** – start a long-running Node.js server and
   communicate via HTTP/socket to avoid the process-spawn overhead.
