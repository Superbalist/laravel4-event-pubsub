# Changelog

## 3.0.0 - ?

* Bump up to superbalist/php-event-pubsub ^4.0
* Add container binding for EventManager::class
* Change 'pubsub.events' binding to no longer be a singleton and to alias to EventManager::class
* Add new 'throw_validation_exceptions_on_dispatch' config option
* Add new 'translate_fail_handler' config option and default callable to dispatch a 'pubsub.events.translation_failure' event

## 2.0.2 - 2017-05-16

* Allow for php-event-pubsub ^3.0

## 2.0.1 - 2017-02-02

* Fix call to `collect` helper method not available in Laravel 4

## 2.0.0 - 2017-02-02

* Update `superbalist/php-event-pubsub` to ^2.0
* Change `pubsub.events` to resolve as a singleton
* Added support for "Attribute Injectors"
* Added new `attribute_injectors` config key

## 1.0.0 - 2017-01-30

* Initial release