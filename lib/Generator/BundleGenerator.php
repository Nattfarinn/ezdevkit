<?php

namespace EzSystems\Devkit\Generator;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Container;

class BundleGenerator
{
    const DIR = './src';

    private $filesystem;

    private $skeletonDirs;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate($namespace, $bundle, $example = false)
    {
        $dir = self::DIR . '/' . str_replace('_', '-', Container::underscore($bundle . 'Bundle'));

        if (!$this->filesystem->isAbsolutePath($dir)) {
            $dir = getcwd().'/'.$dir;
        }

        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $fullname = str_replace('\\', '', $namespace) . $bundle;

        $parameters = [
            'bundle_namespace' => "{$namespace}\\{$bundle}Bundle",
            'lib_namespace'    => "{$namespace}\\{$bundle}",
            'bundle_name'      => $bundle,
            'bundle_name_yml'  => Container::underscore($bundle),
            'bundle_fullname'  => $fullname,
            'bundle_alias'     => Container::underscore($fullname),
            'example'          => $example,
        ];

        $parameterFilenameKeys = array_map(function($key) {
            return "[{$key}]";
        }, array_keys($parameters));

        $exampleFiles = [
            'lib/Core/REST/Server/Output/ValueObjectVisitor/Example.php.twig',
            'lib/Core/REST/Client/Input/ExampleCreate.php.twig',
            'lib/Core/SignalSlot/Signal/ExampleCreateSignal.php.twig',
            'lib/Core/SignalSlot/Slot/ExampleLoggerSlot.php.twig',
            'lib/API/Repository/ValueObject/ExampleStruct.php.twig',
            'lib/API/Repository/ValueObject/ExampleCreateStruct.php.twig',
            'lib/SPI/Persistence/ValueObject/ExampleStruct.php.twig',
            'lib/SPI/Persistence/ValueObject/ExampleCreateStruct.php.twig',
        ];

        foreach ($this->skeletonDirs as $skeletonDir) {
            if (!$this->filesystem->isAbsolutePath($skeletonDir)) {
                $skeletonDir = getcwd() . '/' . $skeletonDir;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $skeletonDir,
                    FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
                )
            );

            $iterator->rewind();

            while ($iterator->valid()) {
                $filepath = $iterator->getSubPathName();


                if ($example || !in_array($filepath, $exampleFiles)) {
                    $output = $dir . '/' . preg_replace('/\.twig$/', '', str_replace($parameterFilenameKeys, array_values($parameters), $filepath));
                    $this->renderFile($filepath, $output, $parameters);
                }

                $iterator->next();
            }
        }
    }

    public function setSkeletonDirs($skeletonDirs)
    {
        $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
    }

    protected function render($template, $parameters)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ));

        return $twig->render($template, $parameters);
    }

    protected function renderFile($template, $target, $parameters)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $this->render($template, $parameters));
    }
}
