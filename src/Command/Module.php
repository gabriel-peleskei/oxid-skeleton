<?php declare(strict_types=1);

namespace GP\Oxid\Skeleton\Command;
use GP\Oxid\Skeleton\Exception\IOException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Module extends Base {

    protected $vendor = '<vendor>/<name>';
    protected $title = 'Module skeleton for oxid 6';
    protected $description = '';
    protected $license = 'MIT';
    protected $version = '1.0.0';
    protected $autoload = 'That\\Should\\Be\\Changed';
    protected $target = 'src/';
    protected $absTarget;
    protected $path = "./gp-skeleton-module";
    protected $id = 'gp_module_skeleton';
    protected $readme = false;
    protected $changelog = false;

    protected function configure() {
        $this->setName('gp:skeleton:module');
        $this->setDescription("Create a module skeleton for metadata v2.1 for oxid 6.");
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, "Module installation path", $this->path);
        $this->addOption('vendor', 'o', InputOption::VALUE_REQUIRED, "Composer vendor/name", $this->vendor);
        $this->addOption('autoload', 'a', InputOption::VALUE_REQUIRED, "Composer psr-4 autoload namespace (use double backslash)", $this->autoload);
        $this->addOption('id', 'x', InputOption::VALUE_REQUIRED, "Module ID for composer", $this->id);
        $this->addOption('title', 't', InputOption::VALUE_REQUIRED, "Metadata title", $this->title);
        $this->addOption('description', 'd', InputOption::VALUE_OPTIONAL, "Composer & metadata description", $this->description);
        $this->addOption('license', 'l', InputOption::VALUE_REQUIRED, "Composer license", $this->license);
        $this->addOption('versioning', 'w', InputOption::VALUE_REQUIRED, "Metadata & composer version", $this->version);
        $this->addOption('readme', 'r', InputOption::VALUE_NONE, "Create README.md");
        $this->addOption('changelog', 'c', InputOption::VALUE_NONE, "Create README.md");
    }

    protected function initOptions(): void {
        $this->path = $this->getRealPath($this->question('path', "Enter installation path"));
        $this->vendor = $this->question('vendor', "Enter composer vendor");
        $this->autoload = $this->question('autoload', "Enter module namepacing");
        $this->absTarget = $this->joinPath($this->path, $this->target);
        $this->id = $this->question('id', "Enter module ID");
        $this->title = $this->question('title', "Enter metadata title");
        $this->description = $this->question('description', "Enter composer & metdata description");
        $this->license = $this->question('license', "Enter composer license");
        $this->version = $this->question('versioning', "Enter composer & metadata version string");
        $this->readme = $this->ask(new ConfirmationQuestion('<comment>Create README.md [y] </comment>', $this->input->getOption('readme') && $this->input->getOption('no-interaction')));
        $this->changelog = $this->ask(new ConfirmationQuestion('<comment>Create CHANGELOG.md [y] </comment>', $this->input->getOption('changelog') && $this->input->getOption('no-interaction')));
    }

    protected function replaceData(string $source): string {
        $source = preg_replace('/__ROOTNAMESPACE__/', $this->autoload, $source);
        $source = preg_replace('/__MODULENAMESPACE__/', implode('\\', [$this->autoload, 'Core\\Module']), $source);
        $source = preg_replace('/__ID__/', $this->id, $source);
        $source = preg_replace('/__VERSION__/', $this->version, $source);
        $source = preg_replace('/__TITLE__/', $this->title, $source);
        $source = preg_replace('/__DESCRIPTION__/', $this->description, $source);
        return $source;
    }

    protected function setupFolders(): void {
        $path = $this->path;
        $root = $this->absTarget;
        $errors = 0;
        $dirs = [
            $this->joinPath($root, 'Application', 'Controller', 'Admin'),
            $this->joinPath($root, 'Application', 'Component', 'Widget'),
            $this->joinPath($root, 'Application', 'Model'),
            $this->joinPath($root, 'Application', 'translations'),
            $this->joinPath($root, 'Core'),
            $this->joinPath($root, 'Service'),
            $this->joinPath($root, 'Smarty', 'Plugin'),
            $this->joinPath($path, 'views', 'blocks'),
            $this->joinPath($path, 'views', 'admin', 'en'),
            $this->joinPath($path, 'views', 'admin', 'de'),
            $this->joinPath($path, 'views', 'tpl', 'admin'),
            $this->joinPath($path, 'migration', 'data'),
            $this->joinPath($path, 'out', 'js'),
            $this->joinPath($path, 'out', 'css'),
            $this->joinPath($path, 'out', 'img'),
        ];
        $this->output->writeln("Setting up folders...");
        foreach ($dirs as $dir) {
            if (mkdir($dir, 0777, true)) {
                $this->output->writeln("<info>Added path</info> <fg=gray>$dir</>");
            } else {
                $this->output->writeln("<error>Failed to create path <comment>$dir</comment></error>");
                ++$errors;
            }
        }
        if ($errors) {
            throw new IOException("Failed to create folders, $errors in total", 409);
        }
    }

    public function setupComposer(): void {
        $composer = [
            'name' => $this->vendor,
            'description' => $this->description,
            'type' => 'oxideshop-module',
            'keywords' => [
                'oxid',
                'eshop',
                'module'
            ],
            'license' => $this->license,
            'version' => $this->version,
            'autoload' => [
                'psr-4' => [
                    $this->autoload . '\\' => $this->target
                ]
            ],
            'require' => new \stdClass(),
            'require-dev' => new \stdClass()
        ];
        $composerFile = $this->joinPath($this->path, 'composer.json');
        $this->writeTemplate(json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $composerFile);
        $this->output->writeln("<info>Saved</info> <fg=gray>composer.json</>");
    }

    protected function setupMetdata(): void {
        $metadata = $this->readTemplate('metadata.php');
        $metadata = $this->replaceData($metadata);
        $target = $this->joinPath($this->path, 'metadata.php');
        $this->writeTemplate($metadata, $target);
        $this->output->writeln("<info>Saved</info> <fg=gray>metadata.php</>");
    }

    protected function setupCoreModule(): void {
        $file = $this->readTemplate('Application.Core.Module.php');
        $file = $this->replaceData($file);
        $target = $this->joinPath($this->absTarget, 'Core', 'Module.php');
        $this->writeTemplate($file, $target);
        $this->output->writeln("<info>Saved</info> <fg=gray>Module.php</>");
    }

    protected function setupAdminTranslations(): void {
        $baseDir = $this->joinPath($this->path, 'views', 'admin');
        $langs = [
            'de' => 'Deutsch',
            'en' => 'Englisch'
        ];
        $template = $this->readTemplate('views.admin.lang.php');
        foreach($langs as $abbr => $name) {
            $dirname = $this->joinPath($baseDir, $abbr);
            try {
                mkdir($dirname, 0777, true);
            } catch(\Throwable $e) {}
            $path = $this->joinPath($dirname, "{$this->id}_admin_{$abbr}_lang.php");
            $file = preg_replace('/__LANGNAME__/', $name, $template);
            $this->writeTemplate($file, $path);
            $this->output->writeln("<info>Saved admin language</info> <comment>$name</comment> <fg=gray>$path</>");
        }
    }

    protected function setupTranslations(): void {
        $baseDir = $this->joinPath($this->absTarget, 'Application', 'translations');
        $template = $this->readTemplate('views.admin.lang.php');
        foreach($this->getLanguages() as $abbr => $name) {
            $dirname = $this->joinPath($baseDir, $abbr);
            try {
                mkdir($dirname, 0777, true);
            } catch(\Throwable $e) {}
            $path = $this->joinPath($dirname, "{$this->id}_{$abbr}_lang.php");
            $file = preg_replace('/__LANGNAME__/', $name, $template);
            $this->writeTemplate($file, $path);
            $this->output->writeln("<info>Saved language</info> <comment>$name</comment> <fg=gray>$path</>");
        }
    }

    protected function setupReadme(): void {
        if (!$this->readme) {
            return;
        }
        $lines = [
            "# " . $this->vendor,
            "",
            $this->description,
            "",
            "",
            "## Installation",
            "",
            "```bash",
            "composer require " . $this->vendor,
            "vendor/bin/oe-console oe:module:install-configuration " . $this->path,
            "vendor/bin/oe-console oe:module:activate" . $this->id,
            "```",
            "## License",
            "",
            $this->license,
            "",
            "",
            "## Copyright",
            "" 
        ];
        $contents = implode("\n", $lines);
        $target = $this->joinPath($this->path, 'README.md');
        $this->writeTemplate($contents, $target);
        $this->output->writeln("<info>Saved</info> <fg=gray>README.md</>");
    }

    protected function setupChangelog(): void {
        if (!$this->changelog) {
            return;
        }
        $lines = [
            "# CHANGELOG",
            "",
            "## [Unreleased]",
            "### Added",
            "### Changed",
            "### Fixed",
            "### Removed",
            "### Deprecated",
            "### Security",
            "",
            "## [{$this->version}] " . date('Y-m-d'),
            "### Added",
            "- Created skeleton"
        ];
        $contents = implode("\n", $lines);
        $target = $this->joinPath($this->path, 'CHANGELOG.md');
        $this->writeTemplate($contents, $target);
        $this->output->writeln("<info>Saved</info> <fg=gray>CHANGELOG.md</>");
    }

    protected function setupCopyFiles(): void {
        $files = [
            [['logo.png'], $this->joinPath($this->path, 'out', 'logo.png')]
        ];
        foreach ($files as [$from, $to]) {
            $this->copyTemplate($to, ...$from);
            $fromString = implode(DIRECTORY_SEPARATOR, $from);
            $this->output->writeln("<info>Copied</info> <fg=gray>$fromString</fg=gray> to <fg=gray>$to</fg=gray>");
        }
        
    }

    protected function setupMigration(): void {
        $lines = [
            "table_storage:",
            "\ttable_name: oxmigrations_" . $this->id,
            "migrations_paths:",
            sprintf("\t'%s': data", $this->autoload . "\\Migrations"),
        ];
        $contents = implode("\n", $lines);
        $target = $this->joinPath($this->path, 'migration', 'migrations.yml');
        $this->writeTemplate($contents, $target);
        $this->output->writeln("<info>Saved</info> <fg=gray>migrations.yml</>");
    }


    protected function confirmExecution(): ? int {
        $this->output->writeln('');
        $this->output->writeln("<comment>Root module directory is: <info>{$this->path}</info></comment>");
        $this->output->writeln("<comment>Autoload source directory is: <info>{$this->absTarget}</info></comment>");
        $this->output->writeln("");
        $question = new ConfirmationQuestion('<question>Do you want to process with given directories?</question> [y] ', $this->input->getOption('no-interaction'));
        if (!$this->ask($question)) {
            $this->output->writeln("<fg=blue>User aborted skeleton creation...</>");
            return static::SUCCESS;
        }
        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::execute($input, $output);
        $this->initOptions();
        $response = $this->confirmExecution();
        if (null !== $response) {
            return $response;
        }
        $this->setupFolders();
        $this->setupComposer();
        $this->setupMetdata();
        $this->setupCoreModule();
        $this->setupAdminTranslations();
        $this->setupTranslations();
        $this->setupMigration();
        $this->setupReadme();
        $this->setupChangelog();
        $this->setupCopyFiles();
        return static::SUCCESS;
    }
}