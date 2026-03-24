.. include:: /Includes.rst.txt

.. _changelog:

=========
Changelog
=========

Version 1.0.0
=============

**Release date:** 2024-03-24

Initial release.

-  Add ``MjmlService`` for converting MJML markup to responsive HTML
   using the MJML binary.
-  Add ``MjmlMiddleware`` providing an optional HTTP API endpoint at
   ``POST /_mjml/convert``.
-  Add extension configuration for custom binary path and middleware toggle.
-  Add GitHub Actions CI pipeline (lint, PHPStan, unit tests).
-  Add automated weekly checks for new stable MJML and TYPO3 versions.
-  Add full RST documentation.
-  Support TYPO3 13.4 (LTS) and 14.x.
-  Require PHP 8.2 or higher.
