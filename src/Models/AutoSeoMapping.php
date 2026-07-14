<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Maps a remote AutoSEO entity (post/category/author/media) to the local model
 * it was imported into. Keyed by (remote_type, remote_id); the importer calls
 * record() to upsert and forRemote()/localId() to resolve on re-runs.
 */
class AutoSeoMapping extends Model
{
    public const TYPE_POST = 'post';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_AUTHOR = 'author';
    public const TYPE_MEDIA = 'media';

    protected $table = 'blog_autoseo_map';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    /** The local model this remote entity was imported into. */
    public function local(): MorphTo
    {
        return $this->morphTo('local', 'local_type', 'local_id');
    }

    /** Look up the mapping for a remote entity, if it has been imported. */
    public static function forRemote(string $remoteType, int $remoteId): ?self
    {
        return static::query()
            ->where('remote_type', $remoteType)
            ->where('remote_id', $remoteId)
            ->first();
    }

    /** The local model's primary key for a remote entity, if mapped. */
    public static function localId(string $remoteType, int $remoteId): ?int
    {
        return static::forRemote($remoteType, $remoteId)?->local_id;
    }

    /** Upsert the mapping for a remote entity onto a freshly imported local model. */
    public static function record(
        string $remoteType,
        int $remoteId,
        \Illuminate\Database\Eloquent\Model $local,
        ?string $payloadHash = null
    ): self {
        return static::updateOrCreate(
            ['remote_type' => $remoteType, 'remote_id' => $remoteId],
            [
                'local_type' => get_class($local),
                'local_id' => $local->getKey(),
                'payload_hash' => $payloadHash,
                'synced_at' => now(),
            ]
        );
    }
}
