<?php

namespace Pantheon\TerminusAliases\Model;

use PHPUnit\Framework\TestCase;
use Pantheon\TerminusAliases\Fixtures;
use Symfony\Component\Console\Output\BufferedOutput;

class DrushRcEmitterTest extends TestCase
{
    /**
     * drushrcEmitterValues provides the expected results and inputs for testDrushrcEmitter
     */
    public function drushrcEmitterValues()
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
     * testDrushrcEmitter confirms that the alias collection sorts
     * its inputs correctly
     *
     * @dataProvider drushrcEmitterValues
     */
    public function testDrushrcEmitter($expectedPath, $rawAliasData, $withDbUrl)
    {
        $aliasCollection = Fixtures::aliasCollection($rawAliasData, $withDbUrl);
        $location = Fixtures::mktmpdir() . '/.drush/pantheon.aliases.drushrc.php';

        $emitter = new AliasesDrushrcEmitter($location);
        $emitter->write($aliasCollection);
        $this->assertFileExists($location);
        $actual = file_get_contents($location);
        $expected = Fixtures::load('drushrcEmitter/' . $expectedPath);

        $this->assertEquals(trim($expected), trim($actual));
    }
}
