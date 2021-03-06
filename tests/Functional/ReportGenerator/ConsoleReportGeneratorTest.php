<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench\Functional\ReportGenerator;

use PhpBench\ReportGenerator\ConsoleTableReportGenerator;
use Symfony\Component\Console\Output\NullOutput;

class ConsoleReportGeneratorTest extends BaseReportGeneratorCase
{
    public function getReport()
    {
        $output = new NullOutput();

        return new ConsoleTableReportGenerator($output);
    }

    /**
     * It should run without any options.
     */
    public function testNoOptions()
    {
        $this->executeReport($this->getResults(), array());
    }

    /**
     * It should change the precision.
     */
    public function testWithPrecision()
    {
        $this->executeReport($this->getResults(), array(
            'precision' => 2,
        ));
    }

    /**
     * It should change aggregate iterations.
     */
    public function testWithAggregateIterations()
    {
        $this->executeReport($this->getResults(), array(
            'aggregate_iterations' => true,
        ));
    }
}
