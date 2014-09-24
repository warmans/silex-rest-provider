Yet Another Silex Rest Provider
=======================================

[![Build Status](https://travis-ci.org/warmans/silex-rest-provider.svg)](https://travis-ci.org/warmans/silex-rest-provider)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/warmans/silex-rest-provider/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/warmans/silex-rest-provider/?branch=master)

Provider to simplify/standardize the generation of REST API resources in Silex based somewhat on mach/silex-rest.

### The Problem

With mach's rest provider you define your api as follows:


    $app['some.controller'] = function() {
        return new \Some\Controller();
    }

    $app['some.controller.2'] = function() {
        return new \Some\Controller();
    }

    $app['some.controller.3'] = function() {
        return new \Some\Controller();
    }

    $app['some.controller.4'] = function() {
        return new \Some\Controller();
    }

    $r1 = $app['rest']->resource('foo', 'some.controller'); // /foo
    $r2 = $r1->subresource('bar', 'some.controller.2');     // /foo/0/bar
    $r3 = $r2->subresource('baz', 'some.controller.3');     // /foo/0/bar/0/baz
    $r4 = $r2->subresource('baz-alt', 'some.controller.4'); // /foo/0/bar/0/baz-alt

This is fine if you have a small set of resources but as an API grows you end up with a bit of a mess.
Deleting resources can also cause problems as you have to find the factory, and untangle the dependent
resources for the resource being removed.

### The Solution

Generate the api based on a configuration array instead and allow inline controller factory definition.

The same api is defined as follows:

    $resources = [[
        'uri' => 'foo',
        'ctl' => function() { return new \Some\Controller(); },
        'sub' => [[
            'uri' => 'bar',
            'ctl' => function() { return new \Some\Controller(); },
            'sub' => [[
                'uri' => 'baz',
                'ctl' => function() { return new \Some\Controller(); },
            ],[
                'uri' => 'baz-alt',
                'ctl' => function() { return new \Some\Controller(); },
            ]]
        ]]
    ]];

    $app['rest']->importApi($resources);

Internally this will register all the controllers and then setup routes for the HTTP verbs.

### Installation

Install with composer than register the provider in your application

    $app->register(new \SilexProvier\Rest\Provider\RestServiceProvider($app));

### Resources

At minimum a resource must have the following elements:

    [
        'uri' => '/api/v1/foo',
        'ctl' => function() { return new \Some\Controller(); }
    ]

The `URI` defines the URI segment for the resource and the controller defines a factory which will
generate the controller used to handle requests to this segment.

The controller class must implement public methods for the HTTP verbs you want to implement.

| Request               | Invokes
| --------------------- | ---------
| GET /api/v1/foo       | cget()
| POST /api/v1/foo      | post()
| GET /api/v1/foo/1     | get($id)
| PUT /api/v1/foo/1     | put($id)
| PATCH /api/v1/foo/1   | patch($id)
| DELETE /api/v1/foo/1  | delete($id)

Note that both the root of the config and each subresources are ARRAYs of resources so even if you have
only a single root resource it must be wrapped in an array as follows:


    $myApi = [[
        'uri' => '/api/v1/foo',
        'ctl' => function() { return new \Some\Controller(); }
    ]];

### Sub Resources

Sub resources can be defines as follows:

    [
        'uri' => '/api/v1/foo',
        'ctl' => function() { return new \Some\Controller(); },
        'sub' => [[
            'uri' => 'bar',
            'ctl' => function() { return new \Some\Controller(); },
        ]]
    ]

For each additional sub resource in a hierarchy an additional id property is passed to the controller
action. These are named id, idd, iddd, idddd and so on for as many sub resources as exist.

With the above example a call to `GET /api/v1/foo/1/bar/2` will call the equivalent of `\Some\Controller::get(1, 2)`
so `\Some\Controller` should define its get method as `get($id, $idd)`.

Similarly a call to `GET /api/v1/foo/1/bar` will invoke `\Some\Controller::cget(1)`.


### Additional information on Controllers

If you would prefer not to defined factories inline you can just assign ctl to the name of an
existing service e.g.

    'ctl' => 'my.registered.controller'

If you want access to a controller that WAS defined inline its name will be prefixed with `rest.ctl`
then use the full hierarchy of resources in a dot-separated string. For example the config defined in
the sub resources section above would register:

1. rest.ctl.api.v1.foo
2. rest.ctl.api.v1.foo.bar

and so on.