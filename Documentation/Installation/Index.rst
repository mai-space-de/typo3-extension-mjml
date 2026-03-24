.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

Composer Installation
=====================

Install the extension via Composer:

.. code-block:: bash

    composer require maispace/mai-mjml

Install the MJML Binary
=======================

The extension requires the MJML npm package to be installed.
The recommended approach is to install it locally inside the extension
directory so the exact version shipped with the extension is used:

.. code-block:: bash

    # From the extension root (or from the project root with the correct path)
    cd vendor/maispace/mai-mjml && npm install

Alternatively you can install MJML globally:

.. code-block:: bash

    npm install -g mjml

Automate via Composer Scripts
==============================

To ensure the npm package is always installed after a ``composer install``
or ``composer update``, add the following to your project's
``composer.json``:

.. code-block:: json

    {
        "scripts": {
            "post-install-cmd": [
                "cd vendor/maispace/mai-mjml && npm install --omit=dev"
            ],
            "post-update-cmd": [
                "cd vendor/maispace/mai-mjml && npm install --omit=dev"
            ]
        }
    }

Activate the Extension
======================

The extension is automatically activated when installed via Composer.
If you use the Classic (non-Composer) mode, activate it in the TYPO3
Extension Manager.
