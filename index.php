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