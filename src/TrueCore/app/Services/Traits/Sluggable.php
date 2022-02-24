<?php

namespace TrueCore\App\Services\Traits;

use Illuminate\Support\Str;

trait Sluggable
{
    /**
     * @param array $data
     * @param string|null $slug
     * @return string
     * @throws \Exception
     */
    protected static function generateSlug(array $data, ?string $slug): string
    {
        $generatedSlug = '';

        $id = ((array_key_exists('id', $data) && (is_string($data['id']) || is_int($data['id']))) ? $data['id'] : null);

        $primaryDescription = ((array_key_exists('descriptions', $data) && is_array($data['descriptions'])) ? reset($data['descriptions']) : []);

        if ($slug) {
            $generatedSlug .= Str::slug($slug);
        } else {
            $generatedSlug .= ((array_key_exists('name', $primaryDescription) && (is_string($primaryDescription['name']) || is_int($primaryDescription['name']))) ? Str::slug($primaryDescription['name']) : $id);
        }

        $conditions = ['slug' => $generatedSlug];

        if ($id) {
            $conditions['id'] = ['<>', $id];
        }

        $entry = static::getOne(['slug' => $generatedSlug, 'id' => ['<>', $id]]);

        while ($entry !== null) {
            $generatedSlug .= '--' . ($id ?? strtolower(Str::random(2)));
            $entry = static::getOne(['slug' => $generatedSlug, 'id' => ['<>', $id]]);
        }

        return $generatedSlug;
    }
}
