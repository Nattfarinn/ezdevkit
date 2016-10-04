<?php

namespace EzSystems\DevkitBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\Validators as BaseValidators;

class Validators
{
    public static function validateBundleNamespace($namespace)
    {
        if (preg_match('/Bundle$/', $namespace)) {
            throw new \InvalidArgumentException('The base (vendor) namespace must not end with "Bundle", are you sure you didn\'t include bundle name here?');
        }

        $namespace = strtr($namespace, '/', '\\');
        if (!preg_match('/^(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\?)+$/', $namespace)) {
            throw new \InvalidArgumentException('The namespace contains invalid characters.');
        }

        // validate reserved keywords
        $reserved = BaseValidators::getReservedWords();
        foreach (explode('\\', $namespace) as $word) {
            if (in_array(strtolower($word), $reserved)) {
                throw new \InvalidArgumentException(sprintf('The namespace cannot contain PHP reserved words ("%s").', $word));
            }
        }

        return $namespace;
    }

    public static function validateBundleName($bundle)
    {
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $bundle)) {
            throw new \InvalidArgumentException('The bundle name contains invalid characters.');
        }

        if (preg_match('/Bundle$/', $bundle)) {
            throw new \InvalidArgumentException('The bundle name must not end with "Bundle".');
        }

        return $bundle;
    }

    public static function validateExample($example)
    {
        $valid = in_array($example, ['yes', 'no']);

        if ($valid) {
            return $valid;
        }

        throw new \InvalidArgumentException('Please answer yes or no.');
    }
}