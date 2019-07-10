<?php

namespace Pantheon\TerminusAliases\Model;

interface AliasEmitterInterface
{
    public function notificationMessage();
    public function write(AliasCollection $collection);
}
