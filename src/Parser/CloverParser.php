<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Parser;

use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\Exception\CloverReportNotParseableException;
use SimpleXMLElement;

readonly class CloverParser implements CloverParserInterface
{
    private const XPATH_METHODS = "//line[@type='method']";

    /**
     * @return array<Method>
     * @throws CloverReportNotParseableException
     */
    public function parseMethods(string $cloverReportContent): array
    {
        try {
            $xml = new SimpleXMLElement($cloverReportContent);
        } catch (\Exception $e) {
            throw new CloverReportNotParseableException('Clover report not parseable', $e->getCode(), $e);
        }

        $rawMethods = $xml->xpath(self::XPATH_METHODS);

        if (!is_array($rawMethods)) {
            return [];
        }

        return array_map(
            fn (SimpleXMLElement $rawMethod): Method => new Method(
                (string)$rawMethod->xpath("..")[0]->class['name'],
                (string)$rawMethod['name'],
                (int)$rawMethod['crap']
            ),
            $rawMethods
        );
    }
}