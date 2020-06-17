#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Composer plugin have been installed correctly
#

@test "confirm terminus version" {
  terminus --version
}

@test "get help on alpha:aliases command" {
  run terminus help alpha:aliases
  [[ $output == *"Saves Pantheon Drush aliases"* ]]
  [ "$status" -eq 0 ]
}

@test "run alpha:aliases command" {
  run terminus alpha:aliases
  [[ $output == *"Fetching site information to build Drush aliases"* ]]
  [ "$status" -eq 0 ]
}
