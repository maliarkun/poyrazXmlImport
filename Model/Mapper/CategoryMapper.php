<?php
declare(strict_types=1);

namespace Poyraz\XmlImport\Model\Mapper;

class CategoryMapper
{
    /**
     * @param string|array<int, string>|null $rawCategories
     * @param array<string, mixed> $source
     * @return array<int, string>
     */
    public function mapCategories(string|array|null $rawCategories, array $source): array
    {
        $categories = $this->normalizeRawCategories($rawCategories);
        if ($categories === []) {
            return [];
        }

        $mapping = $this->normalizeMapping($source['category_mapping'] ?? []);
        $result = [];

        foreach ($categories as $category) {
            $lower = mb_strtolower($category);
            if (isset($mapping[$lower])) {
                $result[] = $mapping[$lower];
                continue;
            }

            $result[] = 'Imported/' . ltrim($category, '/');
        }

        return array_values(array_unique($result));
    }

    /**
     * @param string|array<int, string>|null $raw
     * @return array<int, string>
     */
    private function normalizeRawCategories(string|array|null $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $values = [];
        if (is_string($raw)) {
            if (str_contains($raw, '|')) {
                $values = explode('|', $raw);
            } elseif (str_contains($raw, ',')) {
                $values = explode(',', $raw);
            } else {
                $values = [$raw];
            }
        } else {
            $values = $raw;
        }

        $normalized = [];
        foreach ($values as $value) {
            $trimmed = trim((string)$value);
            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $rawMapping
     * @return array<string, string>
     */
    private function normalizeMapping(mixed $rawMapping): array
    {
        if (!is_array($rawMapping)) {
            return [];
        }

        $result = [];
        foreach ($rawMapping as $map) {
            if (!is_array($map)) {
                continue;
            }
            $source = trim((string)($map['source'] ?? ''));
            $target = trim((string)($map['target'] ?? ''));
            if ($source === '' || $target === '') {
                continue;
            }

            $result[mb_strtolower($source)] = $target;
        }

        return $result;
    }
}
