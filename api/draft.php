<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get request parameters
$opportunityId = $_GET['id'] ?? null;
$tone = $_GET['tone'] ?? 'friendly';

if (!$opportunityId) {
    http_response_code(400);
    echo json_encode(['error' => 'Opportunity ID required']);
    exit;
}

// Validate tone
$validTones = ['friendly', 'contrarian', 'formal', 'consulting', 'creative', 'data_driven', 'horowitz', 'mcphee'];
if (!in_array($tone, $validTones)) {
    $tone = 'friendly';
}

try {
    // Get opportunity from database
    $db = getDatabase();
    $stmt = $db->prepare("SELECT * FROM opportunities WHERE id = ? AND user_id = ?");
    $stmt->execute([$opportunityId, $_SESSION['user_id']]);
    $opportunity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunity) {
        http_response_code(404);
        echo json_encode(['error' => 'Opportunity not found']);
        exit;
    }

    // Generate email draft using Claude API
    $draft = generateEmailDraft($opportunity, $tone);

    echo json_encode([
        'success' => true,
        'draft' => $draft
    ]);
} catch (Exception $e) {
    error_log('Draft generation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate draft: ' . $e->getMessage()]);
}

function generateEmailDraft($opportunity, $tone) {
    $apiKey = ANTHROPIC_API_KEY;

    if (empty($apiKey)) {
        throw new Exception('Anthropic API key not configured');
    }

    // Build tone-specific instructions
    $toneInstructions = getToneInstructions($tone);

    // Build context section
    $contextSection = '';
    if (!empty($opportunity['company_context'])) {
        $contextSection = "\n**Recent Context About Company:**\n{$opportunity['company_context']}\n";
    }

    // Build the prompt
    $prompt = "You are drafting an outreach email in response to this opportunity:

**Company:** {$opportunity['company_name']}
**Sender:** {$opportunity['sender']}
**Email Subject:** {$opportunity['email_subject']}
**Type:** {$opportunity['classification']}
**Email Preview:** {$opportunity['email_snippet']}
{$contextSection}
{$toneInstructions}

**Requirements:**
- Email body must be UNDER 150 words
- Include a compelling subject line
- Be authentic and professional
- Reference specific details from their email when possible" . (!empty($opportunity['company_context']) ? "\n- Reference specific recent company developments from the context to show you understand their current situation" : "") . "
- Include a clear call to action

Return ONLY valid JSON in this exact format:
{
  \"subject\": \"your subject line here\",
  \"body\": \"your email body here\"
}";

    // Call Claude API
    $ch = curl_init('https://api.anthropic.com/v1/messages');

    $data = [
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 2048,
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

    $draft = json_decode($text, true);

    if (!$draft || !isset($draft['subject']) || !isset($draft['body'])) {
        throw new Exception('Invalid draft JSON');
    }

    return $draft;
}

function getToneInstructions($tone) {
    $instructions = [
        'friendly' => "**Tone: Paul Graham/YC Style - Friendly & Conversational**
Write like you're talking to a peer over coffee. Use first names, be helpful and genuine, skip the formalities. Think: \"Hey, I saw your note and wanted to reach out...\" Keep it warm, direct, and human.",

        'contrarian' => "**Tone: Peter Thiel - Contrarian & Provocative**
Challenge assumptions with thoughtful questions. Don't just agree - offer a unique perspective they haven't considered. Make them think. Ask: \"What if you're solving the wrong problem?\" or \"Have you considered the second-order effects?\"",

        'formal' => "**Tone: Executive/Corporate - Formal & Structured**
Professional business communication. Use proper structure, formal greetings, and corporate language. Address them by title. Think quarterly reports, board presentations, and executive memos. Polished and precise.",

        'consulting' => "**Tone: McKinsey/BCG - Strategic & ROI-Focused**
Lead with business impact. Use frameworks, talk transformation, emphasize ROI and strategic value. Reference competitive advantage, market positioning, operational excellence. Quantify when possible. Think: \"This represents a 3x multiplier on your current capacity...\"",

        'creative' => "**Tone: Agency Storytelling - Creative & Human-Centered**
Tell a compelling story. Make it visual, emotional, and memorable. Focus on the human impact, the narrative arc, the \"why\" that resonates. Think: \"Imagine a world where...\" Paint pictures with words.",

        'data_driven' => "**Tone: Marc Andreessen/a16z - Data-Driven & Analytical**
Lead with insights from patterns and data. Reference trends, market dynamics, and analytical observations. Think like a VC spotting signals others miss. Use phrases like \"The data suggests...\" or \"Market indicators show...\"",

        'horowitz' => "**Tone: Ben Horowitz - War Stories & Brutal Honesty**
Get real about the hard stuff. Share hard-won wisdom, acknowledge the brutal facts, but offer operational insight. No sugarcoating. Think: \"Here's what nobody tells you...\" Be direct, authentic, and battle-tested.",

        'mcphee' => "**Tone: John McPhee - Deeply Researched Narrative Journalism**
Take a journalistic approach with rich detail and context. Weave in background, humanize the complexity, make the technical accessible. Think New Yorker long-form - deeply observed, carefully crafted, intellectually curious."
    ];

    return $instructions[$tone] ?? $instructions['friendly'];
}
