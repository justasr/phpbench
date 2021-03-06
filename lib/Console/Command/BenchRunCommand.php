<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Console\Command;

use PhpBench\BenchCaseCollectionResult;
use PhpBench\BenchFinder;
use PhpBench\BenchRunner;
use PhpBench\BenchSubjectBuilder;
use PhpBench\ProgressLogger\PhpUnitProgressLogger;
use PhpBench\ReportGenerator\ConsoleTableReportGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BenchRunCommand extends Command
{
    public function configure()
    {
        $this->setName('run');
        $this->setDescription('Run benchmarks');
        $this->setHelp(<<<EOT
Run benchmark files at given <comment>path</comment>

    $ %command.full_name% /path/to/bench

All bench marks under the given path will be executed recursively.
EOT
        );
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to benchmark(s)');
        $this->addOption('report', array(), InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Report name or configuration in JSON format');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Running benchmarking suite</info>');
        $output->writeln('');
        $reportConfigs = $this->normalizeReportConfig($input->getOption('report'));

        $path = $input->getArgument('path');
        $results = $this->executeBenchmarks($output, $path);
        $this->generateReports($output, $results, $reportConfigs);
    }

    private function generateReports(OutputInterface $output, BenchCaseCollectionResult $results, $reportConfigs)
    {
        $generators = array(
            'console_table' => new ConsoleTableReportGenerator($output),
        );

        foreach ($reportConfigs as $reportName => $reportConfig) {
            if (!isset($generators[$reportName])) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown report generator "%s", known generators: "%s"',
                    $reportName, implode('", "', array_keys($generators))
                ));
            }
        }

        foreach ($reportConfigs as $reportName => $reportConfig) {
            $options = new OptionsResolver();
            $report = $generators[$reportName];
            $report->configure($options);

            try {
                $reportConfig = $options->resolve($reportConfig);
            } catch (UndefinedOptionsException $e) {
                throw new \InvalidArgumentException(sprintf(
                    'Error generating report "%s"', $reportName
                ), null, $e);
            }

            $report->generate($results, $reportConfig);
        }
    }

    private function normalizeReportConfig($rawConfigs)
    {
        $configs = array();
        foreach ($rawConfigs as $rawConfig) {
            // If it doesn't look like a JSON string, assume it is the name of a report
            if (substr($rawConfig, 0, 1) !== '{') {
                $configs[$rawConfig] = array();
                continue;
            }

            $config = json_decode($rawConfig, true);

            if (null === $config) {
                throw new \InvalidArgumentException(sprintf(
                    'Could not decode JSON string: %s', $rawConfig
                ));
            }

            if (!isset($config['name'])) {
                throw new \InvalidArgumentException(sprintf(
                    'You must include the name of the report ("name") in the report configuration: %s',
                    $rawConfig
                ));
            }

            $name = $config['name'];
            unset($config['name']);

            $configs[$name] = $config;
        }

        return $configs;
    }

    private function executeBenchmarks(OutputInterface $output, $path)
    {
        $finder = new Finder();

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf(
                'File or directory "%s" does not exist',
                $path
            ));
        }

        if (is_dir($path)) {
            $finder->in($path);
            $finder->name('*Case.php');
        } else {
            $finder->in(dirname($path));
            $finder->name(basename($path));
        }

        $benchFinder = new BenchFinder($finder);
        $subjectBuilder = new BenchSubjectBuilder();
        $progressLogger = new PhpUnitProgressLogger($output);

        $benchRunner = new BenchRunner(
            $benchFinder,
            $subjectBuilder,
            $progressLogger
        );

        return $benchRunner->runAll();
    }
}
