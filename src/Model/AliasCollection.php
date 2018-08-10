<?php
/**
 * Collect aliases.
 *
 * This class collects aliases, which may be provided in any order.
 * It then sorts them and returns the result when requested.
 */

namespace Pantheon\TerminusAliases\Model;

/**
 * Collect aliases
 */
class AliasCollection
{
    /** @var EnvironmentCollection[] */
    protected $aliases;

    public function __construct()
    {
        $this->aliases = [];
    }

    public function add(AliasData $alias)
    {
        $environmentCollection = $this->getCollection($alias->siteName());
        $environmentCollection->add($alias);
    }

    public function all()
    {
        ksort($this->aliases);
        return $this->aliases;
    }

    public function count()
    {
        return count($this->aliases);
    }

    protected function getCollection($name)
    {
        if (!isset($this->aliases[$name])) {
            $environmentCollection = new EnvironmentCollection();
            $this->aliases[$name] = $environmentCollection;
        }
        return $this->aliases[$name];
    }
}
