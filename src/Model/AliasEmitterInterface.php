<?php

namespace Pantheon\TerminusAliases\Model;

interface AliasEmitterInterface
{
    public function write(AliasCollection $collection);
}
