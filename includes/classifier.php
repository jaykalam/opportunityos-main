<?php
// Claude AI Classification Service

function classifyEmail($subject, $sender, $snippet = '') {
    $apiKey = ANTHROPIC_API_KEY;

    if (empty($apiKey)) {
        error_log('Anthropic API key not configured');
        return [
            'type' => 'ignore',
            'relevance_score' => 0,
            'company_name' => extractCompanyFromSender($sender)
        ];
    }

    $prompt = "Classify this email based on the subject and sender. Is it:
1. Job opportunity (keywords: hiring, role, position, career, job opening, we're looking for, join our team)
2. Funding announcement (keywords: raised, funding, series, investment, venture, seed round)
3. Consulting lead (keywords: CRM, transformation, AI project, consulting, implementation, strategy, digital transformation)
4. Ignore (everything else - newsletters, spam, general updates, etc.)

Email Details:
- Subject: {$subject}
- Sender: {$sender}
" . ($snippet ? "- Preview: {$snippet}" : "") . "

For job opportunities and consulting leads, score 1-10 based on relevance to a senior consultant/executive.
For funding announcements, score based on the size/stage of funding if visible.
For ignore category, always score 0.

Extract the company name from the sender email or subject.

Return ONLY valid JSON in this exact format:
{
  \"type\": \"job\" | \"funding\" | \"consulting\" | \"ignore\",
  \"relevance_score\": 1-10,
  \"company_name\": \"extracted company name\"
}";

    try {
        $ch = curl_init('https://api.anthropic.com/v1/messages');

        $data = [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 1024,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log('Claude API error: ' . $response);
            throw new Exception('Claude API request failed');
        }

        $result = json_decode($response, true);

        if (!isset($result['content'][0]['text'])) {
            throw new Exception('Invalid response from Claude API');
        }

        $text = $result['content'][0]['text'];

        // Extract JSON from response (handle potential markdown code blocks)
        $text = trim($text);
        $text = preg_replace('/```json?\n?/', '', $text);
        $text = preg_replace('/```\n?$/', '', $text);

        $classification = json_decode($text, true);

        if (!$classification || !isset($classification['type'])) {
            throw new Exception('Invalid classification JSON');
        }

        // Validate classification type
        $validTypes = ['job', 'funding', 'consulting', 'ignore'];
        if (!in_array($classification['type'], $validTypes)) {
            $classification['type'] = 'ignore';
        }

        // Validate relevance score
        if (!isset($classification['relevance_score']) ||
            $classification['relevance_score'] < 0 ||
            $classification['relevance_score'] > 10) {
            $classification['relevance_score'] = 0;
        }

        // Ensure company name exists
        if (!isset($classification['company_name']) || empty($classification['company_name'])) {
            $classification['company_name'] = extractCompanyFromSender($sender);
        }

        return $classification;
    } catch (Exception $e) {
        error_log('Classification error: ' . $e->getMessage());
        // Fallback to ignore category on error
        return [
            'type' => 'ignore',
            'relevance_score' => 0,
            'company_name' => extractCompanyFromSender($sender)
        ];
    }
}

function extractCompanyFromSender($sender) {
    // Extract domain from email
    if (preg_match('/<(.+@(.+))>/', $sender, $matches)) {
        $domain = $matches[2];
    } elseif (preg_match('/@(.+)$/', $sender, $matches)) {
        $domain = $matches[1];
    } else {
        return 'Unknown';
    }

    // Clean up domain
    $domain = strtolower($domain);
    $domain = preg_replace('/\.(com|net|org|io|co)$/', '', $domain);
    return ucfirst($domain);
}
