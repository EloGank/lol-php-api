<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Server\Formatter;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class XmlClientFormatter implements ClientFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $results)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0"?><root></root>');

        $this->toXml($results, $xml);

        return trim(preg_replace('/\s+/', '', $xml->asXML()));
    }

    /**
     * @param array             $results
     * @param \SimpleXMLElement $xml
     */
    private function toXml($results, \SimpleXMLElement &$xml)
    {
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                // Indexed array item
                if (!is_numeric($key)) {
                    $node = $xml->addChild($key);

                    $this->toXml($value, $node);
                }
                // Associative array item
                else {
                    $node = $xml->addChild('node');
                    $node->addAttribute('item', $key + 1);

                    $this->toXml($value, $node);
                }
            }
            else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
}