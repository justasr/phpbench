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

require_once __DIR__ . '/assets/parsertest/ParserCase.php';
require_once __DIR__ . '/assets/parsertest/ParserCaseInvalidAnnotation.php';

class BenchParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parser = new BenchParser();
    }

    /**
     * It should parse all of the bench methods and return anarray of
     * BenchSubject instances.
     *
     * @dataProvider provideParseMethodDoc
     */
    public function testParseMethodDoc($docComment, $expected)
    {
        $result = $this->parser->parseMethodDoc($docComment);
        $this->assertEquals($result, $expected);
    }

    public function provideParseMethodDoc()
    {
        return array(
            array(
                <<<EOT
/**
* @description Hello
* @beforeMethod beforeMe
* @beforeMethod afterBeforeMe
* @paramProvider provideParam
* @iterations  3
*/
EOT
                ,
                array(
                    'description' => 'Hello',
                    'iterations' => 3,
                    'beforeMethod' => array('beforeMe', 'afterBeforeMe'),
                    'paramProvider' => array('provideParam'),
                ),
            ),
            array(
                <<<EOT
/**
*/
EOT
                ,
                array(
                    'description' => '',
                    'beforeMethod' => array(),
                    'paramProvider' => array(),
                    'iterations' => 1,
                ),
            ),
        );
    }

    /**
     * It should thow an exception if an unknown annotation is found.
     *
     * @expectedException \PhpBench\Exception\InvalidArgumentException
     */
    public function testInvalidAnnotation()
    {
        $doc = '/** @asdasd */';
        $this->parser->parseMethodDoc($doc);
    }

    /*
     * It should throw an exception if more than one description annotation is present
     */

    /*
     * It should thow an exception if more than one iterations annotation is present
     */
}
