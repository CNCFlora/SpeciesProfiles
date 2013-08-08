# SpeciesProfiles

CNCFlora app to edit and mantain ecological, population and some other specie related data.

## Deployment

Deploy the app codebase to any Apache or Nginx with PHP5.3+ and mod\_rewrite.
 
### Setting up CouchDB

CouchDB is the choosen storage backend, to configure it please refer to the [Datahub](http://github.com/cncflora/datahub).

### Setting up authentication with SSO

This app depends on [Connect app](http://github.com/cncflora/connect), so you will need a functional version of it.

### Setting up the Bibliographic app

Please refer to [Biblio](http://github.com/cncflora/biblio) for references.

### Setting up the application

With both the Datahub and Connect working, copy resources/config.ini-dist to resources/config.ini and fill in accordingly. Now you have a functional system.

## Development

To run local dev mode you can use PHPs built-in webserver:

  php -S localhost:8000 src/router.php

### Fetching dependencies

This app uses [composer](http://getcomposer.org) to manage the application and it's dependencies, so you first must [install composer](http://getcomposer.org/doc/00-intro.md#installation-nix). After installed you can make fetch dependencies for the first time:

    php composer.phar install

### Application organization

Static assets stay in resources, notes on _resources/templates_ for the [mustache](https://github.com/bobthecow/mustache.php) templates, _resouces/locales_ for the translations.

The _src_ dir stands for the source code of the application, with routes.php being the handler for the built-in dev server, index.php being the initial endpoint including the routes using [restserver](http://github.com/diogok/restserver) and repository to interact with the Datahub COuchDB using ???. And the _tests_ directory for the whole test suite, using [phpunit](http://phpunit.de/).

### Running tests

Before commiting remember to make tests pass:

    vendor/bin/phpunit tests

## License

Licensed under the Apache License 2.0.

