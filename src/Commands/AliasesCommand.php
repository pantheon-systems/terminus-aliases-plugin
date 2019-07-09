<?php
/**
 * This variation on the Hello command shows how to add new subcommands to an
 * existing command.
 *
 * To add your subcommands to an existing command use a @command tag with the same
 * value as the existing tag.
 *
 * This command can be invoked by running `terminus auth hello`
 */

namespace Pantheon\TerminusAliases\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\TerminusAliases\Model\Greeter;

use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

use Pantheon\TerminusAliases\Model\AliasCollection;
use Pantheon\TerminusAliases\Model\AliasData;
use Pantheon\TerminusAliases\Model\AliasesDrushRcEmitter;
use Pantheon\TerminusAliases\Model\PrintingEmitter;
use Pantheon\TerminusAliases\Model\DrushSitesYmlEmitter;

use Symfony\Component\Console\Helper\ProgressBar;

use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Generate lots of aliases
 */
class AliasesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Generates Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     *
     * @authenticated
     *
     * @command alpha:aliases
     *
     * @option boolean $print Print aliases only (Drush 8 format)
     * @option string $location Path and filename for php aliases.
     * @option boolean $all Include all sites available, including team memberships.
     * @option string $type Type of aliases to create: 'php', 'yml' or 'all'.
     * @option string $base Base directory to write .yml aliases.
     * @option string $target Base name to use to generate path to alias files.
     *
     * @return string|null
     *
     * @usage Saves Pantheon Drush aliases for sites on which the currently logged-in user is on the team to ~/.drush/pantheon.aliases.drushrc.php.
     * @usage --print Displays Pantheon Drush 8 aliases for sites on which the currently logged-in user is on the team.
     * @usage --location=<full_path> Saves Pantheon Drush 8 aliases for sites on which the currently logged-in user is on the team to <full_path>.
     */
    public function aliases($options = [
        'print' => false,
        'location' => null,
        'all' => false,
        'type' => 'all',
        'base' => '~/.drush',
        'target' => 'pantheon',
    ])
    {
        // Be forgiving about the spelling of 'yaml'
        if ($options['type'] == 'yaml') {
            $options['type'] = 'yml';
        }

        $this->log()->notice("Fetching information to build Drush aliases...");
        $site_ids = $this->getSites($options);

        // Collect information on the requested sites
        $collection = $this->getAliasCollection($site_ids);

        // Write the alias files (only of the type requested)
        $emitters = $this->getAliasEmitters($options);
        if (empty($emitters)) {
            throw new \Exception('No emitters; nothing to do.');
        }
        foreach ($emitters as $emitter) {
            $this->log()->debug("Emitting aliases via {emitter}", ['emitter' => get_class($emitter)]);
            $this->log()->notice($emitter->notificationMessage());
            $emitter->write($collection);
        }
    }

    /**
     * Fetch those sites indicated by the commandline options.
     */
    protected function getSites($options)
    {
        if (!$options['all']) {
            return $this->getSitesWithDirectMembership();
        }
        return $this->getAllSites($options);
    }

    /**
     * Look up all available sites, as filtered by --org and --team
     */
    protected function getAllSites($options)
    {
        $user = $this->session()->getUser();
        $this->sites()->fetch(
            [
                'org_id' => null,
                'team_only' => false,
            ]
        );
        return $this->sites->ids();
    }

    /**
     * Look up those sites that the user has a direct membership in
     * (excluding sites )
     */
    protected function getSitesWithDirectMembership()
    {
        $user = $this->session()->getUser();
        $memberships = $user->getSiteMemberships();
        $site_ids = [];

        foreach ($memberships->ids() as $membership_id) {
            $membership = $memberships->get($membership_id);
            $site = $membership->get('site');
            $site_ids[] = $site->id;
        }
        return $site_ids;
    }

    /**
     * getAliasEmitters returns a list of emitters based on the provided options.
     */
    protected function getAliasEmitters($options)
    {
        $config = $this->getConfig();
        $home = $config->get('user_home');
        $base_dir = preg_replace('#^~#', $home, $options['base']);
        $target_name = $options['target'];
        $emitterType = $options['type'];
        if ($options['print']) {
            $emitterType = 'print';
        }
        $location = !empty($options['location']) ? $options['location'] : "$base_dir/$target_name.aliases.drushrc.php";
        $emitters = [];

        if ($this->emitterTypeMatches($emitterType, 'print', false)) {
            $emitters[] = new PrintingEmitter($this->output());
        }
        if ($this->emitterTypeMatches($emitterType, 'php')) {
            $emitters[] = new AliasesDrushRcEmitter($location, $base_dir);
        }
        if ($this->emitterTypeMatches($emitterType, 'yml')) {
            $emitters[] = new DrushSitesYmlEmitter($base_dir, $home, $target_name);
        }

        return $emitters;
    }

    protected function emitterTypeMatches($emitterType, $checkType, $default = true)
    {
        if (!$emitterType || ($emitterType === 'all')) {
            return $default;
        }
        return $emitterType === $checkType;
    }

    protected function getAliasCollection($site_ids)
    {
        $collection = new AliasCollection();

        $this->log()->notice("Collecting information about Pantheon sites and environments...");
        $out = $this->output()->getErrorOutput();
        $progressBar = new ProgressBar($out, count($site_ids));

        foreach ($site_ids as $site_id) {
            $site = $this->sites->get($site_id);
            $site_name = $site->get('name');

            $alias = new AliasData($site_name, '*', $site_id);
            $collection->add($alias);
            $progressBar->advance();
        }
        $progressBar->finish();
        $out->writeln('');

        return $collection;
    }
}
