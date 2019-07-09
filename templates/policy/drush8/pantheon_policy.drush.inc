<?php

/**
 * Adjust the contents of a site alias.
 */
function pantheon_policy_drush_sitealias_alter(&$alias_record) {
  // If the alias is "remote", but the remote site is
  // the system this command is running on, convert the
  // alias record to a local alias.
  if (isset($alias_record['remote-host'])) {
    $host = $alias_record['remote-host'];
    if (!(preg_match('^appserver.', $host) && preg_match('.drush.in$', $host))) {
      throw new \Exception("the host doesn't begin with 'appserver.' or doesn't end with '.drush.in'")
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
