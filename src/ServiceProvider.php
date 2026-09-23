<?php

namespace jeremykenedy\Slack\Laravel;

use Laravel\Lumen\Application;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The actual provider.
     *
     * @var ServiceProviderLaravel4|ServiceProviderLaravel5
     */
    protected $provider;

    /**
     * Instantiate the service provider.
     *
     * @param  mixed  $app
     * @return void
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->provider = $this->getProvider($app);
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->provider->boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->provider->register();
    }

    /**
     * Return the service provider for the particular Laravel version.
     *
     * @return ServiceProviderLaravel4|ServiceProviderLaravel5
     */
    private function getProvider($app)
    {
        if ($app instanceof Application) {
            return new ServiceProviderLaravel5($app);
        }

        if (intval($app::VERSION) === 4) {
            return new ServiceProviderLaravel4($app);
        }

        return new ServiceProviderLaravel5($app);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['jeremykenedy.slack'];
    }
}
