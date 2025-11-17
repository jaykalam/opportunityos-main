<?php
// Perplexity API integration for company context enrichment

/**
 * Search for recent company news using Perplexity API
 *
 * @param string $companyName The name of the company to search for
 * @param string $opportunityType The type of opportunity (funding, job, consulting)
 * @return string Company context as bullet points, or empty string on failure
 */
function searchCompanyNews($companyName, $opportunityType) {
    $apiKey = getenv('PERPLEXITY_API_KEY') ?: '';

    if (empty($apiKey)) {
        error_log('Perplexity API key not configured');
        return '';
    }

    if (empty($companyName)) {
        error_log('Company name is empty, skipping context enrichment');
        return '';
    }

    // Build search query based on opportunity type
    $query = buildSearchQuery($companyName, $opportunityType);

    // Call Perplexity API
    $ch = curl_init('https://api.perplexity.ai/chat/completions');

    $data = [
        'model' => 'llama-3.1-sonar-small-128k-online',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a research assistant helping executive search consultants find relevant, recent company news. Return 3-5 bullet points with date, headline, and brief summary. Focus on recent developments (last 6 months). Keep total response under 500 words. Format as markdown bullet points.'
            ],
            [
                'role' => 'user',
                'content' => $query
            ]
        ],
        'max_tokens' => 500,
        'temperature' => 0.2,
        'return_citations' => false,
        'return_images' => false
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Handle curl errors
    if ($curlError) {
        error_log('Perplexity API curl error: ' . $curlError);
        return '';
    }

    // Handle HTTP errors
    if ($httpCode !== 200) {
        error_log('Perplexity API error (HTTP ' . $httpCode . '): ' . $response);
        return '';
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        error_log('Invalid response from Perplexity API: ' . $response);
        return '';
    }

    $content = trim($result['choices'][0]['message']['content']);

    // Validate that we got meaningful content
    if (empty($content) || strlen($content) < 20) {
        error_log('Perplexity API returned empty or very short content');
        return '';
    }

    return $content;
}

/**
 * Build search query based on opportunity type
 *
 * @param string $companyName The company name
 * @param string $opportunityType The opportunity type
 * @return string The search query
 */
function buildSearchQuery($companyName, $opportunityType) {
    $queries = [
        'funding' => "Find recent funding announcements, investors, Series round details, and investment news for {$companyName}. Include dates and amounts if available.",
        'job' => "Find recent growth indicators, hiring announcements, team expansion, product launches, and new initiatives at {$companyName}.",
        'consulting' => "Find recent digital transformation initiatives, new technology adoption, strategic changes, and modernization efforts at {$companyName}."
    ];

    // Default to general company news if type not recognized
    $query = $queries[strtolower($opportunityType)] ??
             "Find recent news, announcements, and developments for {$companyName}.";

    return $query;
}
