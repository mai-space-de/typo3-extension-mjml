.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

Extension Configuration
=======================

The extension can be configured in the TYPO3 backend under
:guilabel:`Admin Tools > Settings > Extension Configuration > mjml`.

.. confval:: binaryPath
    :type: string
    :Default: (empty – auto-detected)

    Absolute path to the MJML binary.  Leave empty to let the extension
    auto-detect the binary in the following order:

    1. Environment variable ``MJML_BINARY``
    2. ``node_modules/.bin/mjml`` inside the extension directory
    3. Global ``mjml`` command on the system ``PATH``

    **Example:** ``/usr/local/bin/mjml``

.. confval:: enableMiddleware
    :type: boolean
    :Default: false

    Enable the built-in HTTP API middleware.
    When enabled, the endpoint ``POST /_mjml/convert`` is active on all
    TYPO3 frontend sites.

    .. warning::
        Only enable this middleware in trusted environments (e.g. during
        local development or on servers not publicly accessible), because
        there is no built-in authentication.

Environment Variables
=====================

.. envvar:: MJML_BINARY

    Override the path to the MJML binary without changing the TYPO3
    extension configuration.  This is useful in containerised environments
    (Docker, Kubernetes).

    .. code-block:: bash

        export MJML_BINARY=/usr/local/bin/mjml
