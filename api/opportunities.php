<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gmail.php';
require_once __DIR__ . '/../includes/classifier.php';

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
FILE 11: api/opportunities.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $opportunities = getOpportunitiesByUser($userId);

    echo json_encode([
        'success' => true,
        'opportunities' => $opportunities
    ]);
} catch (Exception $e) {
    error_log('Error fetching opportunities: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch opportunities']);
}
FILE 12: index.php
<?php
require_once __DIR__ . '/config/config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpportunityOS - AI-Powered Email Opportunity Scanner</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav class="nav">
                <div class="logo">OpportunityOS</div>
                <a href="auth/login.php" class="btn btn-primary">Get Started</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-top: 2rem;">
                    <?php
                    switch ($error) {
                        case 'no_code':
                            echo 'Authentication failed: No authorization code received.';
                            break;
                        case 'auth_failed':
                            echo 'Authentication failed. Please try again.';
                            break;
                        default:
                            echo 'An error occurred. Please try again.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <section class="hero">
                <h1>OpportunityOS</h1>
                <p>AI-Powered Email Opportunity Scanner</p>
                <p style="max-width: 600px; margin: 0 auto 2rem; font-size: 1rem;">
                    Automatically discover job opportunities, funding announcements, and consulting leads
                    from your Gmail inbox using advanced AI classification.
                </p>
                <a href="auth/login.php" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                    Connect with Google
                </a>
            </section>

            <section class="features">
                <div class="feature">
                    <h3>🔍 Smart Scanning</h3>
                    <p>Scan your last 50 emails in seconds and discover hidden opportunities.</p>
                </div>
                <div class="feature">
                    <h3>🤖 AI Classification</h3>
                    <p>Powered by Claude AI to intelligently categorize emails with relevance scoring.</p>
                </div>
                <div class="feature">
                    <h3>💼 Three Categories</h3>
                    <p>Job opportunities, funding news, and consulting leads - all in one place.</p>
                </div>
            </section>

            <section style="margin: 4rem 0; padding: 3rem; background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="text-align: center; margin-bottom: 2rem; font-size: 2rem;">How It Works</h2>
                <ol style="max-width: 600px; margin: 0 auto; font-size: 1.125rem; line-height: 2;">
                    <li><strong>Connect Gmail:</strong> Securely authenticate with Google OAuth</li>
                    <li><strong>Scan Emails:</strong> Click one button to analyze your inbox</li>
                    <li><strong>View Results:</strong> See classified opportunities with relevance scores</li>
                    <li><strong>Take Action:</strong> Never miss an important opportunity again</li>
                </ol>
            </section>
        </div>
    </main>

    <footer style="text-align: center; padding: 2rem; color: #6b7280;">
        <p>&copy; <?= date('Y') ?> OpportunityOS. Powered by Claude AI & Gmail.</p>
    </footer>
</body>
</html>
