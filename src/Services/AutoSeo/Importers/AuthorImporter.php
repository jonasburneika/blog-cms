<?php

namespace BlogCms\Services\AutoSeo\Importers;

use BlogCms\Models\AutoSeoMapping;
use BlogCms\Models\BlogAuthorProfile;
use BlogCms\Services\AutoSeo\AutoSeoClient;
use BlogCms\Services\AutoSeo\RemoteMediaDownloader;
use BlogCms\Support\LocaleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Imports AutoSEO authors into local users + BlogAuthorProfile models.
 *
 * Each AutoSEO author maps to one BlogAuthorProfile backed by an app user. The
 * user is resolved/created by a deterministic email ({slug}@{author_email_domain})
 * so re-imports reuse the same account. Avatars are pulled through the media
 * downloader after the profile is saved.
 */
class AuthorImporter
{
    protected RemoteMediaDownloader $media;

    public function __construct(protected AutoSeoClient $client)
    {
        $this->media = new RemoteMediaDownloader($client);
    }

    /**
     * @param  callable  $log  fn(string $message): void
     * @return array{created:int,updated:int,skipped:int,failed:int}
     */
    public function import(bool $dryRun, bool $force, callable $log): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        $rows = $this->client->authors();
        $default = LocaleManager::default();

        foreach ($rows as $row) {
            $remoteId = (int) ($row['id'] ?? 0);
            if ($remoteId <= 0) {
                $stats['failed']++;

                continue;
            }

            $hash = sha1((string) json_encode($row));
            $mapping = AutoSeoMapping::forRemote(AutoSeoMapping::TYPE_AUTHOR, $remoteId);
            $existing = $mapping ? BlogAuthorProfile::find($mapping->local_id) : null;

            // Skip only when the local profile still exists — a locally
            // deleted one must be re-imported even when upstream is unchanged.
            if ($mapping && $existing && $mapping->payload_hash === $hash && ! $force) {
                $stats['skipped']++;
                $log("Author #{$remoteId} \"".($row['name'] ?? '')."\" unchanged — skipped");

                continue;
            }

            $isUpdate = $existing !== null;

            if ($dryRun) {
                $stats[$isUpdate ? 'updated' : 'created']++;
                $log(($isUpdate ? 'Would update' : 'Would create')." author #{$remoteId} \"".($row['name'] ?? '')."\"");

                continue;
            }

            try {
                DB::transaction(function () use ($row, $remoteId, $hash, $default, $existing, $log, &$stats) {
                    $profile = $existing ?? new BlogAuthorProfile;

                    $created = ! $profile->exists;

                    $name = (string) ($row['name'] ?? '');
                    $slug = (string) ($row['slug'] ?? Str::slug($name)) ?: 'author-'.$remoteId;

                    $user = $this->resolveUser($profile, $slug, $name);

                    LocaleManager::withLocale($default, function () use ($profile, $user, $row, $slug, $name) {
                        $profile->user_id = $user->getKey();
                        $profile->slug = $slug;
                        $profile->display_name = $name;
                        $profile->role = $row['job_title'] ?? null;
                        $profile->bio = (string) ($row['bio'] ?? '');
                        $profile->social_links = $this->mapSocialLinks($row['same_as'] ?? []);
                        $profile->knows_about = array_values(array_filter((array) ($row['knows_about'] ?? [])));
                        $profile->credentials = $this->mapCredentials($row['qualifications'] ?? []);
                        $profile->is_guest = false;
                        $profile->is_indexable = ($row['status'] ?? null) === 'active';
                        $profile->save();
                    });

                    // A failed avatar download withholds the payload hash so
                    // the next sync retries it instead of skipping the author.
                    $avatarOk = true;
                    if (! empty($row['avatar_media_id'])) {
                        $avatarOk = $this->media->attach($profile, 'avatar', (int) $row['avatar_media_id'], false) !== null;
                    }

                    AutoSeoMapping::record(AutoSeoMapping::TYPE_AUTHOR, $remoteId, $profile, $avatarOk ? $hash : null);

                    $stats[$created ? 'created' : 'updated']++;
                    $log(($created ? 'Created' : 'Updated')." author #{$remoteId} → profile #{$profile->id} \"{$name}\"");
                });
            } catch (\Throwable $e) {
                $stats['failed']++;
                $log("Author #{$remoteId} failed: ".$e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Resolve the backing user for a profile: the profile's existing user, else
     * an existing user with the deterministic email, else a fresh locked user.
     * The user's name is always synced to the author name; email/password are
     * left untouched once set.
     */
    protected function resolveUser(BlogAuthorProfile $profile, string $slug, string $name): mixed
    {
        $userClass = config('blog-cms.models.user');
        $email = $slug.'@'.trim((string) config('blog-cms.autoseo.author_email_domain', 'autoseo.import'), '@');

        $user = null;
        if ($profile->exists && $profile->user_id) {
            $user = $userClass::find($profile->user_id);
        }
        $user ??= $userClass::where('email', $email)->first();

        if (! $user) {
            $user = new $userClass;
            $user->email = $email;
            $user->password = Hash::make(Str::random(40));
        }

        $user->name = $name;
        $user->save();

        return $user;
    }

    /**
     * Map AutoSEO same_as profile URLs to the profile's social_links shape
     * [{label, url}], labelling each with its host.
     *
     * @param  array<int,mixed>  $sameAs
     * @return array<int,array{label:string,url:string}>
     */
    protected function mapSocialLinks(array $sameAs): array
    {
        $links = [];
        foreach ($sameAs as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }
            $host = parse_url($url, PHP_URL_HOST);
            $label = $host ? preg_replace('/^www\./', '', $host) : $url;
            $links[] = ['label' => (string) $label, 'url' => $url];
        }

        return $links;
    }

    /**
     * Map AutoSEO qualifications to the profile's credentials shape
     * [{name, category}], category coming from the qualification type.
     *
     * @param  array<int,array<string,mixed>>  $qualifications
     * @return array<int,array{name:string,category:?string}>
     */
    protected function mapCredentials(array $qualifications): array
    {
        $credentials = [];
        foreach ($qualifications as $q) {
            $name = trim((string) ($q['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $credentials[] = [
                'name' => $name,
                'category' => trim((string) ($q['type'] ?? '')) ?: null,
            ];
        }

        return $credentials;
    }
}
