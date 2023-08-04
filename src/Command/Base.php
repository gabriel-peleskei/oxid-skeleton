<?php declare(strict_types=1);

namespace GP\Oxid\Skeleton\Command;
use GP\Oxid\Skeleton\Exception\IOException;
use OxidEsales\Eshop\Core\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class Base extends Command {
    /** @var int */
    protected $shopId;
    /** @var QuestionHelper */
    protected $helper;
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;

    public function __construct() {
        parent::__construct();
        $this->helper = new QuestionHelper();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        return static::SUCCESS;
    }

    protected function getShopId(): int {
        try {
            return $this->shopId ?? ($this->shopId = (int) $this->input->getOption('shop-id'));
        } catch(\Throwable $e) {
            return 1;
        }
    }

    protected function ask(Question $question) {
        return $this->helper->ask($this->input, $this->output, $question);
    }

    protected function question(string $option, string $question, string $example = '') {
        $default = $this->input->getOption($option);
        if ($example) {
            $example = "<info>$example</info> ";
        }
        $question = new Question("<comment>$question</comment> $example<fg=gray>(default: '$default')</>: ", $default);
        return $this->ask($question);
    }

    protected function getRealPath(string $path): string {
        $path = trim($path);
        
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

    protected function getTemplateDir(): string {
        return $this->getRealPath(__DIR__ . '/../../templates');
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

    protected function readTemplate(string ... $path): string {
        $template = $this->joinPath($this->getTemplateDir(), ... $path);
        $file = file_get_contents($template);
        if (false === $file) {
            $tpl = implode(DIRECTORY_SEPARATOR, $path);
            throw new IOException("Failed to read template [$tpl] from [$template]", 409);
        }
        return $file;
    }

    protected function writeTemplate(string $source, string $intoPath): void {
        if (false === file_put_contents($intoPath, $source)) {
            throw new IOException("Failed to write template to [$intoPath]", 409);
        }
    }


    /**
     * @return array<string, string>
     */
    protected function getLanguages(): \Generator {
        foreach(Registry::getLang()->getLanguageArray() as $lang) {
            yield $lang->abbr => $lang->name;
        }
    }

    protected function copyTemplate(string $to, string ... $from): void {
        $template = $this->joinPath($this->getTemplateDir(), ...$from);
        if (false === copy($template, $to)) {
            throw new IOException("Failed to copy template file [$template] to [$to]", 409);
        }
    }
}
