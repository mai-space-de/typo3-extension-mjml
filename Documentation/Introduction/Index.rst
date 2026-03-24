.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

What Does It Do?
================

EXT:mai_mjml integrates the `MJML <https://mjml.io>`_ markup language into TYPO3.
MJML is a framework that makes writing responsive HTML emails easy.
Its semantic syntax reduces the amount of hand-written HTML required and
its rich component library speeds up development while keeping your email
code clean.

This extension exposes the MJML binary (installed via npm) as a first-class
TYPO3 service so that any part of your TYPO3 project can convert MJML markup
to production-ready responsive HTML.

Key Features
============

-  **MjmlService** – an injectable PHP service that wraps the MJML binary.
   Accepts raw MJML markup and returns rendered HTML.

-  **HTTP API Middleware** – an optional frontend middleware that exposes a
   ``POST /_mjml/convert`` endpoint for on-the-fly conversion.  Disabled
   by default and must be explicitly enabled in the extension configuration.

-  **Auto-detection of the MJML binary** – the service resolves the binary
   path from the following sources in order:

   1. TYPO3 extension configuration (``binaryPath``).
   2. Environment variable ``MJML_BINARY``.
   3. Local ``node_modules/.bin/mjml`` inside the extension directory.
   4. The global ``mjml`` command on the system ``PATH``.

-  **Specific MJML version** – a ``package.json`` is shipped with the
   extension so the correct MJML version is installed automatically.

Supported TYPO3 Versions
========================

+------------------+-------------------+
| TYPO3 version    | Extension version |
+==================+===================+
| 13.4 (LTS)       | 1.x               |
+------------------+-------------------+
| 14.x             | 1.x               |
+------------------+-------------------+

Requirements
============

-  PHP 8.2 or higher
-  Node.js 18 or higher (for the MJML binary)
-  npm (to install the MJML binary)
