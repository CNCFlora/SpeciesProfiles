# Profiles

CNCFlora app to edit and mantain ecological, population, threat, action, use and some other specie profile related data.

## Deployment

This app is intend to run as part of the [CNCFlora Nuvem](http://github.com/cncflora/nuvem) apps.

To run standalone, you will need [docker](http://docker.com) and [docker-compose](http://docs.docker.com/compose):

Clone the project, access it and run the containers:

    $ git clone git@github.com:CNCFlora/SpeciesProfiles.git
    $ cd SpeciesProfiles
    $ make install-deps
    $ make start

## Development

You will need [docker](http://docker.com) and [docker-compose](http://docs.docker.com/compose).

The whole project is supposed to run inside a docker container, isolated, including the tests, build and etc.

Start with git:

    $ git clone git@github.com:CNCFlora/SpeciesProfiles
    $ cd SpeciesProfiles

The tasks are defined in the Makefile.

At first run, you will need to download the vendor dependencies:

    $ make install-deps # run once to download dependencies

At subsquent, if you wish to update the dependencies:

    $ make update-deps 

To run the app in dev mode:

    $ make start # run in background
    $ make logs # follow logs
    $ make stop # stop all runing

This will take a while the first time, as it download the needed services (like couchdb, elasticsearch and etc).

Other relevant tasks:

    $ make test # run unit tests
    $ make test-features # run feature tests 
    $ make build # builds docker container
    $ make push # pushes the container

## License

Licensed under the Apache License 2.0.

