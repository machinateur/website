<?php


namespace App\Twig;

use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;

/**
 * Class ExtensionAbstract
 * @package App\Twig
 */
abstract class ExtensionAbstract extends AbstractExtension
{
    /**
     * @param string $message
     * @param mixed $value
     * @throws RuntimeError
     */
    protected function throwTypeError(string $message, $value): void
    {
        throw new RuntimeError(sprintf($message, is_object($value) ? get_class($value) : gettype($value)));
    }
}
