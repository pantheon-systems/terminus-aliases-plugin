<?php

namespace Pantheon\TerminusAliases\Model;

use Symfony\Component\Filesystem\Filesystem;

abstract class AliasesDrushRcBase implements AliasEmitterInterface
{
    protected function getAliasContents(AliasCollection $collection)
    {
        $alias_file_contents = $this->getAliasHeader();

        foreach ($collection->all() as $name => $envs) {
            foreach ($envs->all() as $alias) {
                $alias_fragment = $this->getAliasFragment($alias);

                $alias_file_contents .= $alias_fragment . "\n";
            }
        }
    }

    protected function getAliasHeader()
    {
        $header = <<<EOT
<?php
  /**
   * Pantheon drush alias file, to be placed in your ~/.drush directory or the aliases
   * directory of your local Drush home. Once it's in place, clear drush cache:
   *
   * drush cc drush
   *
   * To see all your available aliases:
   *
   * drush sa
   *
   * See http://helpdesk.getpantheon.com/customer/portal/articles/411388 for details.
   */


EOT;
        return $header;
    }
    protected function getAliasFragment($alias)
    {
        $site_name = $alias->siteName();
        $env_name = $alias->envName();
        $site_id = $alias->siteId();
        $db_password = $alias->dbPassword();
        $db_port = $alias->dbPort();

        $alias_fragment = <<<EOT
  \$aliases['{$site_name}.{$env_name}'] = array(
    'uri' => '{$env_name}-{$site_name}.pantheonsite.io',
    'db-url' => 'mysql://pantheon:{$db_password}@dbserver.{$env_name}.{$site_id}.drush.in:{$db_port}/pantheon',
    'db-allows-remote' => TRUE,
    'remote-host' => 'appserver.{$env_name}.{$site_id}.drush.in',
    'remote-user' => '{$env_name}.{$site_id}',
    'ssh-options' => '-p 2222 -o "AddressFamily inet"',
    'path-aliases' => array(
      '%files' => 'files',
      '%drush-script' => 'drush',
     ),
  );
EOT;

        return $alias_fragment;
    }
}
