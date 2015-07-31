#!/bin/bash

set -e

SCRIPT=`readlink -e $0`
DIR=`dirname $SCRIPT`

echo $DIR

# Installation
curl -s https://getcomposer.org/installer | php
php composer.phar install
$DIR/bin/phpcs --config-set installed_paths $DIR/vendor/wp-coding-standards/wpcs

# Check code style
$DIR/bin/phpcs -p --ignore=css,js,*/vendor/*,*/tests/* --report-width=100 --standard=$DIR/code_standard.xml $DIR

# Run tests
# $DIR/../bin/phpunit
