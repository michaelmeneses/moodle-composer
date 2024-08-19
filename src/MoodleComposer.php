<?php

namespace Middag;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Installers\MoodleInstaller;
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
    // Constant for the default installer directory
    const FRAMEWORK_TYPE = 'moodle';
    const INSTALLER_DIR = 'moodle';

    /**
     * Handles the pre-install event.
     *
     * @param Event $event The Composer event object.
     */
    public static function preInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ preInstall ------------");

        $installerdir = self::getInstallerDir($event);

        // TODO required that folder no exists
        if (is_dir($installerdir) && file_exists($installerdir . "/version.php")) {
            throw new \Exception("Moodle is already installed in the folder: $installerdir.");
        }
    }

    /**
     * Handles the post-install event.
     *
     * @param Event $event The Composer event object.
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ postInstall ------------");

        // TODO resolve need move or copy Moodle
        self::moveMoodle($event);
        self::copyConfig($event);
    }

    /**
     * Handles the pre-update event.
     *
     * @param Event $event The Composer event object.
     */
    public static function preUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ preUpdate ------------");

        self::copyConfigToRoot($event);
    }

    /**
     * Handles the post-update event.
     *
     * @param Event $event The Composer event object.
     */
    public static function postUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ postUpdate ------------");

        $installerdir = self::getInstallerDir($event);

        if (self::isNewMoodle($event)) {
            self::removeMoodle($event);
            self::moveMoodle($event);
            self::copyConfig($event);
            $io->write("<warning>DANGER! Run 'composer update' to reinstall plugins.</warning>");
        }

        if (file_exists("$installerdir/config.php")) {
            self::cleanCache($event);
        }
    }

    /**
     * Handles the pre-update-package event.
     *
     * @param PackageEvent $event The Composer package event object.
     */
    public static function preUpdatePackage(PackageEvent $event)
    {
        $io = $event->getIO();
        $io->write("------------ preUpdatePackage ------------");

        $package = self::getPackage($event);
        if (isset($package) && $package instanceof PackageInterface) {
            $io->write("Updating package ", FALSE);
            $io->write($package->getName());
        }
    }

    /**
     * Handles the post-package event.
     *
     * @param PackageEvent $event The Composer package event object.
     */
    public static function postPackage(PackageEvent $event)
    {
        $io = $event->getIO();
        $io->write("------------ postPackage ------------");

        $installerdir = self::getInstallerDir($event);

        $package = self::getPackage($event);

        if (!self::isMoodle($package)) {
            $packageType = $package->getType();
            if (str_starts_with($packageType, 'moodle-')) {
                if (!self::existsInstallerPath($event, $packageType)) {
                    $pluginType = str_replace('moodle-', '', $packageType);

                    try {
                        $moodleInstaller = new MoodleInstaller();
                        $locations = $moodleInstaller->getLocations();
                    } catch (\ArgumentCountError $exception) {
                        $moodleInstaller = new MoodleInstaller($package, $event->getComposer(), $io);
                        $locations = $moodleInstaller->getLocations(self::FRAMEWORK_TYPE);
                    } catch (\Exception $exception) {
                        throw $exception;
                    }

                    if (isset($locations[$pluginType])) {
                        $appDir = getcwd();
                        $path = $event->getComposer()->getInstallationManager()->getInstallPath($package);

                        if (strpos($path, $appDir) === 0) {
                            $path = str_replace($appDir . DIRECTORY_SEPARATOR, '', $path);
                        }

                        $currentPath = $appDir . DIRECTORY_SEPARATOR . $path;
                        $newPath = $appDir . DIRECTORY_SEPARATOR . $installerdir . DIRECTORY_SEPARATOR . $path;

                        try {
                            $filesystem = new Filesystem();
                            $filesystem->copyThenRemove($currentPath, $newPath);

                            while ($currentPath !== $appDir) {
                                if (is_dir($currentPath)) {
                                    if (count(scandir($currentPath)) == 2) {
                                        rmdir($currentPath);
                                    }
                                }
                                $paths = explode(DIRECTORY_SEPARATOR, $currentPath);
                                array_pop($paths);
                                $currentPath = implode(DIRECTORY_SEPARATOR, $paths);
                            }
                        } catch (\Exception $exception) {
                            $io->error($exception->getMessage());
                        }
                    }
                }
            }
        }

        if (isset($package) && $package instanceof PackageInterface) {
            $installationManager = $event->getComposer()->getInstallationManager();
            $path = $installationManager->getInstallPath($package);
            if (file_exists("$path/.gitmodules")) {
                $packageName = $package->getName();
                $io->write("This package $packageName own Submodules Git and they will install now");
                exec("cd $path && git submodule update --init");
            }
        }
    }

    /**
     * Retrieves the package associated with the Composer event.
     *
     * @param PackageEvent $event The Composer package event object.
     * @return PackageInterface|null The package associated with the event.
     */
    public static function getPackage(PackageEvent $event)
    {
        $operation = $event->getOperation();

        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } else if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else if ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
        }

        return $package;
    }

    /**
     * copyConfigToRoot
     *
     * Copies the "config.php" file from the installation directory to the root directory.
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function copyConfigToRoot(Event $event)
    {
        if (!self::canCopyConfig($event)) {
            return;
        }

        $io = $event->getIO();
        $appDir = getcwd();

        $installerdir = self::getInstallerDir($event);

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
     * Copies the "vendor/moodle/moodle" directory to the installation directory.
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function moveMoodle(Event $event)
    {
        $io = $event->getIO();
        $appDir = getcwd();

        $installerdir = self::getInstallerDir($event);

        $filesystem = new Filesystem();
        $io->write("Copying vendor/moodle/moodle to $installerdir/");
        $filesystem->copyThenRemove($appDir . "/vendor/moodle/moodle", $appDir . DIRECTORY_SEPARATOR . $installerdir);
    }

    /**
     * removeMoodle
     *
     * Removes the Moodle installation directory.
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function removeMoodle(Event $event)
    {
        $io = $event->getIO();

        $installerdir = self::getInstallerDir($event);

        if (is_dir($installerdir)) {
            $io->write("Removing $installerdir/");
            self::deleteRecursive($installerdir);
        }
    }

    /**
     * copyConfig
     *
     * Copies the "config.php" file from the root directory to the installation directory.
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function copyConfig(Event $event)
    {
        if (!self::canCopyConfig($event)) {
            return;
        }

        $io = $event->getIO();
        $appDir = getcwd();

        $installerdir = self::getInstallerDir($event);

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
     * Enables or disables Moodle maintenance mode.
     *
     * @param \Composer\Script\Event $event The Composer event.
     * @param boolean $status Indicates whether maintenance mode should be enabled (true) or disabled (false). Default is false.
     */
    public static function setMaintenance(Event $event, $status = false)
    {
        $io = $event->getIO();
        $appDir = getcwd();

        $installerdir = self::getInstallerDir($event);

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
     * Clears the Moodle cache.
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function cleanCache(Event $event)
    {
        if (!self::canCopyConfig($event)) {
            return;
        }

        $io = $event->getIO();
        $appDir = getcwd();

        $installerdir = self::getInstallerDir($event);

        $io->write("Clearing the Moodle cache");
        exec("php $appDir/$installerdir/admin/cli/purge_caches.php");
    }

    /**
     * isNewMoodle
     *
     * Checks if a new Moodle version is detected.
     *
     * @param \Composer\Script\Event $event The Composer event.
     * @return boolean Returns true if a new Moodle version is detected, false otherwise.
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

        $installerdir = self::getInstallerDir($event);

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
            $io->write("### NEW MOODLE VERSION DETECTED ###");
            return true;
        }

        return false;
    }

    /**
     * deleteRecursive
     *
     * Recursively deletes a file or directory.
     *
     * @param string $path The path to the file or directory to delete.
     * @return boolean Returns true on success, false on failure.
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
            $entry_path = $path . DIRECTORY_SEPARATOR . $entry;
            $success = static::deleteRecursive($entry_path) && $success;
        }
        $dir->close();
        return rmdir($path) && $success;
    }

    /**
     * Retrieves the installer directory from composer.json's extra configuration.
     *
     * @param Event|PackageEvent $event The Composer event object.
     * @return string The installer directory.
     */
    public static function getInstallerDir($event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        return $extra['installerdir'] ?? self::INSTALLER_DIR;
    }

    /**
     * Checks if an installer path exists for a given package type.
     *
     * @param Event|PackageEvent $event The Composer event object.
     * @param string $packageType The package type to check.
     * @return bool True if the installer path exists, false otherwise.
     */
    public static function existsInstallerPath($event, $packageType)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        return isset($extra['installer-paths'][$packageType]);
    }

    /**
     * Checks if a given package is Moodle.
     *
     * @param PackageInterface $package The package to check.
     * @return bool True if the package is Moodle, false otherwise.
     */
    public static function isMoodle($package)
    {
        if ($package->getName() === 'moodle/moodle') {
            return true;
        }
        return false;
    }

    /**
     * canCopyConfig
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function canCopyConfig(Event $event)
    {
        $thisConfig = $event->getComposer()->getConfig()->get('moodle-composer');

        if (isset($thisConfig['copy-config']) && $thisConfig['copy-config'] === false) {
            return false;
        }

        return true;
    }

    /**
     * canClearCache
     *
     * @param \Composer\Script\Event $event The Composer event.
     */
    public static function canClearCache(Event $event)
    {
        $thisConfig = $event->getComposer()->getConfig()->get('moodle-composer');

        if (isset($thisConfig['clear-cache']) && $thisConfig['clear-cache'] === false) {
            return false;
        }

        return true;
    }
}
