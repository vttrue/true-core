<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\{
    Builder,
    Model
};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
/**
 * TrueCore\App\Models\System\ImagePreview
 *
 * @property string $entity_namespace
 * @property int $entity_id
 * @property string $image_path
 * @property array $preview_list
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|null $entity
 * @method static Builder|ImagePreview whereEntityNamespace($value)
 * @method static Builder|ImagePreview whereEntityId($value)
 * @method static Builder|ImagePreview whereImagePath($value)
 * @method static Builder|ImagePreview wherePreviewList($value)
 * @method static Builder|ImagePreview whereCreatedAt($value)
 * @method static Builder|ImagePreview whereUpdatedAt($value)
 * @method static Builder|ImagePreview newModelQuery()
 * @method static Builder|ImagePreview newQuery()
 * @method static Builder|ImagePreview query()
 * @mixin Builder
 */
class ImagePreview extends Model
{
    protected $casts = [
        'entity_id'     => 'integer',
        'preview_list'  => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime'
    ];

    protected $fillable = [
        'entity_namespace',
        'entity_id',
        'image_path',
        'preview_list'
    ];

    public $incrementing = false;

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query
            ->where('entity_namespace', '=', $this->entity_namespace)
            ->where('entity_id', '=', $this->entity_id)
            ->where('image_path', '=', $this->image_path);
        return $query;
    }

    protected static function boot()
    {
        parent::boot();

        self::saved(function (ImagePreview $model) {
            $imagePrefix = config('filesystems.disks.image.imagePrefix');

            $originalPreviewList = $model->getOriginal('preview_list');
            $originalPreviewList = ((is_array($originalPreviewList) === true) ? $originalPreviewList : []);

            $needsInvalidation = false;

            $previewList = ((is_array($model->preview_list) === true) ? $model->preview_list : []);

            foreach ($previewList as $previewInd => $previewEntry) {

                if (array_key_exists($previewInd, $originalPreviewList) === true) {

                    if ($originalPreviewList[$previewInd]['previewPath'] !== $previewEntry['previewPath']) {

                        $needsInvalidation = true;

                        $params = ((is_array($previewEntry['params']) === true) ? $previewEntry['params'] : []);

                        $mayBeRemoved = (array_key_exists('mayBeRemoved', $params) === false || $params['mayBeRemoved'] === true);

                        if ($mayBeRemoved === true) {

                            $previewEntryWithNoPrefix = str_replace($imagePrefix, '', $originalPreviewList[$previewInd]['previewPath']);
                            if (Storage::disk('image')->exists($previewEntryWithNoPrefix) === true) {
                                Storage::disk('image')->delete($previewEntryWithNoPrefix);
                                if (Storage::disk('image')->exists($previewEntryWithNoPrefix . '.webp')) {
                                    Storage::disk('image')->delete($previewEntryWithNoPrefix . '.webp');
                                }
                            }

                        }

                    }
                } else {
                    $needsInvalidation = true;
                }

            }

            if ($needsInvalidation === true) {
                //Artisan::call('cache:clear');
            }

        });

        self::deleted(function (ImagePreview $model) {

            $imagePrefix = config('filesystems.disks.image.imagePrefix');
            $previewList = ((is_array($model->preview_list) === true) ? $model->preview_list : []);

            foreach ($previewList AS $previewEntry) {

                $params = ((is_array($previewEntry['params']) === true) ? $previewEntry['params'] : []);

                $mayBeRemoved = (array_key_exists('mayBeRemoved', $params) === false || $params['mayBeRemoved'] === true);

                if ($mayBeRemoved === true) {
                    $previewEntryWithNoPrefix = str_replace($imagePrefix, '', $previewEntry['previewPath']);

                    if (Storage::disk('image')->exists($previewEntryWithNoPrefix) === true) {
                        Storage::disk('image')->delete($previewEntryWithNoPrefix);
                        if (Storage::disk('image')->exists($previewEntryWithNoPrefix . '.webp')) {
                            Storage::disk('image')->delete($previewEntryWithNoPrefix . '.webp');
                        }
                    }

                }
            }

            //Artisan::call('cache:clear');

        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity()
    {
        return $this->belongsTo($this->entity_namespace, 'entity_id', 'id');
    }
}
