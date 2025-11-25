<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Mapper;

use SimpleXMLElement;

class ProductMapper
{
    private const DEFAULT_PRODUCT_NODE = '//product';

    /**
     * @param SimpleXMLElement $node
     * @param array<string, mixed> $source
     * @return array<string, mixed>|null
     */
    public function map(SimpleXMLElement $node, array $source): ?array
    {
        $mapping = $this->getMappingConfig($source);

        $sku = $this->readValue($node, $mapping['sku_field']);
        if ($sku === '') {
            return null;
        }

        $categories = $this->readMultiple($node, $mapping['category_field']);
        $images = $this->readMultiple($node, $mapping['image_field']);

        return [
            'sku' => $sku,
            'name' => $this->readValue($node, $mapping['name_field']) ?: $sku,
            'description' => $this->readValue($node, $mapping['description_field']),
            'price' => $this->readValue($node, $mapping['price_field']),
            'special_price' => $this->readValue($node, $mapping['special_price_field']),
            'qty' => $this->readValue($node, $mapping['stock_field']) ?: 0,
            'status' => $this->readValue($node, $mapping['status_field']),
            'visibility' => $this->readValue($node, $mapping['visibility_field']),
            'tax_class' => $this->readValue($node, $mapping['tax_class_field']),
            'categories' => $categories,
            'images' => $images,
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, string>
     */
    public function getMappingConfig(array $source): array
    {
        $mapping = $source['mapping'] ?? [];

        return [
            'product_node' => (string)($mapping['product_node'] ?? self::DEFAULT_PRODUCT_NODE),
            'sku_field' => (string)($mapping['sku_field'] ?? 'sku'),
            'name_field' => (string)($mapping['name_field'] ?? 'name'),
            'description_field' => (string)($mapping['description_field'] ?? 'description'),
            'price_field' => (string)($mapping['price_field'] ?? 'price'),
            'special_price_field' => (string)($mapping['special_price_field'] ?? ''),
            'stock_field' => (string)($mapping['stock_field'] ?? 'qty'),
            'status_field' => (string)($mapping['status_field'] ?? ''),
            'visibility_field' => (string)($mapping['visibility_field'] ?? ''),
            'tax_class_field' => (string)($mapping['tax_class_field'] ?? ''),
            'image_field' => (string)($mapping['image_field'] ?? 'images/image'),
            'category_field' => (string)($mapping['category_field'] ?? 'categories/category'),
        ];
    }

    private function readValue(SimpleXMLElement $node, string $path): string
    {
        if ($path === '') {
            return '';
        }

        $result = $node->xpath($path);
        if ($result === false || $result === []) {
            return '';
        }

        $first = $result[0];
        return trim((string)$first);
    }

    /**
     * @param SimpleXMLElement $node
     * @param string $path
     * @return array<int, string>
     */
    private function readMultiple(SimpleXMLElement $node, string $path): array
    {
        if ($path === '') {
            return [];
        }

        $result = $node->xpath($path);
        if ($result === false || $result === []) {
            return [];
        }

        $values = [];
        foreach ($result as $item) {
            $value = trim((string)$item);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }
}
