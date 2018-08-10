<?php

namespace Pantheon\TerminusAliases\Model;

use Symfony\Component\Filesystem\Filesystem;

class PrintingEmitter extends AliasesDrushRcBase
{
    public function write(AliasCollection $collection)
    {
        $alias_file_contents = $this->getAliasContents($collection);
        $this->output()->writeln($alias_file_contents);
    }
}
