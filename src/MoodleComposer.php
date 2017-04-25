<?php

namespace Enap;

use Composer\Script\Event;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class MoodleComposer
{

    /**
     * postInstall
     *
     * @param \Composer\Script\Event $event
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ INSTALANDO ------------");
        $io->write("AGUARDE ENQUANTO CONCLUÍMOS ALGUMAS CONFIGURAÇÕES");
        self::moveMoodle($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * preUpdate
     *
     * @param \Composer\Script\Event $event
     */
    public static function preUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ PREPARANDO ------------");
        $io->write("AGUARDE ENQUANTO PREPARAMOS ALGUMAS CONFIGURAÇÕES");
        self::copyConfigToRoot($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * postUpdate
     *
     * @param \Composer\Script\Event $event
     */
    public static function postUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ ATUALIZANDO ------------");
        $io->write("AGUARDE ENQUANTO CONCLUÍMOS ALGUMAS CONFIGURAÇÕES");
        self::moveMoodle($event);
        self::copyConfig($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * copyConfigToRoot
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyConfigToRoot(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installer-dir'];
        if (file_exists('$installerdir/config.php')) {
            $io->write("Copiando $installerdir/config.php para ROOT/");
            exec("cp $appDir/$installerdir/config.php $appDir");
        } else {
            $io->write("ATENÇÃO!!! $installerdir/config.php não encontrado");
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
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installer-dir'];
        $io->write("Copiando vendor/moodle/moodle para $installerdir/");
        exec("cp -r $appDir/vendor/moodle/moodle/* $appDir/$installerdir/");
    }

    /**
     * copyConfig
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyConfig(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installer-dir'];
        $io->write("Copiando config.php para $installerdir/");
        exec("cp $appDir/config.php $appDir/$installerdir/");
    }

}
