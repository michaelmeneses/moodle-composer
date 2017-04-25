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
        self::updateMoodledata($event);
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
        self::moveConfig($event);
        self::updateMoodledata($event);
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
        if (file_exists('moodle/config.php')) {
            $io->write("Copiando moodle/config.php para ROOT/");
            exec("cp $appDir/moodle/config.php $appDir");
        } else {
            $io->write("ATENÇÃO!!! moodle/config.php não encontrado");
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
        $io->write("Copiando moodle/vendor/moodle/moodle para moodle/");
        exec("cp -r $appDir/moodle/vendor/moodle/moodle/* $appDir/moodle/");
        $io->write("Removendo moodle/vendor/moodle");
        exec("rm -r $appDir/moodle/vendor/moodle");
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
        $io->write("Copiando config.php para moodle/");
        exec("cp $appDir/config.php $appDir/moodle/");
    }

    /**
     * updateMoodledata
     *
     * @param \Composer\Script\Event $event
     */
    public static function updateMoodledata(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        if (is_dir('moodledata')) {
            $io->write("Atualizando permissões para moodledata/");
            chmod('moodledata', 0777);
        } else {
            $io->write("Criando moodledata/");
            mkdir('moodledata');
            self::updateMoodledata($event);
        }
    }

}
