<?php

namespace mbootsman\Remindme;

use GuzzleHttp\Client;

final class MastodonHttp {
    private Client $http;

    public function __construct(private Config $cfg) {
        $this->http = new Client([
            "base_uri" => $this->cfg->baseUrl,
            "timeout" => 20
        ]);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function getMentionNotifications(?string $sinceId = null, int $limit = 40): array {
        $query = [
            "limit" => $limit,
            "types[]" => ["mention"]
        ];
        if ($sinceId) {
            $query["since_id"] = $sinceId;
        }

        $res = $this->http->get("/api/v1/notifications", [
            "headers" => [
                "Authorization" => "Bearer " . $this->cfg->accessToken
            ],
            "query" => $query
        ]);

        return json_decode((string)$res->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Fetch a single status by ID. Returns null if the status cannot be retrieved.
     *
     * @return array<string,mixed>|null
     */
    public function getStatus(string $id): ?array {
        try {
            $res = $this->http->get("/api/v1/statuses/{$id}", [
                "headers" => ["Authorization" => "Bearer " . $this->cfg->accessToken]
            ]);
            return json_decode((string)$res->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
    }

    public function postStatus(string $status, string $visibility = "direct", ?string $inReplyToId = null): void {
        $form = [
            "status" => $status,
            "visibility" => $visibility
        ];
        if ($inReplyToId) {
            $form["in_reply_to_id"] = $inReplyToId;
        }

        $this->http->post("/api/v1/statuses", [
            "headers" => [
                "Authorization" => "Bearer " . $this->cfg->accessToken
            ],
            "form_params" => $form
        ]);
    }
}
