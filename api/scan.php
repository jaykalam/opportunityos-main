<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gmail.php';
require_once __DIR__ . '/../includes/classifier.php';
require_once __DIR__ . '/../includes/perplexity.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $user = getUserById($userId);

    if (!$user || empty($user['access_token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Gmail not connected']);
        exit;
    }

    // Fetch emails
    $emails = fetchRecentEmails($user['access_token'], 50);

    $results = [];
    $processed = 0;

    foreach ($emails as $email) {
        try {
            // Classify the email
            $classification = classifyEmail(
                $email['subject'],
                $email['from'],
                $email['snippet']
            );

            // Only store non-ignore classifications
            if ($classification['type'] !== 'ignore') {
                $opportunityId = insertOpportunity(
                    $userId,
                    $email['subject'],
                    $email['from'],
                    $classification['type'],
                    $classification['relevance_score'],
                    $classification['company_name'],
                    $email['date'],
                    $email['snippet']
                );

                // Enrich with company context (don't fail if this errors)
                try {
                    if (!empty($classification['company_name'])) {
                        $companyContext = searchCompanyNews(
                            $classification['company_name'],
                            $classification['type']
                        );

                        if (!empty($companyContext)) {
                            updateOpportunityContext($opportunityId, $companyContext);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Company context enrichment failed for opportunity ' . $opportunityId . ': ' . $e->getMessage());
                }

                $results[] = [
                    'id' => $opportunityId,
                    'subject' => $email['subject'],
                    'sender' => $email['from'],
                    'type' => $classification['type'],
                    'score' => $classification['relevance_score'],
                    'company' => $classification['company_name']
                ];
            }

            $processed++;
        } catch (Exception $e) {
            error_log('Error processing email: ' . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'processed' => $processed,
        'total' => count($emails),
        'opportunities' => count($results),
        'results' => $results
    ]);
} catch (Exception $e) {
    error_log('Scan error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to scan emails: ' . $e->getMessage()]);
}
