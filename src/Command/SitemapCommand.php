<?php


namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class SitemapCommand
 * @package App\Command
 */
class SitemapCommand extends Command
{
    protected static $defaultName = 'app:sitemap';

    /**
     * @var string
     */
    private const DEFAULT_SCHEME = 'https';

    /**
     * @var string
     */
    private const DEFAULT_HOST = '127.0.0.1';

    /**
     * @var string
     */
    private const DEFAULT_PORT = '8000';

    private string $contentPath;

    /**
     * @return string
     */
    public function getContentPath(): string
    {
        return $this->contentPath;
    }

    /**
     * @param string $contentPath
     */
    public function setContentPath(string $contentPath): void
    {
        $this->contentPath = $contentPath;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Create the sitemap (text format for google).')
            ->addArgument('sitemap-path', InputArgument::REQUIRED,
                'The path to write the sitemap to.', null)
            ->addArgument('twig-path', InputArgument::OPTIONAL,
                'The path to scan for content struct.', null)
            ->addOption('url-scheme', null, InputOption::VALUE_REQUIRED,
                'The url scheme to use.', self::DEFAULT_SCHEME)
            ->addOption('url-host', null, InputOption::VALUE_REQUIRED,
                'The url host to use.', self::DEFAULT_HOST)
            ->addOption('url-port', null, InputOption::VALUE_REQUIRED,
                'The url port to use.', self::DEFAULT_PORT)
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A filter regex pattern to match against the sitemap urls.', null);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null !== $twigPath = $input->getArgument('twig-path')) {
            $this->setContentPath($twigPath);
        }

        $pages = iterator_to_array(
            Finder::create()
                ->files()
                ->depth('<= 2')
                ->name('/^[a-z0-9]+(?:-[a-z0-9]+)*.html.twig$/')
                ->path('content/')
                ->in(
                    $this->getContentPath()
                ),
            false
        );

        $sitemapContent = implode("\n", array_filter(array_map(function (SplFileInfo $fileinfo) use ($input): string {
            $urlScheme = $input->getOption('url-scheme');
            $urlHost = $input->getOption('url-host');
            $urlPort = $input->getOption('url-port');
            if (self::DEFAULT_PORT != $urlPort) {
                $urlPort = ':' . $urlPort;
            }
            $urlPath = str_replace('\\', '/', substr($fileinfo->getRelativePathname(),
                strlen('content'), -strlen('.html.twig')));
            return "{$urlScheme}://{$urlHost}{$urlPort}{$urlPath}";
        }, $pages), function (string $url) use ($input): bool {
            $filterArray = $input->getOption('filter');

            foreach ($filterArray as $filter) {
                if (1 === preg_match($filter, $url)) {
                    return true;
                }
            }

            return 0 === count($filterArray);
        }, 0)) . "\n";

        return (false !== file_put_contents($input->getArgument('sitemap-path'), $sitemapContent))
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
