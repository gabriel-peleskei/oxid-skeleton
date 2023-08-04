<?php declare(strict_types=1);

namespace GP\Oxid\Skeleton\Command;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Component extends Base {
    protected $vendor = 'please/change';
    protected $path = './gp-skeleton-component';
    protected $description = '';
    protected $license = 'MIT';
    protected $version = '1.0.0';
    protected $autoload = 'That\\Should\\Be\\Changed';
    protected $target = 'src/';
    protected $authorName = '';
    protected $authorMail = '';


    protected function configure() {
        $this->setName('gp:skeleton:component');
        $this->setDescription('Create Component skeleton.');
        $this->addOption('vendor', 'o', InputOption::VALUE_REQUIRED, 'Add composer name: <vendor>/<name>', $this->vendor);
        $this->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to write this component into', $this->path);
        $this->addOption('description', 'd', InputOption::VALUE_OPTIONAL, 'Composer description', $this->description);
        $this->addOption('license', 'l', InputOption::VALUE_REQUIRED, 'Composer license', $this->license);
        $this->addOption('versioning', 'w', InputOption::VALUE_REQUIRED, 'Composer version', $this->version);
        $this->addOption('autoload', 'a', InputOption::VALUE_REQUIRED, 'Composer autoload psr-4 class (use double backslash)', $this->autoload);
        $this->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Autoload target directory', $this->target);
        $this->addOption('author-name', null, InputOption::VALUE_REQUIRED, 'Author\'s name', $this->authorName);
        $this->addOption('author-mail', null, InputOption::VALUE_REQUIRED, 'Author\'s email', $this->authorMail);
        $this->addOption('override', null, InputOption::VALUE_NONE, 'Overwrite contents `path` exists; in combination with [-n]');
    }

    protected function makeComposer(): array {
        $composer = [
            'name' => $this->vendor,
            'description' => $this->description,
            'type' => 'oxideshop-component',
            'keywords' => [
                'oxid',
                'eshop',
                'component'
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
        if ($author = array_filter([
            'name' => $this->authorName,
            'email' => $this->authorMail
        ])) {
            $composer['authors'] = [$author];
        }
        return $composer;
    }

    protected function makeReadme(): string {
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
            "```",
            "## License",
            "",
            $this->license,
            "",
            "",
            "## Copyright",
            "" 
        ];
        if ($this->authorMail || $this->authorName) {
            $line = $this->authorName . ' ';
            if ($this->authorMail) {
                $line .= "<$this->authorMail>";
            }
            $lines[] = trim($line);
        }
        return implode("\n", $lines);
    }

    protected function make(): int {
        try {
            $path = $this->getRealPath($this->path);

            if (realpath($path)) {
                if (!is_dir($path)) {
                    throw new InvalidOptionException("Path is not a directory [$path]", 400);
                }
                if (count(scandir($path)) > 2) {
                    $confirm = new ConfirmationQuestion("Path [$path] is not empty. Do you want to proceed? [y] This may override files: ", $this->input->getOption('override'));
                    if (!$this->ask($confirm)) {
                        throw new InvalidOptionException("Path [$path] is not empty!", 400);
                    } else {
                        $this->output->writeln("<comment>Overriding path</> [<fg=gray>$path</>] ...");
                    }
                }
            } else {
                mkdir($path, 0777, true);
                if (false === is_dir($path)) {
                    throw new InvalidOptionException("Given Path [$path] is not creatable!", 400);
                }
            }

            $targetPath = $this->joinPath($path, $this->target);
            if (realpath($targetPath)) {
                if (!is_dir($targetPath)) {
                    throw new InvalidOptionException("Given path to target [$targetPath] exists and is not a directory!", 409);
                }
            } else {
                mkdir($targetPath, 0777, true);
                if (false === is_dir($targetPath)) {
                    throw new InvalidOptionException("Given Path to target [$targetPath] is not creatable!", 400);
                }
            }
            
            $composer = $this->makeComposer();
            $composerFilePath = $this->joinPath($path, 'composer.json');
            if (false === file_put_contents($composerFilePath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                throw new InvalidOptionException("Failed to write composer to [$composerFilePath]", 409);
            }

            $readme = $this->joinPath($path, 'README.md');
            if (false === file_put_contents($readme, $this->makeReadme())) {
                $this->output->writeln("<comment>Failed to write Readme to [$readme]</>");
            }      
            
            $this->output->writeln('<info>... component created</>');
            return static::SUCCESS;
        } catch(\Throwable $e) {
            $this->output->writeln([sprintf("<error>%s:</> %s", get_class($e), $e->getMessage())]);
            Registry::getLogger()->error($e->getMessage(), [$e]);
            return static::FAILURE;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::execute($input, $output);

        $this->vendor = $this->question('vendor', 'Enter composer vendor like', '<vendor>/<name>');
        $this->path = $this->question('path', 'Enter path where to write the component files into like', '.');
        $this->description = $this->question('description', 'Enter description like', 'This component will safe lives.');
        $this->license = $this->question('license', 'Enter license name like', 'Apache');
        $this->version = $this->question('versioning', 'Enter desired version like', 'v6.6.6');
        $this->autoload = $this->question('autoload', 'Define Autoload namespace in psr-4 like', 'Company\\Oxid\\AwesomeComponent');
        $this->target = $this->question('target', 'Target of your autoload class like', 'source/');
        $this->authorName = $this->question('author-name', 'Your name like', 'Mike Smith');
        $this->authorMail = $this->question('author-mail', 'Your email like', 'awesome@person.com');
        $output->writeln(["<info>... processing data ...</>"]);
        $background = $this->input->getOption('no-interaction');
        $confirm = new ConfirmationQuestion("Do you want do proceed with the directory <comment>{$this->getRealPath($this->path)}</comment>? [y] ", $background);
        if (!$this->ask($confirm)) {
            $output->writeln('<comment>Skipping process</>');
            return static::INVALID;
        }
        return $this->make();
    }

}