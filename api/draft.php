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
$tone = $_GET['tone'] ?? 'warm';

if (!$opportunityId) {
    http_response_code(400);
    echo json_encode(['error' => 'Opportunity ID required']);
    exit;
}

// Validate tone - 3 personality styles
$validTones = ['direct', 'warm', 'formal'];
if (!in_array($tone, $validTones)) {
    $tone = 'warm';
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

    // Build the prompt
    $prompt = "You're writing an outreach email to an actual person. This needs to sound like it came from an executive search consultant - not an AI.

**THEIR EMAIL:**
From: {$opportunity['sender']}
Subject: {$opportunity['email_subject']}
Preview: {$opportunity['email_snippet']}
Company: {$opportunity['company_name']}
Type: {$opportunity['classification']}

{$toneInstructions}

**CRITICAL - AVOID THESE AI TELLS:**
❌ \"I hope this email finds you well\"
❌ \"I wanted to reach out\"
❌ \"I'd love to connect\"
❌ \"I came across your...\"
❌ Long formulaic intros
❌ Bullet points or structured formatting
❌ Perfect grammar (occasional fragments are fine)
❌ Overly enthusiastic tone

**DO THIS INSTEAD:**
✓ Start with a question, specific reference, or direct statement
✓ Use natural transitions (\"Actually,\" \"Quick thing,\" \"So,\" \"Anyway\")
✓ Reference something specific from THEIR email
✓ Keep it conversational - like you're typing quickly between meetings
✓ 2-3 short paragraphs MAX (aim for 80-120 words total)
✓ One clear ask at the end
✓ Subject line should be short and natural (4-7 words)

**STRUCTURE VARIATION (pick one randomly):**
- Start with a question about something specific they mentioned
- Lead with a quick observation about their company/space
- Open with a direct statement of why you're reaching out
- Reference a mutual connection or context

Write like a busy consultant who types fast and gets to the point.

Return ONLY valid JSON:
{
  \"subject\": \"short natural subject line\",
  \"body\": \"email body as plain text with \\n for line breaks\"
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
        'direct' => "**PERSONALITY: DIRECT/BRIEF (Sam Altman style)**

You text like Sam Altman tweets. Super short. No fluff.

Examples of this voice:
- \"Saw your Series A note. We've placed 3 CTOs in similar stage companies. Worth a call?\"
- \"Quick Q on the AI transformation project - is this already staffed?\"
- \"Your funding announcement caught my eye. Scaling eng teams?\"

Rules for this style:
- 50-80 words MAX
- Sometimes single sentence paragraphs
- Use fragments. Totally fine.
- Skip pleasantries entirely
- One ask, super clear
- Subject: 3-5 words max (\"Quick Q\" \"Worth a call?\" \"re: your Series A\")
- Sound like you're texting, not writing",

        'warm' => "**PERSONALITY: WARM/CONSULTATIVE**

You build rapport but stay professional. Reference shared context, mutual interests, or specific details that show you read their email.

Examples of this voice:
- \"I noticed you mentioned the CRM overhaul in your note. We did something similar with Acme Corp last year and learned some things the hard way. Happy to share what worked if you're still figuring out the approach.\"
- \"Congrats on the Series B. Scaling from 20 to 100 engineers is a specific kind of chaos - I've helped a few companies navigate it. Worth comparing notes?\"

Rules for this style:
- 90-120 words
- Reference something SPECIFIC from their email
- Use their name naturally (not in a salutation)
- Add a small human detail or observation
- Mention relevant experience without bragging
- Sound helpful, not sales-y
- Subject: conversational, 5-7 words (\"Following up on your CRM project\" \"re: scaling your eng team\")
- Like you're writing to someone you met at a conference",

        'formal' => "**PERSONALITY: PROFESSIONAL/FORMAL**

Traditional executive search consultant. Professional but not stuffy. You respect their time and get to business.

Examples of this voice:
- \"I'm reaching out regarding your recent posting for a VP of Engineering. My firm specializes in placing technical executives at growth-stage SaaS companies. I have two qualified candidates currently in process who align well with the requirements outlined in your posting. Would you be open to a brief conversation this week?\"

Rules for this style:
- 100-130 words
- Use full sentences, proper grammar
- Reference their company name
- Establish credibility quickly
- Specific value proposition
- Formal but not robotic
- Clear call to action with timeframe
- Subject: professional, 5-7 words (\"Regarding VP Engineering search\" \"Executive placement inquiry\")
- Like a well-written business email, not a form letter"
    ];

    return $instructions[$tone] ?? $instructions['warm'];
}
