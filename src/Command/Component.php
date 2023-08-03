<?php declare(strict_types=1);

namespace GP\Oxid\Skeleton\Command;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Component extends Command {
    protected InputInterface $input;
    protected OutputInterface $output;
    protected QuestionHelper $helper;

    protected string $vendor = 'please/change';
    protected string $path = './gp-skeleton-component';
    protected string $permission = "0644";
    protected string $description = '';
    protected string $license = 'MIT';
    protected string $version = '1.0.0';
    protected string $autoload = 'That\\Should\\Be\\Changed';
    protected string $target = 'src/';
    protected string $authorName = '';
    protected string $authorMail = '';

    public function __construct() {
        parent::__construct();
        $this->helper = new QuestionHelper();
    }

    protected function configure() {
        $this->setName('gp:skeleton:component');
        $this->setDescription('Create Component skeleton.');
        $this->addOption('vendor', 'o', InputOption::VALUE_OPTIONAL, 'Add composer name: <vendor>/<name>', $this->vendor);
        $this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Path to write this component into', $this->path);
        $this->addOption('permission', 'x', InputOption::VALUE_OPTIONAL, 'File/Foler permissions', $this->permission);
        $this->addOption('description', 'd', InputOption::VALUE_OPTIONAL, 'Composer description', $this->description);
        $this->addOption('license', 'l', InputOption::VALUE_OPTIONAL, 'Composer license', $this->license);
        $this->addOption('versioning', 'w', InputOption::VALUE_OPTIONAL, 'Composer version', $this->version);
        $this->addOption('autoload', 'a', InputOption::VALUE_OPTIONAL, 'Composer autoload psr-4 class (use double backslash)', $this->autoload);
        $this->addOption('target', 't', InputOption::VALUE_OPTIONAL, 'Autoload target directory', $this->target);
        $this->addOption('author-name', null, InputOption::VALUE_OPTIONAL, 'Author\'s name', $this->authorName);
        $this->addOption('author-mail', null, InputOption::VALUE_OPTIONAL, 'Author\'s email', $this->authorMail);
        $this->addOption('override', null, InputOption::VALUE_NONE, 'Overwrite contents `path` exists; in combination with [-n]');
    }

    protected function ask(Question $question): mixed {
        return $this->helper->ask($this->input, $this->output, $question);
    }

    protected function question(string $option, string $question, string $example = ''): mixed {
        $default = $this->input->getOption($option);
        if ($example) {
            $example = "<info>$example</info> ";
        }
        $question = new Question("<comment>$question</comment>: $example<fg=gray>(default: '$default')</>: ", $default);
        return $this->ask($question);
    }

    protected function getRealPath(?string $path = null): string {
        $path = trim($path ?? $this->path);
        
        // $path = trim($this->path, " \n\r\t\v\x00".DIRECTORY_SEPARATOR);
        if (0 !== strpos($path, DIRECTORY_SEPARATOR)) {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }

        $paths = explode(DIRECTORY_SEPARATOR, $path);
        $next = [];
        foreach($paths as $item) {
            $item = trim($item);
            if ($item === '.' || $item === '') {
                continue;
            }
            if ($item === '..') {
                array_pop($next);
                continue;
            }
            $next[] = $item;
        }

        $path = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $next);
        
        return $path;
    }


    protected function makeComposer(): array {
        $composer = [
            'name' => $this->vendor,
            'description' => $this->description,
            'type' => 'oxideshop-component',
            'license' => $this->license,
            'version' => $this->version,
            'autoload' => [
                'psr-4' => [
                    $this->autoload => $this->target
                ]
            ],
            'require' => (object)[]
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

    protected function joinPath(string $base, string ... $section): string {
        $section = array_map('trim', $section);
        $section = array_filter($section);
        $extended = implode(DIRECTORY_SEPARATOR, $section);
        if (0 === strpos($extended, DIRECTORY_SEPARATOR)) {
            return $this->getRealPath($extended);
        }
        return $this->getRealPath(implode(DIRECTORY_SEPARATOR, array_merge([$base], $section)));
    }

    protected function make(): int {
        try {
            $path = $this->getRealPath();

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
            }

            mkdir($path, 0777, true);
            if (false === realpath($path)) {
                throw new InvalidOptionException("Given Path [$path] is not creatable!", 400);
            }

            $targetPath = $this->joinPath($path, $this->target);
            mkdir($targetPath, 0777, true);
            if (false === realpath($targetPath)) {
                throw new InvalidOptionException("Given Path to target [$targetPath] is not creatable!", 400);
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
            chmod($path, octdec($this->permission));
            chmod($targetPath, octdec($this->permission));
            $this->output->writeln('<info>... component created</>');
            return static::SUCCESS;
        } catch(\Throwable $e) {
            $this->output->writeln([sprintf("<error>%s:</> %s", get_class($e), $e->getMessage())]);
            Registry::getLogger()->error($e->getMessage(), [$e]);
            return static::FAILURE;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        $this->vendor = $this->question('vendor', 'Enter composer vendor like', '<vendor>/<name>');
        $this->path = $this->question('path', 'Enter path where to write the component files into like', '.');
        $this->permission = $this->question('permission', 'Enter file permissions like', '0600');
        $this->description = $this->question('description', 'Enter description like', 'This component will safe lives.');
        $this->license = $this->question('license', 'Enter license name like', 'Apache');
        $this->version = $this->question('versioning', 'Enter desired version like', 'v6.6.6');
        $this->autoload = $this->question('autoload', 'Define Autoload namespace in psr-4 like', 'Company\\Oxid\\AwesomeComponent');
        $this->target = $this->question('target', 'Target of your autoload class like', 'source/');
        $this->authorName = $this->question('author-name', 'Your name like', 'Mike Smith');
        $this->authorMail = $this->question('author-mail', 'Your email like', 'awesome@person.com');
        $output->writeln(["<info>... processing data ...</>"]);
        $background = $this->input->getOption('no-interaction');
        $confirm = new ConfirmationQuestion("Do you want do proceed with the directory <comment>{$this->getRealPath()}</comment>? ", $background);
        if ($this->ask($confirm)) {
            $output->writeln('<info>... creating component ...</>');
        } else {
            $output->writeln('<comment>Skipping process</>');
        }
        return $this->make();
    }

}