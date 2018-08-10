<?php

namespace Pantheon\TerminusAliases\Model;

use Symfony\Component\Filesystem\Filesystem;

class PrintingEmitter extends AliasesDrushRcBase
{
    protected $output;

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function write(AliasCollection $collection)
    {
        $alias_file_contents = $this->getAliasContents($collection);
        $this->output->writeln($alias_file_contents);
    }
}
