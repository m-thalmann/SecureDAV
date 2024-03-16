<?php

namespace App\WebDav;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Sabre\DAV;
use Sabre\DAV\Locks\LockInfo;
use stdClass;

/**
 * WebDAV locks backend for Laravel
 */
class LocksBackend extends DAV\Locks\Backend\AbstractBackend {
    public const TABLE_NAME = 'webdav_locks';
    public const TIMEOUT = 30 * 60;

    public function __construct(protected AuthBackend $authBackend) {
    }

    public function getLocks(mixed $uri, mixed $returnChildLocks): array {
        $query = $this->query()
            ->whereRaw('((created + timeout) > ?)', [time()])
            ->where('user_id', $this->authBackend->getAuthenticatedUser()->id)
            ->where(function (Builder $query) use ($uri, $returnChildLocks) {
                $query->where('uri', $uri);

                // we need to check locks for every part in the uri (except the last one, since it has already been checked)

                $currentSeparatorIndex = strpos($uri, '/');

                while ($currentSeparatorIndex !== false) {
                    $subpath = substr($uri, 0, $currentSeparatorIndex);

                    $currentSeparatorIndex = strpos(
                        $uri,
                        '/',
                        $currentSeparatorIndex + 1
                    );

                    $query->orWhere(function (Builder $query) use ($subpath) {
                        $query->where('depth', '!=', 0)->where('uri', $subpath);
                    });
                }

                if ($returnChildLocks) {
                    $query->orWhere('uri', 'LIKE', "$uri/%");
                }
            });

        return $query
            ->get()
            ->map(function (stdClass $lockRow) {
                $lockInfo = new LockInfo();

                $lockInfo->owner = $lockRow->owner;
                $lockInfo->token = $lockRow->token;
                $lockInfo->timeout = intval($lockRow->timeout);
                $lockInfo->created = intval($lockRow->created);
                $lockInfo->scope = intval($lockRow->scope);
                $lockInfo->depth = intval($lockRow->depth);
                $lockInfo->uri = $lockRow->uri;

                return $lockInfo;
            })
            ->all();
    }

    public function lock(mixed $uri, LockInfo $lockInfo): bool {
        $timeout = static::TIMEOUT; // overwrite timeout
        $created = time();

        return $this->query()->upsert(
            [
                'user_id' => $this->authBackend->getAuthenticatedUser()->id,
                'owner' => $lockInfo->owner,
                'timeout' => $timeout,
                'scope' => $lockInfo->scope,
                'depth' => $lockInfo->depth,
                'uri' => $uri,
                'created' => $created,
                'token' => $lockInfo->token,
            ],
            ['user_id', 'token']
        ) > 0;
    }

    public function unlock(mixed $uri, LockInfo $lockInfo): bool {
        return $this->query()
            ->where('uri', $uri)
            ->where('token', $lockInfo->token)
            ->where('user_id', $this->authBackend->getAuthenticatedUser()->id)
            ->delete() === 1;
    }

    protected function query(): Builder {
        return DB::table(static::TABLE_NAME);
    }
}
