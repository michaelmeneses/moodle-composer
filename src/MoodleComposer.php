<?php

namespace Middag;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class MoodleComposer
{

    /**
     * preInstall
     *
     * @param \Composer\Script\Event $event
     */
    public static function preInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ preInstall ------------");
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (is_dir($installerdir) && file_exists($installerdir . "/version.php")) {
            throw new \Exception("Moodle is already installed in the folder: $installerdir.");
        }
    }

    /**
     * postInstall
     *
     * @param \Composer\Script\Event $event
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ postInstall ------------");
        self::moveMoodle($event);
        self::copyConfig($event);
    }

    /**
     * preUpdate
     *
     * @param \Composer\Script\Event $event
     */
    public static function preUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ preUpdate ------------");
        self::copyConfigToRoot($event);
    }

    /**
     * postUpdate
     *
     * @param \Composer\Script\Event $event
     */
    public static function postUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ postUpdate ------------");
        if (self::isNewMoodle($event)) {
            self::removeMoodle($event);
            self::moveMoodle($event);
            self::copyConfig($event);
            $io->write("DANGER! Run 'composer update' to reinstall plugins.");
        }
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (file_exists("$installerdir/config.php")) {
            self::cleanCache($event);
        }
    }

    /**
     * preUpdatePackage
     *
     * @param \Composer\Script\Event $event
     */
    public static function preUpdatePackage(PackageEvent $event)
    {
        $io = $event->getIO();
        $io->write("------------ preUpdatePackage ------------");

        $appDir = getcwd();
        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } else if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
        }
        if (isset($package) && $package instanceof PackageInterface) {
            $installationManager = $event->getComposer()->getInstallationManager();
            $path = $installationManager->getInstallPath($package);
            $io->write("Updating package ", FALSE);
            $io->write($package->getName());
        }
    }

    /**
     * copyConfigToRoot
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyConfigToRoot(Event $event)
    {
        $io = $event->getIO();
        $appDir = getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (file_exists("$installerdir/config.php")) {
            $io->write("Copying $installerdir/config.php to ROOT/");
            if (!copy("$appDir/$installerdir/config.php", "$appDir/config.php")) {
                $io->write("FAILURE");
            }
        } else {
            $io->write("File $installerdir/config.php not found!");
        }
    }

    /**
     * moveMoodle
     *
     * @param \Composer\Script\Event $event
     */
    public static function moveMoodle(Event $event)
    {
        $io = $event->getIO();
        $appDir = getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        $filesystem = new Filesystem();
        $io->write("Copying vendor/moodle/moodle to $installerdir/");
        $filesystem->copyThenRemove($appDir . "/vendor/moodle/moodle", $appDir . DIRECTORY_SEPARATOR . $installerdir);
    }

    /**
     * removeMoodle
     *
     * @param \Composer\Script\Event $event
     */
    public static function removeMoodle(Event $event)
    {
        $io = $event->getIO();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (is_dir($installerdir)) {
            $io->write("Removing $installerdir/");
            self::deleteRecursive($installerdir);
        }
    }

    /**
     * copyConfig
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyConfig(Event $event)
    {
        $io = $event->getIO();
        $appDir = getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (file_exists('config.php')) {
            $io->write("Copying config.php to $installerdir/");
            if (!copy("$appDir/config.php", "$appDir/$installerdir/config.php")) {
                $io->write("FAILURE");
            }
        }
    }

    /**
     * setMaintenance
     *
     * @param \Composer\Script\Event $event
     * @param boolean                $status
     */
    public static function setMaintenance(Event $event, $status = false)
    {
        $io = $event->getIO();
        $appDir = getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if ($status) {
            $io->write("Enabling Maintenance Mode");
            exec("php $appDir/$installerdir/admin/cli/maintenance.php --enable");
        } else {
            $io->write("Disabling Maintenance Mode");
            exec("php $appDir/$installerdir/admin/cli/maintenance.php --disable");
        }
    }

    /**
     * cleanCache
     *
     * @param \Composer\Script\Event $event
     * @param boolean                $status
     */
    public static function cleanCache(Event $event)
    {
        $io = $event->getIO();
        $appDir = getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        $io->write("Clearing the Moodle cache");
        exec("php $appDir/$installerdir/admin/cli/purge_caches.php");
    }

    /**
     * isNewMoodle
     *
     * @param \Composer\Script\Event $event
     * @param boolean                $status
     */
    public static function isNewMoodle(Event $event)
    {
        define("MOODLE_INTERNAL", true);
        define('MATURITY_ALPHA', 50);
        define('MATURITY_BETA', 100);
        define('MATURITY_RC', 150);
        define('MATURITY_STABLE', 200);
        define('ANY_VERSION', 'any');

        $io = $event->getIO();
        $appDir = getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];

        $oldVersion = 0;
        $newVersion = 0;

        $oldFile = $appDir . "/" . $installerdir . "/version.php";
        if (file_exists($oldFile)) {
            require_once $oldFile;
            if (isset($version)) {
                $oldVersion = $version;
            }
        } else {
            return false;
        }

        $newFile = $appDir . "/vendor/moodle/moodle/version.php";
        if (file_exists($newFile)) {
            require_once $newFile;
            if (isset($version)) {
                $newVersion = $version;
            }
        } else {
            return false;
        }

        if ($newVersion > $oldVersion) {
            $io->write("### NEW MOODLE DETECTED VERSION ###");
            return true;
        }

        return false;
    }

    /**
     * deleteRecursive
     */
    public static function deleteRecursive($path)
    {
        if (is_file($path) || is_link($path)) {
            return unlink($path);
        }
        $success = true;
        $dir = dir($path);
        while (($entry = $dir->read()) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $entry_path = $path . '/' . $entry;
            $success = static::deleteRecursive($entry_path) && $success;
        }
        $dir->close();
        return rmdir($path) && $success;
    }

}
