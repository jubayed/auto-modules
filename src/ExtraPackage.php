<?php

namespace Jubayed\Composer;

use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\RootAliasPackage;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use UnexpectedValueException;


class ExtraPackage
{

    /**
     * @var Composer $composer
     */
    protected $composer;
    
    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var array $json
     */
    protected $json;

    /**
     * @var CompletePackage $package
     */
    protected $package;

    /**
     * @var VersionParser $versionParser
     */
    protected $versionParser;

    /**
     * @param string $path Path to composer.json file
     * @param Composer $composer
     */
    public function __construct(Composer $composer, $website, $module )
    {
        $this->composer = $composer;
        $this->path = $this->getModuleJson($website, $module);
        $this->json = $this->readPackageJson($this->path);
        $this->package = $this->loadPackage($this->json);
        $this->versionParser = new VersionParser();
    }

    public function getModuleJson($website, $module)
    {
        $baseDir = dirname(__DIR__, 4);

        $path1 = realpath("{$baseDir}/websites/{$website}/modules/{$module}/composer.json");
        $path2 = realpath("{$baseDir}/modules/{$module}/composer.json");

        if(is_file($path1)){
            return "websites/{$website}/modules/{$module}/composer.json";
        } else if(is_file($path2)){
            return "modules/{$module}/composer.json";
        }
        
        trigger_error("modules/{$module}/composer.json file not found", E_USER_ERROR);
    }

    /**
     * Read the contents of a composer.json style file into an array.
     *
     * The package contents are fixed up to be usable to create a Package
     * object by providing dummy "name" and "version" values if they have not
     * been provided in the file. This is consistent with the default root
     * package loading behavior of Composer.
     *
     * @param string $path
     * @return array
     */
    protected function readPackageJson($path)
    {
        $file = new JsonFile($path);
        $json = $file->read();
        if (!isset($json['name'])) {
            $json['name'] = 'merge-plugin/' .
                strtr($path, DIRECTORY_SEPARATOR, '-');
        }
        if (!isset($json['version'])) {
            $json['version'] = '1.0.0';
        }
        return $json;
    }

    /**
     * @param array $json
     * @return CompletePackage
     */
    protected function loadPackage(array $json)
    {
        $loader = new ArrayLoader();
        $package = $loader->load($json);
        // @codeCoverageIgnoreStart
        if (!$package instanceof CompletePackage) {
            throw new UnexpectedValueException(
                'Expected instance of CompletePackage, got ' .
                get_class($package)
            );
        }
        // @codeCoverageIgnoreEnd
        return $package;
    }

    /**
     * Merge this package into a RootPackageInterface
     *
     * @param RootPackageInterface $root
     */
    public function mergeInto(RootPackageInterface $root)
    {
      $this->mergeAutoload('autoload', $root);
    }

    /**
     * Merge autoload or autoload-dev into a RootPackageInterface
     *
     * @param string $type 'autoload' or 'devAutoload'
     * @param RootPackageInterface $root
     */
    protected function mergeAutoload($type, RootPackageInterface $root)
    {
        $getter = 'get' . ucfirst($type);
        $setter = 'set' . ucfirst($type);

        $autoload = $this->package->{$getter}();

        if (empty($autoload)) {
            return;
        }

        $unwrapped = self::unwrapIfNeeded($root, $setter);

        $unwrapped->{$setter}(array_merge_recursive(
            $root->{$getter}(),
            $this->fixRelativePaths($autoload)
        ));
    }

    /**
     * Fix a collection of paths that are relative to this package to be
     * relative to the base package.
     *
     * @param array $paths
     * @return array
     */
    protected function fixRelativePaths(array $paths)
    {
        $base = dirname($this->path);
        $base = ($base === '.') ? '' : "{$base}/";

        array_walk_recursive(
            $paths,
            function (&$path) use ($base) {
                $path = "{$base}{$path}";
            }
        );
        return $paths;
    }

    /**
     * Get a full featured Package from a RootPackageInterface.
     *
     * In Composer versions before 599ad77 the RootPackageInterface only
     * defines a sub-set of operations needed by composer-merge-plugin and
     * RootAliasPackage only implemented those methods defined by the
     * interface. Most of the unimplemented methods in RootAliasPackage can be
     * worked around because the getter methods that are implemented proxy to
     * the aliased package which we can modify by unwrapping. The exception
     * being modifying the 'conflicts', 'provides' and 'replaces' collections.
     * We have no way to actually modify those collections unfortunately in
     * older versions of Composer.
     *
     * @param RootPackageInterface $root
     * @param string $method Method needed
     * @return RootPackageInterface|RootPackage
     */
    public static function unwrapIfNeeded(
        RootPackageInterface $root,
        $method = 'setExtra'
    ) {
        // @codeCoverageIgnoreStart
        if ($root instanceof RootAliasPackage &&
            !method_exists($root, $method)
        ) {
            // Unwrap and return the aliased RootPackage.
            $root = $root->getAliasOf();
        }
        // @codeCoverageIgnoreEnd
        return $root;
    }

}
