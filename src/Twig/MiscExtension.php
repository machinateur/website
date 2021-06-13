<?php


namespace App\Twig;

use Exception;
use Twig\TwigFilter;

/**
 * Class MiscExtension
 * @package App\Twig
 */
class MiscExtension extends ExtensionAbstract
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('unique', [$this, 'unique']),
        ];
    }

    /**
     * @param array $value
     * @param string $sort_name
     * @return array
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function unique($value, $sort_name = 'regular')
    {
        if (!is_array($value)) {
            $this->throwTypeError('The "unique" filter only works with arrays or "Traversable", got "%s" as first argument.', $value);
        }

        if (!is_string($sort_name)) {
            $this->throwTypeError('The "unique" filter expects a string as sort_name value, got "%s".', $sort_name);
        } elseif (!in_array($sort_name, $whitelist = ['regular', 'numeric', 'string', 'locale_string'], true)) {
            $this->throwTypeError('The "unique" filter allows one of [' . implode(', ', $whitelist)
                . '] as sort_name value, got %s "' . $sort_name . '".', $sort_name);
        }

        /** @var int $sort_flag */
        $sort_flag = constant('SORT_' . strtoupper($sort_name));

        return array_unique($value, $sort_flag);
    }
}
