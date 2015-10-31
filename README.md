# wp-ckan-backend

WordPress plugin to create manual datasets, organizations and groups in CKAN via API.

## Development

1. Install composer if it isn't installed system wide:
    ```
   $ curl -sS https://getcomposer.org/installer | php
   ```

1. Run `php composer.phar install` to install dependencies

1. add wordpress-standard to phpcs: `./bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs`


To check the code style, run the build script:

```
$ ./build.sh
```

This script runs on GitLab CI as well for every pull request.
