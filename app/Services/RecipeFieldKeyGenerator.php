<?php

namespace App\Services;

use Illuminate\Support\Str;

class RecipeFieldKeyGenerator
{
    public function generate(string $label, array $usedKeys): string
    {
        $key = Str::of($label)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($key === '') {
            $key = 'campo';
        }

        if (ctype_digit($key[0])) {
            $key = 'campo_'.$key;
        }

        $key = substr($key, 0, 50);
        $candidate = $key;
        $suffix = 2;

        while (in_array($candidate, $usedKeys, true)) {
            $suffixText = '_'.$suffix++;
            $candidate = substr($key, 0, 50 - strlen($suffixText)).$suffixText;
        }

        return $candidate;
    }
}
