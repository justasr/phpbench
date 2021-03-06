<?php

/*
 * This file is part of the PHP Bench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpBench;

class BenchSubjectBuilder
{
    private $parser;

    public function __construct()
    {
        $this->parser = new BenchParser();
    }

    public function buildSubjects(BenchCase $case)
    {
        $reflection = new \ReflectionClass(get_class($case));
        $methods = $reflection->getMethods();

        $subjects = array();
        foreach ($methods as $method) {
            if (0 !== strpos($method->getName(), 'bench')) {
                continue;
            }

            $meta = $this->parser->parseMethodDoc($method->getDocComment());

            $subjects[] = new BenchSubject(
                $method->getName(),
                $meta['beforeMethod'],
                $meta['paramProvider'],
                $meta['iterations'],
                $meta['description']
            );
        }

        return $subjects;
    }
}
