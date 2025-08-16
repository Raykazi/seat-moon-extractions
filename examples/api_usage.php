<?php

/**
 * Example usage of the SeAT Moon Extractions Plugin API
 * 
 * This file demonstrates how to interact with the moon extractions API
 * from external applications or scripts.
 */

class MoonExtractionsApiExample
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct(string $baseUrl, string $apiToken)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = $apiToken;
    }

    /**
     * Get all active moon extractions
     */
    public function getAllActiveExtractions(): array
    {
        return $this->makeRequest('GET', '/api/v1/moon-extractions/', [
            'status' => 'active',
            'per_page' => 100
        ]);
    }

    /**
     * Get extractions for a specific corporation
     */
    public function getCorporationExtractions(int $corporationId): array
    {
        return $this->makeRequest('GET', "/api/v1/moon-extractions/corporation/{$corporationId}");
    }

    /**
     * Get upcoming extractions in the next 48 hours
     */
    public function getUpcomingExtractions(int $hours = 48): array
    {
        return $this->makeRequest('GET', '/api/v1/moon-extractions/upcoming', [
            'hours' => $hours
        ]);
    }

    /**
     * Get extractions in a specific system
     */
    public function getSystemExtractions(int $systemId): array
    {
        return $this->makeRequest('GET', "/api/v1/moon-extractions/system/{$systemId}");
    }

    /**
     * Get extraction statistics
     */
    public function getStatistics(?int $corporationId = null): array
    {
        $params = [];
        if ($corporationId) {
            $params['corporation_id'] = $corporationId;
        }

        return $this->makeRequest('GET', '/api/v1/moon-extractions/statistics', $params);
    }

    /**
     * Make HTTP request to the API
     */
    private function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params) && $method === 'GET') {
            $url .= '?' . http_build_query($params);
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method !== 'GET' && !empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }

        if ($httpCode >= 400) {
            throw new Exception("HTTP Error {$httpCode}: {$response}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return $data;
    }
}

// Usage example:
try {
    $api = new MoonExtractionsApiExample(
        'https://seat.your-alliance.com',  // Your SeAT installation URL
        'your-api-token-here'              // Your API token
    );

    // Get all active extractions
    echo "=== Active Moon Extractions ===\n";
    $active = $api->getAllActiveExtractions();
    foreach ($active['data'] as $extraction) {
        echo sprintf(
            "Structure: %s | Corporation: %s | System: %s | Arrives: %s\n",
            $extraction['structure_name'],
            $extraction['corporation']['name'],
            $extraction['location']['system']['name'],
            $extraction['extraction']['chunk_arrival_time']
        );
    }

    // Get upcoming extractions
    echo "\n=== Upcoming Extractions (Next 24h) ===\n";
    $upcoming = $api->getUpcomingExtractions(24);
    echo "Found {$upcoming['meta']['total']} upcoming extractions\n";

    // Get statistics
    echo "\n=== Statistics ===\n";
    $stats = $api->getStatistics();
    foreach ($stats['data'] as $key => $value) {
        echo ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
