<?php declare(strict_types=1);

namespace GP\Oxid\Skeleton\Command;
use GP\Oxid\Skeleton\Exception\IOException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Module extends Base {

    protected string $template = 'twig';
    protected string $vendor = '<vendor>/<name>';
    protected string $title = 'Module skeleton for oxid 7';
    protected string $description = '';
    protected string $license = 'MIT';
    protected string $version = '1.0.0';
    protected string $autoload = 'That\\Should\\Be\\Changed';
    protected string $target = 'src/';
    protected string $absTarget;
    protected string $path = "./gp-skeleton-module";
    protected string $id = 'gp_module_skeleton';
    protected bool $readme = false;
    protected bool $changelog = false;

    protected function configure() {
        $this->setName('gp:skeleton:module');
        $this->setDescription("Create a module skeleton for metadata v2.1 for oxid 6.");
        $this->addOption('template', 'e', InputOption::VALUE_REQUIRED, "Template to use, one of [twig, smarty, both]", $this->template);
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
        $this->template = $this->question([$this, 'getOptionTemplate'], "Choose which template you want (twig, smarty, both)");
        $this->path = $this->getRealPath($this->question('path', "Enter installation path"));
        $this->vendor = $this->question('vendor', "Enter composer vendor");
        $this->autoload = $this->question('autoload', "Enter module namepacing");
        $this->absTarget = $this->joinPath($this->path, $this->target);
        $this->id = $this->question('id', "Enter module ID");
        $this->title = $this->question('title', "Enter metadata title");
        $this->description = $this->question('description', "Enter composer & metadata description");
        $this->license = $this->question('license', "Enter composer license");
        $this->version = $this->question('versioning', "Enter composer & metadata version string");
        $this->readme = $this->ask(new ConfirmationQuestion('<comment>Create README.md [y] </comment>', $this->input->getOption('readme') && $this->input->getOption('no-interaction')));
        $this->changelog = $this->ask(new ConfirmationQuestion('<comment>Create CHANGELOG.md [y] </comment>', $this->input->getOption('changelog') && $this->input->getOption('no-interaction')));
    }

    public function getOptionTemplate(?string $given = null): string {
        $template = strtolower(trim($given ?? $this->input->getOption('template')));
        if (!$template) {
            throw new InvalidOptionException("Option [template] must be given", 400);
        }
        if (!in_array($template, ['twig', 'smarty', 'both'], true)) {
            throw new InvalidOptionException("Option [template] must be one of [twig, smarty, both]", 400);
        }
        return $template;
    }

    public function isTwig(): bool {
        return in_array($this->template, ['twig', 'both'], true);
    }

    public function isSmarty(): bool {
        return in_array($this->template, ['smarty', 'both'], true);
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
            $this->joinPath($path, 'migration', 'data'),
            $this->joinPath($path, 'assets', 'js'),
            $this->joinPath($path, 'assets', 'css'),
            $this->joinPath($path, 'assets', 'img'),
        ];
        if ($this->isSmarty()) {
            $dirs[] = $this->joinPath($root, 'Smarty', 'Plugin');
            $dirs[] = $this->joinPath($root, 'views', 'admin_smarty', 'en');
            $dirs[] = $this->joinPath($root, 'views', 'admin_smarty', 'de');
            $dirs[] = $this->joinPath($path, 'views', 'smarty', 'blocks');
            $dirs[] = $this->joinPath($path, 'views', 'smarty', 'admin');
            $dirs[] = $this->joinPath($path, 'views', 'smarty', 'tpl');
        }
        if ($this->isTwig()) {
            $dirs[] = $this->joinPath($path, 'views', 'admin_twig', 'en');
            $dirs[] = $this->joinPath($path, 'views', 'admin_twig', 'de');
            $dirs[] = $this->joinPath($path, 'views', 'twig', 'admin');
        }
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
                    $this->autoload => $this->target
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
        if ($this->isTwig()) {
            $path = $this->joinPath($this->path, 'views', 'admin_twig');
            $this->setupAdminHelper('twig', $path);
        }
        if ($this->isSmarty()) {
            $path = $this->joinPath($this->path, 'views', 'admin_smarty');
            $this->setupAdminHelper('smarty', $path);
        }

    }

    protected function setupAdminHelper(string $for, string $baseDir): void {
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
            $this->output->writeln("<info>Saved admin language ($for)</info> <comment>$name</comment> <fg=gray>$path</>");
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
            [['logo.png'], $this->joinPath($this->path, 'assets', 'logo.png')]
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
        $this->output->writeln("<info>Saved</info> <fg=gray>CHANGELOG.md</>");
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
        $this->output->writeln("Using template <info>{$this->template}</>");
        return static::SUCCESS;
    }
}