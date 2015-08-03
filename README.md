# wp-ckan-backend

WordPress plugin to create manual datasets, organisations and groups in CKAN via API.

# Testing

1. Install composer if it isn't installed system wide:

    ```
   $ curl -sS https://getcomposer.org/installer | php
   ```

1. Run `php composer.phar install --dev` to install dependencies

1. add wordpress-standard to phpcs: `./bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs`

1. Run PHP CodeSniffer

    ```
    bin/phpcs -p --ignore=css,js,*/vendor/*,*/tests/* --report-width=100 --standard=code_standard.xml .
    ```
