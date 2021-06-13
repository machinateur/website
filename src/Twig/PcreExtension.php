<?php


namespace App\Twig;

use Exception;
use Twig\Error\RuntimeError;
use Twig\TwigFilter;

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
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function pcre_replace($value, $pattern, $replacement = '', $limit = -1)
    {
        if (!isset($value)) {
            return null;
        } elseif (!(is_array($value) || is_string($value))) {
            $this->throwTypeError('The "pcre_replace" filter only works with arrays or "Traversable", got "%s" as first argument.', $value);
        }

        if (is_array($pattern)) {
            array_walk($pattern, function ($value, $key): void {
                $this->assertPatternWithoutModifier((string)$value);
            });
        } elseif (is_string($pattern)) {
            $this->assertPatternWithoutModifier($pattern);
        } else {
            $this->throwTypeError('The "pcre_replace" filter expects a string, an array or "Traversable" as pattern values, got "%s".', $pattern);
        }

        if(!(is_array($replacement) || is_string($replacement))) {
            $this->throwTypeError('The "pcre_replace" filter expects a string, an array or "Traversable" as replacement values, got "%s".', $replacement);
        }

        if (is_numeric($limit)) {
            $limit = (int)$limit;
        } else {
            $this->throwTypeError('The "pcre_replace" filter expects a number as limit value, got "%s".', $limit);
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
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
}
