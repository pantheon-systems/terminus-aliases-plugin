<?php

namespace Pantheon\TerminusAliases\Model;

use PHPUnit\Framework\TestCase;
use Pantheon\TerminusAliases\Fixtures;

class AliasCollectionTest extends TestCase
{
    /**
     * aliasCollectionSortingValues provides the expected results and inputs for testAliasCollectionSorting
     */
    public function aliasCollectionSortingValues()
    {
        return [
            [
                'agency,demo,personalsite',
                Fixtures::aliasCollection(
                    [
                        'personalsite' => [],
                        'demo' => [],
                        'agency' => [],
                    ]
                ),
            ],
            [
                'site9,site13,site78,site201',
                Fixtures::aliasCollection(
                    [
                        'site201' => [],
                        'site13' => [],
                        'site9' => [],
                        'site78' => [],
                    ]
                ),
            ],
        ];
    }

    /**
     * testAliasCollectionSorting confirms that the alias collection sorts
     * its inputs correctly
     *
     * @dataProvider aliasCollectionSortingValues
     */
    public function testAliasCollectionSorting($expected, $aliasCollection)
    {
        $all = $aliasCollection->all();
        $actual = implode(',', array_keys($all));
        $this->assertEquals($expected, $actual);
    }
}
