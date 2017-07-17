<?php

namespace Superbalist\Laravel4EventPubSub;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use League\JsonGuard\Dereferencer;
use League\JsonGuard\Loader;
use League\JsonGuard\Loaders\ArrayLoader;
use Superbalist\EventPubSub\EventManager;
use Superbalist\EventPubSub\EventValidatorInterface;
use Superbalist\EventPubSub\MessageTranslatorInterface;
use Superbalist\EventPubSub\Translators\SchemaEventMessageTranslator;
use Superbalist\EventPubSub\Translators\SimpleEventMessageTranslator;
use Superbalist\EventPubSub\Translators\TopicEventMessageTranslator;
use Superbalist\EventPubSub\Validators\JSONSchemaEventValidator;
use Superbalist\Laravel4PubSub\PubSubManager;
use Superbalist\Laravel4PubSub\PubSubServiceProvider;
use Superbalist\PubSub\PubSubAdapterInterface;

class PubSubEventsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $dir = realpath(__DIR__);
        $this->package('superbalist/laravel4-event-pubsub', 'laravel4-event-pubsub', $dir);
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->app->register(PubSubServiceProvider::class);

        $this->app->bind('pubsub.events.connection', function ($app) {
            // we'll use the connection name configured in the 'default' config setting from the 'pubsub_events'
            // config
            // if this value isn't set, we'll default to that from the 'pubsub' package config
            $config = $this->getConfig();
            $manager = $app['pubsub']; /* @var PubSubManager $manager */
            return $manager->connection($config['default']);
        });

        $this->app->bind('pubsub.events.translator', MessageTranslatorInterface::class);

        $this->app->bind(MessageTranslatorInterface::class, function ($app) {
            $config = $this->getConfig();
            $binding = $config['translator'];
            return $app[$binding];
        });

        $this->app->bind('pubsub.events.validator', EventValidatorInterface::class);

        $this->app->bind(EventValidatorInterface::class, function ($app) {
            $config = $this->getConfig();
            $binding = $config['validator'];
            // a validator is optional
            // if nothing is set, we don't try resolve it
            return $binding === null ? null : $app[$binding];
        });

        $this->registerTranslators();
        $this->registerValidators();

        $this->app->bind('pubsub.events', EventManager::class);

        $this->app->bind(EventManager::class, function ($app) {
            $adapter = $app['pubsub.events.connection']; /** @var PubSubAdapterInterface $connection */
            $translator = $app['pubsub.events.translator']; /** @var MessageTranslatorInterface $translator */
            $validator = $app['pubsub.events.validator']; /** @var EventValidatorInterface $validator */
            $injectors = [];
            $config = $this->getConfig();
            foreach ($config['attribute_injectors'] as $binding) {
                if (is_callable($binding)) {
                    $injectors[] = $binding;
                } else {
                    // resolve binding from container
                    $injectors[] = $app[$binding];
                }
            }

            $manager = new EventManager(
                $adapter,
                $translator,
                $validator,
                $injectors,
                $config['translate_fail_handler'],
                $config['listen_expr_fail_handler'],
                $config['validation_fail_handler']
            );
            $manager->throwValidationExceptionsOnDispatch($config['throw_validation_exceptions_on_dispatch']);
        });
    }

    /**
     * Register translators in the container.
     */
    protected function registerTranslators()
    {
        $this->app->bind('pubsub.events.translators.simple', function () {
            return new SimpleEventMessageTranslator();
        });

        $this->app->bind('pubsub.events.translators.topic', function () {
            return new TopicEventMessageTranslator();
        });

        $this->app->bind('pubsub.events.translators.schema', function () {
            return new SchemaEventMessageTranslator();
        });
    }

    /**
     * Register validators in the container.
     */
    protected function registerValidators()
    {
        $this->app->singleton('pubsub.events.validators.json_schema.loaders.array.schemas', function ($app) {
            $config = $this->getConfig();
            $schemas = $config['validators']['json_schema']['loaders']['array']['schemas'];
            return new Collection($schemas);
        });

        $this->app->bind('pubsub.events.validators.json_schema.loaders.array', function ($app) {
            $schemas = $app['pubsub.events.validators.json_schema.loaders.array.schemas']; /* @var Collection $schemas */
            return new ArrayLoader($schemas->all());
        });

        $this->app->bind('pubsub.events.validators.json_schema', function ($app) {
            $dereferencer = $app['pubsub.events.validators.json_schema.dereferencer']; /* @var Dereferencer $dereferencer */
            return new JSONSchemaEventValidator($dereferencer);
        });

        $this->app->bind('pubsub.events.validators.json_schema.dereferencer', function ($app) {
            $dereferencer = new Dereferencer();

            $config = $this->getConfig();

            foreach ($config['validators']['json_schema']['loaders'] as $name => $params) {
                $name = array_get($params, 'binding', $name);
                $binding = sprintf('pubsub.events.validators.json_schema.loaders.%s', $name);

                $prefix = array_get($params, 'prefix', $name);

                $loader = $app[$binding]; /* @var Loader $loader */

                $dereferencer->registerLoader($loader, $prefix);
            }

            return $dereferencer;
        });
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        $config = $this->app->make('config'); /* @var \Illuminate\Config\Repository $config */
        return $config->get('laravel4-event-pubsub::config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'pubsub.events',
            'pubsub.events.connection',
            'pubsub.events.translator',
            'pubsub.events.translators.simple',
            'pubsub.events.translators.topic',
            'pubsub.events.translators.schema',
            'pubsub.events.validator',
            'pubsub.events.validators.json_schema',
            'pubsub.events.validators.json_schema.dereferencer',
            'pubsub.events.validators.json_schema.loaders.array',
            'pubsub.events.validators.json_schema.loaders.array.schemas',
        ];
    }
}
