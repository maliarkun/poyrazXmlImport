<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Parser;

use DOMDocument;
use DOMXPath;
use Poyraz\XmlImport\Logger\Logger;

class XmlParser
{
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * @param string $xmlContent
     * @param array<string, mixed> $mapping
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $xmlContent, array $mapping): array
    {
        $document = new DOMDocument();
        $document->loadXML($xmlContent);
        $xpath = new DOMXPath($document);

        $productNode = $mapping['product_node'] ?? '//product';
        $nodeList = $xpath->query($productNode);
        if ($nodeList === false) {
            return [];
        }

        $products = [];
        foreach ($nodeList as $node) {
            $product = [];
            foreach ($mapping['fields'] ?? [] as $field => $path) {
                $value = '';
                $result = $xpath->evaluate($path, $node);
                if ($result instanceof \DOMNodeList) {
                    $value = $result->length > 0 ? (string)$result->item(0)?->textContent : '';
                } else {
                    $value = (string)$result;
                }
                $product[$field] = trim($value);
            }

            if (!empty($mapping['arrays']) && is_array($mapping['arrays'])) {
                foreach ($mapping['arrays'] as $field => $path) {
                    $values = [];
                    $result = $xpath->query($path, $node);
                    if ($result !== false) {
                        foreach ($result as $child) {
                            $values[] = trim((string)$child->textContent);
                        }
                    }
                    $product[$field] = array_values(array_filter($values));
                }
            }

            $products[] = $product;
        }

        $this->logger->info(sprintf('Parsed %d products via XPath %s', count($products), $productNode));

        return $products;
    }
}
