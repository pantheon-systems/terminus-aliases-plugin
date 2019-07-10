<?php

use Drush\Log\LogLevel;

if (version_compare(DRUSH_VERSION, '8.3.0') < 0) {
  drush_log(dt('You are using an old version of Drush (' . DRUSH_VERSION . '). Please upgrade to 8.3.0 or later.'), LogLevel::WARNING);
}

/**
 * Adjust the contents of a site alias.
 */
function pantheon_policy_drush_sitealias_alter(&$alias_record) {
  // If the alias is "remote", but the remote site is
  // the system this command is running on, convert the
  // alias record to a local alias.
  if (isset($alias_record['remote-host'])) {
    $host = $alias_record['remote-host'];
    if (!(preg_match('^appserver\..*\.drush\.in$', $host))) {
      return;
    }
    $ip = gethostbyname($host);
    // If the return value of gethostbyname equals its input parameter,
    // that indicates failure.
    if ($host == $ip) {
      $aliasName = $alias_record['#name'];
      return drush_set_error('NO_SUCH_ALIAS', "The alias $aliasName refers to a multidev environment that does not exist.");
    }
  }
}
