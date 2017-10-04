<?php
namespace Cosnics\Composer;

use Composer\Composer;
use Composer\EventDispatcher\Event as BaseEvent;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class MergePlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Name of the composer 1.1 init event.
     */
    const COMPAT_PLUGINEVENTS_INIT = 'init';

    /**
     * Priority that plugin uses to register callbacks.
     */
    const CALLBACK_PRIORITY = 1;

    /**
     *
     * @var Composer $composer
     */
    protected $composer;

    /**
     *
     * {@inheritdoc}
     *
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function getSubscribedEvents()
    {
        return array(
            // Use our own constant to make this event optional. Once
            // composer-1.1 is required, this can use PluginEvents::INIT
            // instead.
            self::COMPAT_PLUGINEVENTS_INIT => array('onInit', self::CALLBACK_PRIORITY));
    }

    /**
     * Handle an event callback for initialization.
     *
     * @param \Composer\EventDispatcher\Event $event
     */
    public function onInit(BaseEvent $event)
    {
        $package = $this->composer->getPackage();
        $extra = $package->getExtra();
        $extra['merge-plugin']['include'] = array('src/Chamilo/Libraries/composer.json');
        $extra['merge-plugin']['recurse'] = true;
        $extra['merge-plugin']['replace'] = false;
        $extra['merge-plugin']['ignore-duplicates'] = false;
        $extra['merge-plugin']['merge-dev'] = true;
        $extra['merge-plugin']['merge-extra'] = false;
        $extra['merge-plugin']['merge-extra-deep'] = false;
        $extra['merge-plugin']['merge-scripts'] = false;
        $package->setExtra($extra);
    }
}