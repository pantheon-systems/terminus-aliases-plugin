<?php

namespace Pantheon\TerminusAliases\Model;

use Symfony\Component\Filesystem\Filesystem;

class DrushSitesYmlEmitter implements AliasEmitterInterface
{
    protected $base_dir;
    protected $home;

    public function __construct($base_dir, $home, $target_name = 'pantheon')
    {
        $this->base_dir = $base_dir;
        $this->home = $home;
        $this->target_name = $target_name;
    }

    public function notificationMessage()
    {
        $pantheon_sites_dir = $this->pantheonSitesDir();

        return 'Writing Drush 9 alias files to ' . $pantheon_sites_dir;
    }

    public function write(AliasCollection $collection)
    {
        $pantheon_sites_dir = $this->pantheonSitesDir();

        $fs = new Filesystem();
        $fs->mkdir($pantheon_sites_dir);

        foreach ($collection->all() as $name => $envs) {
            $alias_file_contents = '';
            foreach ($envs->all() as $alias) {
                $alias_fragment = $this->getAliasFragment($alias);

                $alias_file_contents .= $alias_fragment . "\n";
            }
            file_put_contents("{$pantheon_sites_dir}/{$name}.site.yml", $alias_file_contents);
        }

        // Add in our directory location to the Drush alias file search path
        $drushYmlEditor = new DrushYmlEditor($this->base_dir);
        $drushConfig = $drushYmlEditor->getDrushConfig();
        if (!is_null($drushConfig['drush']['paths']['alias-path'])) {
            $drushConfig['drush']['paths']['alias-path'] = array_filter($drushConfig['drush']['paths']['alias-path'], array($this, 'filterForPantheon'));
        }
        if (!is_null($drushConfig['drush']['paths']['include'])) {
            $drushConfig['drush']['paths']['include'] = array_filter($drushConfig['drush']['paths']['alias-path'], array($this, 'filterForPantheon'));
        }
        $drushConfigFiltered['drush']['paths']['alias-path'][] = str_replace($this->home, '${env.home}', $pantheon_sites_dir);
        $drushConfigFiltered['drush']['paths']['include'][] = '${env.home}/.drush/pantheon';
        $drushYmlEditor->writeDrushConfig($drushConfigFiltered);
    }

    protected function filterForPantheon($line)
    {
        if (strpos($line, 'pantheon')) {
            return false;
        }
        return true;
    }

    protected function getAliasFragment($alias)
    {
        return Template::process('fragment.site.yml.tmpl', $alias->replacements());
    }

    protected function pantheonSitesDir()
    {
        return $this->base_dir . '/sites/' . $this->target_name;
    }
}
