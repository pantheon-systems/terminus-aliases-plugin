<?php

namespace Terminus\Commands;

use Terminus\Auth;
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
    Auth::ensureLogin();
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

    $content = $this->getAliases();
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
    $site_name   = $environment->site->get('name');
    $site_id     = $environment->site->get('id');
    $env_id      = $environment->get('id');
    $db_bindings = $environment->bindings->getByType('dbserver');
    $hostnames   = array_keys((array)$environment->getHostnames());
    if (empty($hostnames) || empty($db_bindings)) {
      throw new TerminusException(
        'No hostname entry for {site}.{env}',
        ['site' => $site_name, 'env' => $env_id,],
        1
      );
    }
    $db_binding = array_shift($db_bindings);
    $uri        = array_shift($hostnames);
    $db_pass    = $db_binding->get('password');
    $db_port    = $db_binding->get('port');
    if (strpos(TERMINUS_HOST, 'onebox') !== false) {
      $remote_user = "appserver.$env_id.$site_id";
      $remote_host = TERMINUS_HOST;
      $db_url      = "mysql://pantheon:$db_pass@$remote_host:$db_port";
      $db_url     .= '/pantheon';
    } else {
      $remote_user = "$env_id.$site_id";
      $remote_host = "appserver.$env_id.$site_id.drush.in";
      $db_url      = "mysql://pantheon:$db_pass@dbserver.$environment.$site_id";
      $db_url     .= ".drush.in:$db_port/pantheon";
    }
    $output = "array(
    'uri'              => $uri,
    'db-url'           => $db_url,
    'db-allows-remote' => true,
    'remote-host'      => $remote_host,
    'remote-user'      => $remote_user,
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
   * @return string
   */
  private function getAliases() {
    $user         = Session::getUser();
    $alias_string = $user->getAliases();
    eval(str_replace('<?php', '', $alias_string));
    $formatted_aliases = substr($alias_string, 0, -1);
    $sites_object = new Sites();
    $sites        = $sites_object->all();
    foreach ($sites as $site) {
      $environments = $site->environments->all();
      foreach ($environments as $environment) {
        $key = $site->get('name') . '.'. $environment->get('id');
        if (isset($aliases[$key])) {
          break;
        }
        try {
          $formatted_aliases .= PHP_EOL . "  \$aliases['$key'] = ";
          $formatted_aliases .= $this->constructAlias($environment);
        } catch (TerminusException $e) {
          continue;
        }
      }
    }
    $formatted_aliases .= PHP_EOL;
    return $formatted_aliases;
  }

}
