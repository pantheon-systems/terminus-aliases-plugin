<?php

namespace Pantheon\TerminusAliases\Model;

use Symfony\Component\Filesystem\Filesystem;

class AliasesDrushRcEmitter extends AliasesDrushRcBase
{
    protected $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function write(AliasCollection $collection)
    {
        $alias_file_contents = $this->getAliasContents($collection);

        $fs = new Filesystem();
        $fs->mkdir(dirname($this->location));

        file_put_contents($this->location, $alias_file_contents);
    }
}
