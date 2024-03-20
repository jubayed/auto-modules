<?php

declare(strict_types=1);

namespace Jubayed\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;

class Plugin implements PluginInterface, Capable
{
    /**
     * Priority that plugin uses to register callbacks.
     */
    private const CALLBACK_PRIORITY = 50000;

    /**
     * Version number of the internal composer-plugin-api package
     *
     * This is used to denote the API version of Plugin specific
     * features, but is also bumped to a new major if Composer
     * includes a major break in internal APIs which are susceptible
     * to be used by plugins.
     *
     * @var string
     */
    public const PLUGIN_API_VERSION = '1.0.0';

    /**
     * Apply plugin modifications to Composer
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $installer = $composer->getInstallationManager();
        // $installer->uninstall(['composer', 'dumpautoload']);
    }  

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }

    public function getCapabilities()
    {
        // Return the list of commands provided by this plugin
        return [
            'Composer\\Plugin\\Capability\\CommandProvider' => '\\Jubayed\\Composer\\CommandProvider',
        ];
    }

}
