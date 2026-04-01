<?php

declare(strict_types=1);

namespace RectorUi\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServeCommand extends Command
{
    protected static $defaultName = 'serve';
    protected static $defaultDescription = 'Start the rector-ui development server';

    private const DEFAULT_HOST = 'localhost';
    private const DEFAULT_PORT = 8199;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'The host address to bind to',
                self::DEFAULT_HOST,
            )
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_REQUIRED,
                'The port to listen on',
                self::DEFAULT_PORT,
            )
            ->addOption(
                'no-open',
                null,
                InputOption::VALUE_NONE,
                'Do not open the browser automatically',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = $input->getOption('host');
        $port = (int) $input->getOption('port');
        $openBrowser = !$input->getOption('no-open');

        $routerScript = dirname(__DIR__, 2) . '/bin/router.php';

        if (!file_exists($routerScript)) {
            $io->error(sprintf('Router script not found: %s', $routerScript));

            return Command::FAILURE;
        }

        $io->title('rector-ui Development Server');
        $io->text(sprintf('Starting server at <info>http://%s:%d</info>', $host, $port));
        $io->text('Press Ctrl+C to stop.');
        $io->newLine();

        $command = sprintf(
            'php -S %s:%d %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($routerScript),
        );

        if ($openBrowser) {
            $url = sprintf('http://%s:%d', $host, $port);
            $this->openBrowser($url, $io);
        }

        passthru($command, $exitCode);

        return $exitCode;
    }

    private function openBrowser(string $url, SymfonyStyle $io): void
    {
        $candidates = [
            'xdg-open',
            'open',
            'start',
        ];

        foreach ($candidates as $candidate) {
            $which = shell_exec(sprintf('which %s 2>/dev/null', escapeshellarg($candidate)));

            if (!empty($which)) {
                exec(sprintf('%s %s > /dev/null 2>&1 &', $candidate, escapeshellarg($url)));
                $io->text(sprintf('Opening browser: <info>%s</info>', $url));

                return;
            }
        }
    }
}
