<?php

namespace Pantheon\TerminusAliases\Model;

use Symfony\Component\Filesystem\Filesystem;

class DrushSitesYmlEmitter implements AliasEmitterInterface
{
    protected $base_dir;
    protected $home;

    public function __construct($base_dir, $home, $target_name)
    {
        $this->base_dir = $base_dir;
        $this->home = $home;
        $this->target_name = $target_name;
    }

    public function write(AliasCollection $collection)
    {
        $pantheon_sites_dir = $this->base_dir . '/sites/' . $this->target_name;

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
        $drushConfig['drush']['paths']['alias-path'][] = str_replace($this->home, '${env.home}', $pantheon_sites_dir);
        $drushYmlEditor->writeDrushConfig($drushConfig);
    }

    protected function getAliasFragment($alias)
    {
        $site_name = $alias->siteName();
        $env_name = $alias->envName();
        $site_id = $alias->siteId();
        $db_password = $alias->dbPassword();
        $db_port = $alias->dbPort();

        $alias_fragment = <<<EOT
{$env_name}:
  host: appserver.{$env_name}.{$site_id}.drush.in
  options:
    db-allows-remote: true
    db-url: 'mysql://pantheon:{$db_password}@dbserver.{$env_name}.{$site_id}.drush.in:{$db_port}/pantheon'
    strict: 0
  paths:
    files: files
    drush-script: drush9
  uri: d{$env_name}-{$site_name}.pantheonsite.io
  user: {$env_name}.{$site_id}
  ssh:
    options: '-p 2222 -o "AddressFamily inet"'
EOT;

        return $alias_fragment;
    }
}
