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

/**
 * Generate lots of aliases
 */
class AliasesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Write Drush alias files. Drush 8 php format and Drush 9 yml format files supported.
     *
     * @command alpha:aliases
     *
     * @authenticated
     */
    public function allAliases(
        $options = [
            'mine-only' => false,
            'org' => 'all',
            'team' => false,
            'type' => 'all',
            'base' => false,
            'print' => false,
            'location' => null,
            'target' => 'pantheon',
        ]
    ) {
        $this->log()->notice("Fetching list of available Pantheon sites...");
        $site_ids = $this->getSites($options);

        // Collect information on the requested sites
        $collection = $this->getAliasCollection($site_ids);

        // Write the alias files (only of the type requested)
        $this->log()->notice("Writing alias files...");
        $emitters = $this->getAliasEmitters($options);
        foreach ($emitters as $emitter) {
            $this->log()->debug("Emitting aliases via {emitter}", ['emitter' => get_class($emitter)]);
            $emitter->write($collection);
        }
    }

    /**
     * Fetch those sites indicated by the commandline options.
     */
    protected function getSites($options)
    {
        if ($options['mine-only']) {
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
                'org_id' => (isset($options['org']) && ($options['org'] !== 'all')) ? $user->getOrganizationMemberships()->get($options['org'])->getOrganization()->id : null,
                'team_only' => isset($options['team']) ? $options['team'] : false,
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
        $base_dir = !empty($options['base']) ? $options['base'] : "$home/.drush";
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
            $emitters[] = new AliasesDrushRcEmitter($location);
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
            //$this->log()->notice($site_id);
            $site = $this->sites->get($site_id);
            //$this->log()->notice($site->get('id'));
            $site_name = $site->get('name');

            $environments = $site->getEnvironments();
            // $this->log()->notice(var_export($site->getEnvironments()->serialize(), true));

            foreach ($site->getEnvironments()->all() as $env_name => $env) {
                $db_password = '';
                $db_port = '';
                $alias = new AliasData($site_name, $env_name, $site_id, $db_password, $db_port);

                $collection->add($alias);
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $out->writeln('');

        return $collection;
    }
}
