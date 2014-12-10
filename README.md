# Profiles

CNCFlora app to edit and mantain ecological, population and some other specie related data.

## Deployment

Use docker:
  
    $ docker run -d -p 8282:80 -t cncflora/profiles

You will need to have access to etcd, connect and datahub.

## Development

Start with git:

    $ git clone git@github.com:CNCFlora/checklist
    $ cd checklist

Use [vagrant](http://vagrantup.com) and [virtualbox](http://virtualbox.org):

    $ vagrant up

Now the server is running at http://192.168.50.10

Run tests:

    $ vendor/bin/phpunit tests
    $ vendor/bin/behat

Build the container for deployment:

    $ docker build -t cncflora/checklist .
    $ docker push cncflora/checklist 

## License

Licensed under the Apache License 2.0.

