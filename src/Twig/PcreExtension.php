<?php


namespace App\Twig;

use Exception;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension as ExtensionAbstract;
use Twig\TwigFilter;
use function get_class;
use function gettype;
use function is_object;

/**
 * Class PcreExtension
 * @package App\Twig
 */
class PcreExtension extends ExtensionAbstract
{
    /**
     * PcreExtension constructor.
     * @throws Exception
     */
    public function __construct()
    {
        if (!extension_loaded('pcre')) {
            throw new Exception("The Twig PCRE extension requires the PHP PCRE extension.");
        }
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pcre_replace', [$this, 'pcre_replace']),
        ];
    }

    /**
     * @param string|array|string[] $value
     * @param string|array|string[] $pattern
     * @param string|array|string[] $replacement
     * @param int $limit
     * @return string|array|string[]|null
     * @throws Exception
     */
    public function pcre_replace($value, $pattern, $replacement = '', int $limit = -1)
    {
        if (!isset($value)) {
            return null;
        }

//        if (twig_test_iterable($pattern)) {
        if (is_array($pattern)) {
//            $pattern = twig_to_array($pattern, false);

            array_walk($pattern, function ($value, $key): void {
                $this->assertPatternWithoutModifier((string)$value);
            });
        } elseif (is_string($pattern)) {
            $this->assertPatternWithoutModifier($pattern);
        } else {
            $this->throwTypeException('The "pcre_replace" filter expects a string, an array or "Traversable" as pattern values, got "%s".', $pattern);
        }

//        if (twig_test_iterable($replacement)) {
        if (is_array($replacement)) {
//            $replacement = twig_to_array($replacement, false);
        } elseif(!is_string($replacement)) {
            $this->throwTypeException('The "pcre_replace" filter expects a string, an array or "Traversable" as replacement values, got "%s".', $replacement);
        }

        return preg_replace($pattern, $replacement, $subject = $value, $limit, $count);
    }

    /**
     * @param string $pattern
     * @param string $modifier
     * @throws RuntimeError
     */
    protected function assertPatternWithoutModifier(string $pattern, string $modifier = 'e'): void
    {
        $position = strrpos($pattern, $pattern[0]);
        $modifierPart = substr($pattern, $position + 1);

        if (strpos($modifierPart, $modifier) !== false) {
            throw new RuntimeError(sprintf('Using the "%s" modifier for regular expressions is not allowed.', $modifier));
        }
    }

    /**
     * @param string $message
     * @param mixed $value
     * @throws RuntimeError
     */
    protected function throwTypeException(string $message, $value): void
    {
        throw new RuntimeError(sprintf($message, is_object($value) ? get_class($value) : gettype($value)));
    }
}
