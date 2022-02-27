<?php

namespace App\WebDav;

use Illuminate\Support\Facades\DB;
use Sabre\DAV;
use Sabre\DAV\Locks\LockInfo;

/**
 * Modified locks backend class for Sabre\DAV
 */
class Locks extends DAV\Locks\Backend\AbstractBackend {
    private static function getTable() {
        return DB::table("webdav_locks");
    }

    public function getLocks($uri, $returnChildLocks) {
        $query = self::getTable()
            ->whereRaw("(created > (? - timeout))", [time()])
            ->where(function ($query) use ($uri, $returnChildLocks) {
                $query->orWhereRaw("(uri = ?)", [$uri]);

                // We need to check locks for every part in the uri.
                $uriParts = explode("/", $uri);

                // We already covered the last part of the uri
                array_pop($uriParts);

                $currentPath = "";

                foreach ($uriParts as $part) {
                    if ($currentPath) {
                        $currentPath .= "/";
                    }

                    $currentPath .= $part;

                    $query = $query->whereRaw(
                        "(depth != 0 AND uri = ?)",
                        [$currentPath],
                        "or"
                    );
                }

                if ($returnChildLocks) {
                    $query = $query->whereRaw(
                        "(uri LIKE ?)",
                        [$uri . "/%"],
                        "or"
                    );
                }
            });

        $lockList = [];

        foreach ($query->get() as $row) {
            $lockInfo = new LockInfo();

            $lockInfo->owner = $row->owner;
            $lockInfo->token = $row->token;
            $lockInfo->timeout = intval($row->timeout);
            $lockInfo->created = intval($row->created);
            $lockInfo->scope = intval($row->scope);
            $lockInfo->depth = intval($row->depth);
            $lockInfo->uri = $row->uri;

            $lockList[] = $lockInfo;
        }

        return $lockList;
    }

    public function lock($uri, LockInfo $lockInfo) {
        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 30 * 60;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        self::getTable()->upsert(
            [
                "owner" => $lockInfo->owner,
                "timeout" => $lockInfo->timeout,
                "scope" => $lockInfo->scope,
                "depth" => $lockInfo->depth,
                "uri" => $uri,
                "created" => $lockInfo->created,
                "token" => $lockInfo->token,
            ],
            "token"
        );

        return true;
    }

    public function unlock($uri, LockInfo $lockInfo) {
        return 1 ===
            self::getTable()
                ->where("uri", $uri)
                ->where("token", $lockInfo->token)
                ->delete();
    }
}
