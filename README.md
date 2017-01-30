# laravel4-event-pubsub

An event protocol and implementation over pub/sub for Laravel 4.

[![Author](http://img.shields.io/badge/author-@superbalist-blue.svg?style=flat-square)](https://twitter.com/superbalist)
[![Build Status](https://img.shields.io/travis/Superbalist/laravel4-event-pubsub/master.svg?style=flat-square)](https://travis-ci.org/Superbalist/laravel4-event-pubsub)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/superbalist/laravel4-event-pubsub.svg?style=flat-square)](https://packagist.org/packages/superbalist/laravel4-event-pubsub)
[![Total Downloads](https://img.shields.io/packagist/dt/superbalist/laravel4-event-pubsub.svg?style=flat-square)](https://packagist.org/packages/superbalist/laravel4-event-pubsub)

This package is a wrapper bridging [php-event-pubsub](https://github.com/Superbalist/php-event-pubsub) into Laravel.
It builds on top of the existing [laravel4-pubsub](https://github.com/Superbalist/laravel4-pubsub) package adding support
for publishing and subscribing to events over pub/sub.

If you aren't familiar with the `laravel4-pubsub` package, it's worth first taking a look at their [documentation](https://github.com/Superbalist/laravel4-pubsub).

## Installation

```bash
composer require superbalist/laravel4-event-pubsub
```

The package has a default configuration built-in.

To customize the configuration file, publish the package configuration using Artisan.
```bash
php artisan config:publish superbalist/laravel4-event-pubsub
```

You can then edit the generated config at `app/config/packages/superbalist/laravel4-event-pubsub/config.php`.

Register the service provider in app.php
```php
'providers' => [
    // ...
    'Superbalist\Laravel4EventPubSub\PubSubEventsServiceProvider',
]
```

Register the facade in app.php
```php
'aliases' => [
    // ...
    'PubSubEvents' => 'Superbalist\Laravel4EventPubSub\PubSubEventsFacade',
]
```

## Usage

### Simple Events

A `SimpleEvent` is an event which takes a name and optional attributes.

```php
// the pubsub_events.translator config setting should be set to 'pubsub.events.translators.simple'

// get the event manager
$manager = app('pubsub.events');

// publish an event
$event = new \Superbalist\EventPubSub\Events\SimpleEvent(
    'user.created',
    [
        'user' => [
            'id' => 1456,
            'first_name' => 'Joe',
            'last_name' => 'Soap',
            'email' => 'joe.soap@example.org',
        ],
    ]
);
$manager->dispatch('events', $event);

// listen for an event
$manager->listen('events', 'user.created', function (\Superbalist\EventPubSub\EventInterface $event) {
    var_dump($event->getName());
    var_dump($event->getAttribute('user'));
});

// listen for all events on the channel
$manager->listen('events', '*', function (\Superbalist\EventPubSub\EventInterface $event) {
    var_dump($event->getName());
});

// all the aboce commands can also be done using the facade
PubSubEvents::dispatch('events', $event);
```

### Topic Events

A `TopicEvent` is an event which takes a topic, name, version and optional attributes.

```php
// the pubsub_events.translator config setting should be set to 'pubsub.events.translators.topic'

// get the event manager
$manager = app('pubsub.events');

// publish an event
$event = new \Superbalist\EventPubSub\Events\TopicEvent(
    'user',
    'created',
    '1.0',
    [
        'user' => [
            'id' => 1456,
            'first_name' => 'Joe',
            'last_name' => 'Soap',
            'email' => 'joe.soap@example.org',
        ],
    ]
);
$manager->dispatch('events', $event);

// listen for an event on a topic
$manager->listen('events', 'user/created', function (\Superbalist\EventPubSub\EventInterface $event) {
    // ...
});

// listen for an event on a topic matching the given version
$manager->listen('events', 'user/created/1.0', function (\Superbalist\EventPubSub\EventInterface $event) {
    // ...
});

// listen for all events on a topic
$manager->listen('events', 'user/*', function (\Superbalist\EventPubSub\EventInterface $event) {
    // ...
});

// listen for all events on the channel
$manager->listen('events', '*', function (\Superbalist\EventPubSub\EventInterface $event) {
    // ...
});
```

### Schema Events

A `SchemaEvent` is an extension of the `TopicEvent` and takes a schema and optional attributes.  The topic, name and
version are derived from the schema.

The schema must be in the format `(protocol)://(......)?/events/(topic)/(channel)/(version).json`

```php
// the pubsub_events.translator config setting should be set to 'pubsub.events.translators.schema'
// the pubsub_events.validator config setting can be set to 'pubsub.events.validators.json_schema' to take advantage of
// JSON Schema validation on incoming events

// get the event manager
$manager = app('pubsub.events');

// publish an event
$event = new \Superbalist\EventPubSub\Events\SchemaEvent(
    'http://schemas.my-website.org/events/user/created/1.0.json',
    [
        'user' => [
            'id' => 1456,
            'first_name' => 'Joe',
            'last_name' => 'Soap',
            'email' => 'joe.soap@example.org',
        ],
    ]
);
$manager->dispatch('events', $event);

// the listen expressions are the same as those used for TopicEvents.
```