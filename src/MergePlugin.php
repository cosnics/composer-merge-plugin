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
     * @var string[]
     */
    private $packageNamespaces = array();

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
        $this->discoverPackages('');
        var_dump($this->packageNamespaces);
        exit();

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

    /**
     *
     * @param string $namespace
     */
    protected function discoverPackages($rootNamespace)
    {
        $blacklist = $this->getBlacklistedFolders();
        $folders = $this->getDirectoryContent($this->namespaceToFullPath($rootNamespace));

        foreach ($folders as $folder)
        {
            if (! in_array($folder, $blacklist) && substr($folder, 0, 1) != '.')
            {
                $folderNamespace = ($rootNamespace ? $rootNamespace . '\\' : '') . $folder;

                if ($this->verifyPackage($folderNamespace))
                {
                    $this->addPackageNamespace($folderNamespace);
                }

                $this->discoverPackages($folderNamespace);
            }
        }
    }

    protected function namespaceToFullPath($namespace = null)
    {
        return $this->getBasePath() . ($namespace ? $this->namespaceToPath($namespace) . DIRECTORY_SEPARATOR : '');
    }

    /**
     *
     * @param string $namespace
     * @param boolean $web
     * @return string
     */
    protected function namespaceToPath($namespace)
    {
        return strtr($namespace, '\\', DIRECTORY_SEPARATOR);
    }

    protected function getBasePath()
    {
        return realpath(__DIR__ . '/../../../../src/') . DIRECTORY_SEPARATOR;
    }

    /**
     *
     * @param string $packageNamespace
     */
    protected function addPackageNamespace($packageNamespace)
    {
        $this->packageNamespaces[] = $packageNamespace;
    }

    protected function getDirectoryContent($path)
    {
        $result = array();

        if (! file_exists($path))
        {
            return $result;
        }

        $it = new \DirectoryIterator($path);

        foreach ($it as $entry)
        {
            if ($it->isDot())
            {
                continue;
            }

            if ($entry->isDir())
            {
                $result[] = $entry->__toString();
            }
        }

        return $result;
    }

    /**
     *
     * @return string[]
     */
    protected function getBlacklistedFolders()
    {
        return array('.git', '.hg', 'build', 'Build', 'plugin', 'resources', 'Resources', 'Test');
    }

    /**
     *
     * @param string $folderNamespace
     * @return boolean
     */
    protected function verifyPackage($folderNamespace)
    {
        return file_exists($this->namespaceToFullPath($folderNamespace) . DIRECTORY_SEPARATOR . 'composer.json');
    }
}