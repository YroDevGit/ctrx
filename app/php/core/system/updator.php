<?php

/**
 * GitHub Tag Comparison Class
 *
 * A reusable helper for fetching tags and comparing file changes
 * between tags on a GitHub repository via the GitHub REST API.
 *
 * Usage:
 *   $files = GitHubTagComparer::getChangedFiles('owner', 'repo', 'v1.0', 'v1.1');
 */
class CtrxUpdater
{
    /**
     * @var string|null Optional GitHub personal access token for higher rate limits.
     */
    private static ?string $token = null;

    /**
     * @var int Timeout in seconds for each API request.
     */
    private static int $timeout = 30;

    /**
     * @var string User-Agent string sent with every request.
     */
    private static string $userAgent = 'PHP-GitHubTagComparer';

    // ============================================================
    // Configuration
    // ============================================================

    /**
     * Sets a GitHub personal access token for authenticated requests.
     * Authenticated requests allow 5,000 req/hour instead of 60 req/hour.
     *
     * @param string $token Your GitHub personal access token.
     * @return void
     */
    public static function setToken(string $token): void
    {
        self::$token = $token;
    }

    /**
     * Sets the cURL timeout in seconds.
     *
     * @param int $seconds Timeout in seconds.
     * @return void
     */
    public static function setTimeout(int $seconds): void
    {
        self::$timeout = $seconds;
    }

    /**
     * Sets the User-Agent string for API requests.
     *
     * @param string $userAgent The user agent string.
     * @return void
     */
    public static function setUserAgent(string $userAgent): void
    {
        self::$userAgent = $userAgent;
    }

    // ============================================================
    // Core API Helper
    // ============================================================

    /**
     * Makes a GET request to the GitHub API and returns the decoded JSON.
     *
     * @param string $url The full API URL to request.
     * @return array|null The decoded JSON response, or null on failure.
     */
    public static function request(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);

        if (self::$token !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: token ' . self::$token,
                'Accept: application/vnd.github+json',
            ]);
        }

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            error_log("cURL error: " . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            error_log("GitHub API request failed with code {$httpCode} for URL: {$url}");
            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }

    // ============================================================
    // Tag Retrieval
    // ============================================================

    /**
     * Fetches all tags from a GitHub repository.
     *
     * @param string $owner The repository owner.
     * @param string $repo  The repository name.
     * @return array An array of tag objects, or an empty array on failure.
     */
    public static function getAllTags(string $owner, string $repo): array
    {
        $url  = "https://api.github.com/repos/{$owner}/{$repo}/tags?per_page=100";
        $data = self::request($url);

        return is_array($data) ? $data : [];
    }

    /**
     * Fetches the latest and previous tags from a GitHub repository.
     * Tags are ordered by the date they were created (newest first).
     *
     * @param string $owner The repository owner.
     * @param string $repo  The repository name.
     * @return array An array with 'latest' and 'previous' keys (values may be null).
     */
    public static function getLatestAndPreviousTags(string $owner, string $repo): array
    {
        $url  = "https://api.github.com/repos/{$owner}/{$repo}/tags?per_page=2";
        $data = self::request($url);

        if (!is_array($data)) {
            return ['latest' => null, 'previous' => null];
        }

        return [
            'latest'   => $data[0]['name'] ?? null,
            'previous' => $data[1]['name'] ?? null,
        ];
    }

    /**
     * Fetches only the latest tag from a GitHub repository.
     *
     * @param string $owner The repository owner.
     * @param string $repo  The repository name.
     * @return string|null The latest tag name, or null on failure.
     */
    public static function getLatestTag(string $owner, string $repo): ?string
    {
        $tags = self::getLatestAndPreviousTags($owner, $repo);
        return $tags['latest'];
    }

    /**
     * Sorts an array of tag objects by semantic version (descending).
     *
     * @param array $tags Array of tag objects from the GitHub API.
     * @return array Sorted array of tag objects.
     */
    public static function sortTagsBySemver(array $tags): array
    {
        usort($tags, function ($a, $b) {
            return version_compare(
                ltrim($b['name'], 'vV'),
                ltrim($a['name'], 'vV')
            );
        });
        return $tags;
    }

    // ============================================================
    // File Change Comparison
    // ============================================================

    /**
     * Fetches the list of changed files between two tags.
     *
     * @param string $owner   The repository owner.
     * @param string $repo    The repository name.
     * @param string $baseTag The older tag to compare from.
     * @param string $headTag The newer tag to compare to.
     * @return array An array of file change objects, or an empty array on failure.
     */
    public static function getChangedFiles(string $owner, string $repo, string $baseTag, string $headTag): array
    {
        $url  = "https://api.github.com/repos/{$owner}/{$repo}/compare/{$baseTag}...{$headTag}";
        $data = self::request($url);

        if (!is_array($data)) {
            return [];
        }

        return (isset($data['files']) && is_array($data['files'])) ? $data['files'] : [];
    }

    /**
     * Groups an array of changed files by their change status.
     *
     * @param array $files Array of file change objects.
     * @return array An array keyed by status (added, modified, removed, renamed, etc.).
     */
    public static function groupFilesByStatus(array $files): array
    {
        $grouped = [];
        foreach ($files as $file) {
            $status = $file['status'] ?? 'unknown';
            $grouped[$status][] = $file;
        }
        return $grouped;
    }

    /**
     * Returns a summary count of changes grouped by status.
     *
     * @param array $files Array of file change objects.
     * @return array An array with status as key and count as value.
     */
    public static function summarizeByStatus(array $files): array
    {
        $summary = [];
        foreach ($files as $file) {
            $status = $file['status'] ?? 'unknown';
            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }
        return $summary;
    }

    /**
     * Compares the latest tag against the previous tag automatically.
     *
     * @param string $owner The repository owner.
     * @param string $repo  The repository name.
     * @return array An array with 'base', 'head', and 'files' keys.
     */
    public static function compareLatestTags(string $owner, string $repo): array
    {
        $tags = self::getLatestAndPreviousTags($owner, $repo);

        if ($tags['latest'] === null || $tags['previous'] === null) {
            return ['base' => null, 'head' => null, 'files' => []];
        }

        return [
            'base'  => $tags['previous'],
            'head'  => $tags['latest'],
            'files' => self::getChangedFiles($owner, $repo, $tags['previous'], $tags['latest']),
        ];
    }

    /**
     * Renders a formatted text report of the file changes between two tags.
     *
     * @param string      $owner   The repository owner.
     * @param string      $repo    The repository name.
     * @param string|null $baseTag The older tag. If null, uses the previous tag.
     * @param string|null $headTag The newer tag. If null, uses the latest tag.
     * @return string The formatted report as a string.
     */
    public static function renderReport(string $owner, string $repo, ?string $baseTag = null, ?string $headTag = null): string
    {
        // Auto-detect tags if not provided
        if ($baseTag === null || $headTag === null) {
            $auto = self::getLatestAndPreviousTags($owner, $repo);
            $headTag ??= $auto['latest'];
            $baseTag ??= $auto['previous'];
        }

        if ($baseTag === null || $headTag === null) {
            return "Could not determine tags to compare for {$owner}/{$repo}.\n";
        }

        $files = self::getChangedFiles($owner, $repo, $baseTag, $headTag);

        $out  = "========================================\n";
        $out .= "Comparing {$baseTag}...{$headTag}\n";
        $out .= "Repository: {$owner}/{$repo}\n";
        $out .= "========================================\n\n";

        if (empty($files)) {
            $out .= "No file changes found (or an error occurred).\n";
            return $out;
        }

        $out .= "Total files changed: " . count($files) . "\n\n";

        // Summary by status
        $out .= "--- Summary by status ---\n";
        foreach (self::summarizeByStatus($files) as $status => $count) {
            $out .= sprintf("  %-10s %d\n", $status . ':', $count);
        }
        $out .= "\n";

        // Detailed file list
        $out .= "--- Detailed file changes ---\n";
        foreach ($files as $file) {
            $status   = $file['status']   ?? 'unknown';
            $filename = $file['filename'] ?? '(unknown)';
            $adds     = $file['additions'] ?? 0;
            $dels     = $file['deletions'] ?? 0;

            $line = sprintf("[%-8s] %s (+%d/-%d)", $status, $filename, $adds, $dels);

            if (isset($file['previous_filename'])) {
                $line .= " (previously: " . $file['previous_filename'] . ")";
            }

            $out .= $line . "\n";
        }

        return $out;
    }
}