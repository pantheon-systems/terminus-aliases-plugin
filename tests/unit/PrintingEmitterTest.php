<?php

namespace Pantheon\TerminusAliases\Model;

use PHPUnit\Framework\TestCase;
use Pantheon\TerminusAliases\Fixtures;
use Symfony\Component\Console\Output\BufferedOutput;

class PrintingEmitterTest extends TestCase
{
    /**
     * printingEmitterValues provides the expected results and inputs for testPrintingEmitter
     */
    public function printingEmitterValues()
    {
        return [
            [
                'standardAliasFixtureWithDbUrl.out',
                Fixtures::standardAliasFixture(),
                true,
            ],

            [
                'standardAliasFixtureWithoutDbUrl.out',
                Fixtures::standardAliasFixture(),
                false,
            ],
        ];
    }

    /**
     * testPrintingEmitter confirms that the alias collection sorts
     * its inputs correctly
     *
     * @dataProvider printingEmitterValues
     */
    public function testPrintingEmitter($expectedPath, $rawAliasData, $withDbUrl)
    {
        $aliasCollection = Fixtures::aliasCollection($rawAliasData, $withDbUrl);
        $buffer = new BufferedOutput();

        $emitter = new PrintingEmitter($buffer);
        $emitter->write($aliasCollection);
        $actual = $buffer->fetch();
        $expected = Fixtures::load('drushrcEmitter/' . $expectedPath);

        $this->assertEquals(trim($expected), trim($actual));
    }
}
