<?php

/**
 * @file
 *
 */

namespace Pantheon\TerminusAliases\Model;

use Consolidation\Comments\Comments;
use Symfony\Component\Yaml\Yaml;

class DrushYmlEditor
{
    protected $dir;
    protected $comments;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Return the path to the drushrc.php file.
     */
    public function getDrushRCPath()
    {
        return $this->dir . "/drushrc.php";
    }

    /**
     * Load the drushrc.php file and return its parsed contents.
     */
    public function getDrushConfig()
    {
        $drushRCPath = $this->getDrushRCPath();

        // Load the drushrc.php file
        if (file_exists($drushRCPath)) {
            $drushRCContents = file_get_contents($drushRCPath);
        } else {
            //$drushRCContents = Template::load('initial.drush.yml');
        }
        //$drushRC = Yaml::parse($drushRCContents);
        $this->comments = new Comments();
        $this->comments->collect(explode("\n", $drushRCContents));
        return $drushRCContents;
    }

    /**
     * Write a modified drushrc.php file back to disk.
     */
    public function writeDrushConfig($drushRC)
    {
        //string contains check
        //file append
        $drushRCPath = $this->getDrushRCPath();
        if(strpos(file_get_contents($drushRCPath, drushRC)) != false) {
            return file_put_contents($drushRCPath, $drushRC);
        }

        return false;
    }
}
