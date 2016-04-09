<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Sites;
use Terminus\Session;

/**
 * Generates all aliases for your Pantheon account
 *
 * @command sites
 */
class PantheonAliases extends TerminusCommand {

  /**
   * Object constructor
   *
   * @param array $options Elements as follow:
   * @return PantheonAliases
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
  }

  /**
   * Retrieves and writes Pantheon aliases to a file
   *
   * [--location=<location>]
   * : Location for the new file to be saved
   *
   * [--print]
   * : Print out the aliases after generation
   *
   * [--org-only]
   * : Only output organizational aliases
   *
   * [--team-only]
   * : Only output team aliases
   *
   * @subcommand aliases
   */
  public function allAliases($args, $assoc_args) {
    $location = $this->input()->optional(
      [
        'key'     => 'location',
        'choices' => $assoc_args,
        'default' => getenv('HOME') . '/.drush/pantheon.aliases.drushrc.php',
      ]
    );
    if (is_dir($location)) {
      $message  = 'Please provide a full path with filename,';
      $message .= ' e.g. {location}/pantheon.aliases.drushrc.php';
      $this->failure($message, compact('location'));
    }

    $file_exists = file_exists($location);

    // Create the directory if it doesn't yet exist
    $dirname = dirname($location);
    if (!is_dir($dirname)) {
      mkdir($dirname, 0700, true);
    }

    $options = [
      'org_only'  => isset($assoc_args['org-only']),  
      'team_only' => isset($assoc_args['team-only']),  
    ];
    $content = $this->getAliases($options);
    $handle  = fopen($location, 'w+');
    fwrite($handle, $content);
    fclose($handle);
    chmod($location, 0700);

    $message = 'Pantheon aliases created';
    if ($file_exists) {
      $message = 'Pantheon aliases updated';
    }
    $this->log()->info($message);

    if (isset($assoc_args['print'])) {
      $aliases = str_replace(array('<?php', '?>'), '', $content);
      $this->output()->outputDump($aliases);
    }
  }

  /**
   * Constructs a Drush alias for an environment. Used to supply
   *   organizational Drush aliases not provided by the API.
   *
   * @param Environment $environment Environment to create an alias for
   * @return string
   * @throws TerminusException
   */
  private function constructAlias($environment) {
    $info      = $environment->connectionInfo();
    $site_name = $environment->site->get('name');
    $site_id   = $environment->site->get('id');
    $env_id    = $environment->get('id');
    $hostnames = method_exists($environment, 'getHostnames') ? array_keys((array)$environment->getHostnames()) : $environment->hostnames->ids();

    if (empty($hostnames)) {
      throw new TerminusException(
        'No hostname entry for {site}.{env}',
        ['site' => $site_name, 'env' => $env_id,],
        1
      );
    }

    $uri         = array_shift($hostnames);
    $db_url      = $info['mysql_url'];
    $remote_host = $info['sftp_host'];
    $remote_user = $info['sftp_username'];
    $output      = "array(
    'uri'              => '$uri',
    'db-url'           => '$db_url',
    'db-allows-remote' => true,
    'remote-host'      => '$remote_host',
    'remote-user'      => '$remote_user',
    'ssh-options'      => '-p 2222 -o \"AddressFamily inet\"',
    'path-aliases'     => array(
      '%files'        => 'code/sites/default/files',
      '%drush-script' => 'drush',
    ),
  );";
    return $output;
  }

  /**
   * Requests API data and returns aliases
   *
   * @param array $arg_options Elements as follow:
   *    boolean org_only  Set to true for only organizational aliases
   *    boolean team_only Set to true for only team aliases
   * @return string
   */
  private function getAliases(array $arg_options = []) {
    $default_options = [
      'org_only'  => false,
      'team_only' => false,
    ];
    $options         = array_merge($default_options, $arg_options);

    $user         = Session::getUser();
    $alias_string = $user->getAliases();
    if ($options['team_only']) {
      return $alias_string;
    }

    eval(str_replace('<?php', '', $alias_string));
    $team_aliases = substr($alias_string, 0, -1);
    $sites_object = new Sites();
    $sites        = $sites_object->all();
    $org_aliases  = '';
    foreach ($sites as $site) {
      $environments = $site->environments->all();
      foreach ($environments as $environment) {
        $key = $site->get('name') . '.'. $environment->get('id');
        if (isset($aliases[$key])) {
          break;
        }
        try {
          $org_aliases .= PHP_EOL . "  \$aliases['$key'] = ";
          $org_aliases .= $this->constructAlias($environment);
        } catch (TerminusException $e) {
          continue;
        }
      }
    }

    if ($options['org_only']) {
      $org_aliases .= PHP_EOL;
      return $org_aliases;
    }
    $all_aliases = $alias_string . $org_aliases . PHP_EOL;
    return $all_aliases;
  }

}
