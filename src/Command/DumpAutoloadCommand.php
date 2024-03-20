<?php

namespace Jubayed\Composer\Command;

use Composer\Package\RootPackageInterface;
use Jubayed\Composer\ExtraPackage;
use Jubayed\Composer\Logger;
use Jubayed\Composer\PluginState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Jubayed\Composer\Autoload\AutoloadGenerator;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\IO\IOInterface;

use function PHPUnit\Framework\throwException;

class DumpAutoloadCommand extends BaseCommand
{

    /**
     * @var Composer $composer
     */
    protected $composer;

    /**
     * @var PluginState $state
     */
    protected $state;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('saas:dump')
            ->setAliases(['saas:website-dump-autoload', 'saas:dump-autoload'])
            ->setDescription('Dumps the all of websites')
            ->setDefinition([
                new InputArgument('website', InputArgument::OPTIONAL, 'Your target website name'),
                new InputOption('optimize', 'o', InputOption::VALUE_NONE, 'Optimizes PSR0 and PSR4 packages to be loaded with classmaps too, good for production.'),
                new InputOption('classmap-authoritative', 'a', InputOption::VALUE_NONE, 'Autoload classes from the classmap only. Implicitly enables `--optimize`.'),
                new InputOption('apcu', null, InputOption::VALUE_NONE, 'Use APCu to cache found/not-found classes.'),
                new InputOption('apcu-prefix', null, InputOption::VALUE_REQUIRED, 'Use a custom prefix for the APCu autoloader cache. Implicitly enables --apcu'),
                new InputOption('dev', null, InputOption::VALUE_NONE, 'Enables autoload-dev rules. Composer will by default infer this automatically according to the last install or update --no-dev state.'),
                new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables autoload-dev rules. Composer will by default infer this automatically according to the last install or update --no-dev state.'),
                new InputOption('ignore-platform-req', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore a specific platform requirement (php & ext- packages).'),
                new InputOption('ignore-platform-reqs', null, InputOption::VALUE_NONE, 'Ignore all platform requirements (php & ext- packages).'),
                new InputOption('strict-psr', null, InputOption::VALUE_NONE, 'Return a failed status code (1) if PSR-4 or PSR-0 mapping errors are present. Requires --optimize to work.'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //
        $moduleNames = $this->getModules();

        foreach ($moduleNames as $module) {
            $this->generateAutoload($input, $output, $module);
            $this->createBootsrapCacheDirs($module);
        }

        $output->writeln("<info>All of module autoload file generated</info>");
        
        return Command::SUCCESS;
    }

    /**
     * Generate autoload class
     */
    public function generateAutoload($input, $output, $module)
    {
        $composer  = $this->requireComposer();

        $installationManager = $composer->getInstallationManager();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $config = $composer->getConfig();

        $optimize = $input->getOption('optimize') || $config->get('optimize-autoloader');
        $authoritative = $input->getOption('classmap-authoritative') || $config->get('classmap-authoritative');
        $apcuPrefix = $input->getOption('apcu-prefix');
        $apcu = $apcuPrefix !== null || $input->getOption('apcu') || $config->get('apcu-autoloader');

        if ($input->getOption('strict-psr') && !$optimize) {
            throw new \InvalidArgumentException('--strict-psr mode only works with optimized autoloader, use --optimize if you want a strict return value.');
        }

        if ($authoritative) {
            $this->getIO()->write("<comment>[{$module}]</comment> <info>Generating optimized autoload files (authoritative)</info>");
        } elseif ($optimize) {
            $this->getIO()->write("<comment>[{$module}]</comment> <info>Generating optimized autoload files</info>");
        } else {
            $this->getIO()->write("<comment>[{$module}]</comment> <info>Generating autoload files</info>");
        }

        $generator = new AutoloadGenerator($composer->getEventDispatcher(), $this->getIO());
        if ($input->getOption('no-dev')) {
            $generator->setDevMode(false);
        }
        if ($input->getOption('dev')) {
            if ($input->getOption('no-dev')) {
                throw new \InvalidArgumentException('You can not use both --no-dev and --dev as they conflict with each other.');
            }
            $generator->setDevMode(true);
        }
        $generator->setClassMapAuthoritative($authoritative);
        $generator->setRunScripts(true);
        $generator->setApcu($apcu, $apcuPrefix);
        $generator->setPlatformRequirementFilter($this->getPlatformRequirementFilter($input));

        $composer_dir = "jubayed/autoload/{$module}";
        $classMap = $generator->dump($config, $localRepo, $composer, $installationManager, $composer_dir, $optimize);
        $numberOfClasses = count($classMap);

        if ($authoritative) {
            $this->getIO()->write("<comment>[{$module}]</comment> <info>Generated optimized autoload files (authoritative) containing ". $numberOfClasses .' classes</info>');
        } elseif ($optimize) {
            $this->getIO()->write("<comment>[{$module}]</comment> <info>Generated optimized autoload files containing ". $numberOfClasses .' classes</info>');
        } else {
            $this->getIO()->write("<comment>[{$module}]</comment> <info>Generated autoload files</info>");
        }

        if ($input->getOption('strict-psr') && count($classMap->getPsrViolations()) > 0) {
            return 1;
        }
    }

    /**
     * Get all module name
     * 
     * @return array
     */
    private function getModules()
    {
        $path = dirname(__DIR__, 5). DIRECTORY_SEPARATOR ."modules.json";

        if (!file_exists($path)) {
            throw new \Exception("File does not exist at path: $path");
        }

        $modules = json_decode(file_get_contents($path), true);

        return array_keys($modules);
    }

    /**
     * Create boostrap cache dir
     * 
     * @param string $dir
     * 
     * @return void
     */
    private function createBootsrapCacheDirs($dir) {

        $cacheDir = dirname(__DIR__, 5). "/bootstrap/cache";
        if(!is_dir($cacheDir)){
            mkdir($cacheDir);
        }

        if(!is_dir($path = $cacheDir. "/{$dir}")){
            mkdir($path);
        }
    }

}
