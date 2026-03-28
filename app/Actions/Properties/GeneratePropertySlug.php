<?php

namespace App\Actions\Properties;

use App\Models\Property;
use Illuminate\Support\Str;

class GeneratePropertySlug
{
    public function handle(string $name, ?Property $ignoredProperty = null): string
    {
        $baseSlug = Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s_-]+/', '')
            ->replaceMatches('/\s+/', '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();

        $slug = $baseSlug !== '' ? $baseSlug : 'property';

        if (! $this->slugExists($slug, $ignoredProperty)) {
            return $slug;
        }

        do {
            $candidate = $slug.'_'.$this->randomAlphaSuffix(4);
        } while ($this->slugExists($candidate, $ignoredProperty));

        return $candidate;
    }

    private function slugExists(string $slug, ?Property $ignoredProperty = null): bool
    {
        $query = Property::query()->where('slug', $slug);

        if ($ignoredProperty !== null) {
            $query->whereKeyNot($ignoredProperty->getKey());
        }

        return $query->exists();
    }

    private function randomAlphaSuffix(int $length): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $suffix = '';

        for ($index = 0; $index < $length; $index++) {
            $suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $suffix;
    }
}
