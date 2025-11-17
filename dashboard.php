<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user = getUserById($_SESSION['user_id']);
$connected = isset($_GET['connected']) && $_GET['connected'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OpportunityOS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <nav class="nav">
                <div class="logo">OpportunityOS</div>
                <div class="user-profile">
                    <?php if ($user['picture']): ?>
                        <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Profile" class="user-avatar">
                    <?php endif; ?>
                    <span><?= htmlspecialchars($user['name'] ?: $user['email']) ?></span>
                    <a href="auth/logout.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="dashboard">
        <div class="container">
            <h1 style="margin-bottom: 2rem; font-size: 2rem;">OpportunityOS Dashboard</h1>
            <p style="color: #6b7280; margin-bottom: 2rem;">
                Scan your emails for job opportunities, funding news, and consulting leads
            </p>

            <div id="alerts"></div>

            <?php if ($connected): ?>
                <div class="alert alert-success">
                    Successfully connected to Gmail!
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Email Scanner</h2>
                        <p id="scan-status" style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                            Click to scan your last 50 emails for opportunities
                        </p>
                    </div>
                    <div class="card-actions">
                        <a href="auth/login.php" class="btn btn-secondary">Connect Gmail</a>
                        <button id="scan-btn" class="btn btn-primary" onclick="scanEmails()">
                            Scan My Emails
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1.5rem;">
                    <h2 class="card-title">Opportunities (<span id="opportunity-count">0</span>)</h2>
                </div>

                <div id="opportunities-container">
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p style="font-weight: 500; margin-bottom: 0.5rem;">No opportunities found</p>
                        <p style="font-size: 0.875rem;">Try scanning your emails to discover opportunities</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Draft Email Modal -->
    <div id="draft-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 8px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin: 0;">Draft Email</h2>
                <button onclick="closeDraftModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem;">
                    <label for="tone-select" style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Communication Style</label>
                    <select id="tone-select" onchange="generateDraft()" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 1rem; background: white; cursor: pointer;">
                        <option value="">Select a style...</option>
                        <option value="direct">Direct/Brief - Super short, gets to the point (Sam Altman style)</option>
                        <option value="warm">Warm/Consultative - Builds rapport, references specifics</option>
                        <option value="formal">Professional/Formal - Traditional executive search</option>
                    </select>
                </div>

                <div id="draft-loading" style="display: none; text-align: center; padding: 2rem; color: #6b7280;">
                    <div style="margin-bottom: 0.5rem;">Generating your email draft...</div>
                    <div style="font-size: 0.875rem;">This may take a few seconds</div>
                </div>

                <div id="draft-error" style="display: none; padding: 1rem; background: #fee2e2; border: 1px solid #fecaca; border-radius: 4px; color: #991b1b; margin-bottom: 1rem;"></div>

                <div id="draft-content" style="display: none;">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Subject Line</label>
                        <div id="draft-subject" style="padding: 0.75rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; font-family: monospace; font-size: 0.875rem;"></div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Email Body</label>
                        <div id="draft-body" style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; white-space: pre-wrap; font-family: system-ui; font-size: 0.875rem; line-height: 1.6; min-height: 200px;"></div>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button id="copy-btn" onclick="copyDraft()" class="btn btn-primary" style="flex: 1;">
                            Copy to Clipboard
                        </button>
                        <button onclick="generateDraft()" class="btn btn-secondary" style="flex: 1;">
                            Regenerate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let scanning = false;

        async function fetchOpportunities() {
            try {
                const response = await fetch('api/opportunities.php');
                const data = await response.json();

                if (data.success && data.opportunities.length > 0) {
                    displayOpportunities(data.opportunities);
                }
            } catch (error) {
                console.error('Error fetching opportunities:', error);
            }
        }

        async function scanEmails() {
            if (scanning) return;

            scanning = true;
            const btn = document.getElementById('scan-btn');
            const status = document.getElementById('scan-status');
            const alertsDiv = document.getElementById('alerts');

            btn.disabled = true;
            btn.textContent = 'Scanning...';
            status.textContent = 'Scanning emails... This may take a minute.';

            try {
                const response = await fetch('api/scan.php', {
                    method: 'POST'
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to scan emails');
                }

                status.textContent = `Scanned ${data.processed} emails. Found ${data.opportunities} opportunities.`;

                alertsDiv.innerHTML = `
                    <div class="alert alert-success">
                        Successfully scanned ${data.processed} of ${data.total} emails.
                        Found ${data.opportunities} opportunities!
                    </div>
                `;

                // Refresh opportunities list
                await fetchOpportunities();
            } catch (error) {
                console.error('Scan error:', error);
                status.textContent = 'Click to scan your last 50 emails for opportunities';
                alertsDiv.innerHTML = `
                    <div class="alert alert-error">
                        ${error.message}
                    </div>
                `;
            } finally {
                scanning = false;
                btn.disabled = false;
                btn.textContent = 'Scan My Emails';
            }
        }

        function displayOpportunities(opportunities) {
            const container = document.getElementById('opportunities-container');
            const count = document.getElementById('opportunity-count');

            count.textContent = opportunities.length;

            if (opportunities.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p style="font-weight: 500; margin-bottom: 0.5rem;">No opportunities found</p>
                        <p style="font-size: 0.875rem;">Try scanning your emails to discover opportunities</p>
                    </div>
                `;
                return;
            }

            const tableHTML = `
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${opportunities.map(opp => `
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">${escapeHtml(opp.company_name || 'Unknown')}</div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">${escapeHtml(opp.sender)}</div>
                                    </td>
                                    <td>
                                        <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(opp.email_subject)}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-${opp.classification}">
                                            ${capitalize(opp.classification)}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500;">${opp.relevance_score}/10</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;" onclick="openDraftModal(${opp.id})">
                                            Draft Email
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = tableHTML;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function capitalize(text) {
            return text.charAt(0).toUpperCase() + text.slice(1);
        }

        // Load opportunities on page load
        fetchOpportunities();

        // Draft Email Modal Functions
        let currentOpportunityId = null;

        function openDraftModal(opportunityId) {
            currentOpportunityId = opportunityId;
            const modal = document.getElementById('draft-modal');
            const toneSelect = document.getElementById('tone-select');
            const draftContent = document.getElementById('draft-content');
            const draftLoading = document.getElementById('draft-loading');
            const draftError = document.getElementById('draft-error');

            // Reset modal state
            toneSelect.value = '';
            draftContent.style.display = 'none';
            draftLoading.style.display = 'none';
            draftError.style.display = 'none';

            modal.style.display = 'flex';
        }

        function closeDraftModal() {
            const modal = document.getElementById('draft-modal');
            modal.style.display = 'none';
            currentOpportunityId = null;
        }

        async function generateDraft() {
            const tone = document.getElementById('tone-select').value;

            if (!tone) {
                return;
            }

            const draftContent = document.getElementById('draft-content');
            const draftLoading = document.getElementById('draft-loading');
            const draftError = document.getElementById('draft-error');

            // Show loading state
            draftContent.style.display = 'none';
            draftError.style.display = 'none';
            draftLoading.style.display = 'block';

            try {
                const response = await fetch(`api/draft.php?id=${currentOpportunityId}&tone=${tone}`);
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to generate draft');
                }

                // Display draft
                document.getElementById('draft-subject').textContent = data.draft.subject;
                document.getElementById('draft-body').textContent = data.draft.body;

                draftLoading.style.display = 'none';
                draftContent.style.display = 'block';
            } catch (error) {
                console.error('Draft generation error:', error);
                draftError.textContent = error.message;
                draftError.style.display = 'block';
                draftLoading.style.display = 'none';
            }
        }

        async function copyDraft() {
            const subject = document.getElementById('draft-subject').textContent;
            const body = document.getElementById('draft-body').textContent;
            const fullText = `Subject: ${subject}\n\n${body}`;

            try {
                await navigator.clipboard.writeText(fullText);

                // Show feedback
                const btn = document.getElementById('copy-btn');
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.background = '#10b981';

                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '';
                }, 2000);
            } catch (error) {
                console.error('Copy failed:', error);
                alert('Failed to copy to clipboard. Please select and copy manually.');
            }
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeDraftModal();
            }
        });

        // Close modal when clicking outside
        document.getElementById('draft-modal').addEventListener('click', (e) => {
            if (e.target.id === 'draft-modal') {
                closeDraftModal();
            }
        });
    </script>
</body>
</html>
