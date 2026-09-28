<?php

namespace App\Services;

final class JobSkillVocabulary
{
    public const GROUPS = [
        'c' => ['c'],
        'c++' => ['c++', 'cpp', 'cplusplus'],
        'c#' => ['c#', 'csharp'],
        'java' => ['java'],
        'python' => ['python'],
        'php' => ['php'],
        'ruby' => ['ruby'],
        'swift' => ['swift'],
        'django' => ['django'],
        'laravel' => ['laravel'],
        'springboot' => ['springboot', 'spring boot', 'spring-boot'],
        'kubernetes' => ['kubernetes', 'k8s'],
        'kafka' => ['kafka', 'apache kafka'],
        'docker' => ['docker'],
        'html' => ['html', 'html5'],
        'css' => ['css', 'css3'],
        'javascript' => ['javascript', 'java script'],
        'reactnative' => ['react native', 'react-native', 'reactnative'],
        'reactbootstrap' => ['react bootstrap', 'react-bootstrap', 'reactbootstrap'],
        'react' => ['react', 'reactjs', 'react.js', 'rect'],
        'nodejs' => ['node js', 'node.js', 'nodejs'],
        'typescript' => ['type script', 'typescript'],
    ];

    public static function terms(string $input): array
    {
        $input = mb_strtolower(trim($input));
        $aliases = [];
        foreach (self::GROUPS as $canonical => $variants) {
            foreach ($variants as $variant) $aliases[$variant] = $canonical;
        }
        uksort($aliases, fn ($a, $b) => strlen($b) <=> strlen($a));
        $pattern = '/(?<![a-z0-9+#])('.implode('|', array_map(fn ($value) => preg_quote($value, '/'), array_keys($aliases))).')(?![a-z0-9+#])/iu';
        $input = preg_replace_callback($pattern, fn ($match) => $aliases[mb_strtolower($match[0])], $input);
        $input = preg_replace('/[^\pL\pN+#.]+/u', ' ', $input);
        return array_slice(array_values(array_unique(array_filter(preg_split('/\s+/', $input),
            fn ($term) => $term !== '' && (strlen($term) > 1 || $term === 'c')))), 0, 8);
    }

    public static function variants(string $name): array
    {
        foreach (self::GROUPS as $canonical => $variants) {
            if ($canonical === mb_strtolower($name) || in_array(mb_strtolower($name), $variants, true)) return $variants;
        }
        return [mb_strtolower($name)];
    }

    public static function pattern(string $name): string
    {
        return '(^|[^a-z0-9+#])('.implode('|', array_map(fn ($value) => preg_quote($value, '~'), self::variants($name))).')($|[^a-z0-9+#])';
    }

    public static function matches(string $text, string $name): bool
    {
        return preg_match('~'.self::pattern($name).'~iu', $text) === 1;
    }

    public static function apply($query, string $input)
    {
        foreach (self::terms($input) as $term) {
            $query->where(function ($nested) use ($term) {
                if (isset(self::GROUPS[$term])) {
                    $operator = $nested->getConnection()->getDriverName() === 'pgsql' ? '~*' : 'REGEXP';
                    $pattern = self::pattern($term);
                    foreach (['title', 'description', 'requirements'] as $column) {
                        $cast = $nested->getConnection()->getDriverName() === 'mysql' ? 'CHAR' : 'TEXT';
                        $nested->orWhereRaw("LOWER(COALESCE(CAST({$column} AS {$cast}), '')) {$operator} ?", [$pattern]);
                    }
                    $nested->orWhereHas('technologies', fn ($technology) => $technology->whereIn('name', array_map('strtolower', self::variants($term)))
                        ->orWhereRaw("LOWER(name) {$operator} ?", [$pattern]));
                } else {
                    $like = '%'.$term.'%';
                    $nested->where('title', 'LIKE', $like)->orWhere('description', 'LIKE', $like)
                        ->orWhere('requirements', 'LIKE', $like)->orWhere('location', 'LIKE', $like)
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'LIKE', $like))
                        ->orWhereHas('technologies', fn ($technology) => $technology->where('name', 'LIKE', $like));
                }
            });
        }
        return $query;
    }
}
