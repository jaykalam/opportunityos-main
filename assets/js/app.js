/**
 * OpportunityOS - Main Application JavaScript
 * Handles UI interactions, LocalStorage tracking, and API calls
 */

// Constants
const MAX_FREE_TRIES = 3;
const STORAGE_KEY = 'opportunityos_tries';

// State
let triesRemaining = MAX_FREE_TRIES;
let currentEmailDraft = '';

// DOM Elements
const inputContent = document.getElementById('inputContent');
const generateBtn = document.getElementById('generateBtn');
const emailPreview = document.getElementById('emailPreview');
const copyBtn = document.getElementById('copyBtn');
const copySection = document.getElementById('copySection');
const triesLeftSpan = document.getElementById('triesLeft');
const signupLink = document.getElementById('signupLink');
const loadingOverlay = document.getElementById('loadingOverlay');

// Filter elements
const toneSelect = document.getElementById('toneSelect');
const purposeSelect = document.getElementById('purposeSelect');
const lengthSlider = document.getElementById('lengthSlider');
const lengthLabel = document.getElementById('lengthLabel');
const styleRadios = document.querySelectorAll('input[name="styleToggle"]');

// Modal
let signupModal;

/**
 * Initialize the application
 */
document.addEventListener('DOMContentLoaded', () => {
    initializeTryCounter();
    setupEventListeners();
    initializeModal();
    updateLengthLabel();
});

/**
 * Initialize the try counter from LocalStorage
 */
function initializeTryCounter() {
    const stored = localStorage.getItem(STORAGE_KEY);

    if (stored) {
        const data = JSON.parse(stored);
        triesRemaining = data.remaining || 0;
    } else {
        triesRemaining = MAX_FREE_TRIES;
        saveTryCounter();
    }

    updateTryCounterDisplay();
}

/**
 * Save try counter to LocalStorage
 */
function saveTryCounter() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        remaining: triesRemaining,
        lastUpdated: new Date().toISOString()
    }));
}

/**
 * Update the try counter display
 */
function updateTryCounterDisplay() {
    triesLeftSpan.textContent = triesRemaining;

    if (triesRemaining <= 0) {
        generateBtn.disabled = true;
        generateBtn.textContent = 'No Tries Remaining';
        showSignupModal();
    }
}

/**
 * Initialize Bootstrap modal
 */
function initializeModal() {
    const modalElement = document.getElementById('signupModal');
    signupModal = new bootstrap.Modal(modalElement);
}

/**
 * Show signup modal
 */
function showSignupModal() {
    if (signupModal) {
        signupModal.show();
    }
}

/**
 * Setup all event listeners
 */
function setupEventListeners() {
    // Generate button
    generateBtn.addEventListener('click', handleGenerate);

    // Copy button
    copyBtn.addEventListener('click', handleCopy);

    // Signup link
    signupLink.addEventListener('click', (e) => {
        e.preventDefault();
        showSignupModal();
    });

    // Length slider
    lengthSlider.addEventListener('input', updateLengthLabel);

    // Input validation
    inputContent.addEventListener('input', validateInput);
}

/**
 * Update length label based on slider value
 */
function updateLengthLabel() {
    const value = parseInt(lengthSlider.value);
    let label = '';

    switch(value) {
        case 1:
            label = 'Short (50 words)';
            break;
        case 2:
            label = 'Standard (150 words)';
            break;
        case 3:
            label = 'Detailed (300 words)';
            break;
    }

    lengthLabel.textContent = label;
}

/**
 * Validate input and enable/disable generate button
 */
function validateInput() {
    const hasContent = inputContent.value.trim().length > 10;
    const hasTries = triesRemaining > 0;

    generateBtn.disabled = !hasContent || !hasTries;
}

/**
 * Get current filter settings
 */
function getFilterSettings() {
    const lengthValue = parseInt(lengthSlider.value);
    let wordCount = 150;

    switch(lengthValue) {
        case 1:
            wordCount = 50;
            break;
        case 2:
            wordCount = 150;
            break;
        case 3:
            wordCount = 300;
            break;
    }

    let style = 'us';
    styleRadios.forEach(radio => {
        if (radio.checked) {
            style = radio.value;
        }
    });

    return {
        tone: toneSelect.value,
        purpose: purposeSelect.value,
        wordCount: wordCount,
        style: style
    };
}

/**
 * Handle generate button click
 */
async function handleGenerate() {
    const content = inputContent.value.trim();

    if (!content || content.length < 10) {
        alert('Please paste some content first (at least 10 characters).');
        return;
    }

    if (triesRemaining <= 0) {
        showSignupModal();
        return;
    }

    // Get filter settings
    const filters = getFilterSettings();

    // Show loading
    showLoading(true);
    generateBtn.disabled = true;

    try {
        // Call API
        const response = await fetch('api/generate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                filters: filters
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // Update tries
            triesRemaining--;
            saveTryCounter();
            updateTryCounterDisplay();

            // Display result
            displayEmailDraft(data.draft);
        } else {
            throw new Error(data.error || 'Failed to generate email draft');
        }

    } catch (error) {
        console.error('Error generating draft:', error);
        alert('Sorry, there was an error generating your email draft. Please try again.');
    } finally {
        showLoading(false);
        generateBtn.disabled = triesRemaining <= 0;
    }
}

/**
 * Display the generated email draft
 */
function displayEmailDraft(draft) {
    currentEmailDraft = draft;

    // Remove empty state and show draft
    emailPreview.innerHTML = '';
    emailPreview.classList.add('has-content');

    // Format the draft nicely
    const formattedDraft = document.createElement('div');
    formattedDraft.style.whiteSpace = 'pre-wrap';
    formattedDraft.textContent = draft;

    emailPreview.appendChild(formattedDraft);

    // Show copy button
    copySection.style.display = 'block';
}

/**
 * Handle copy to clipboard
 */
async function handleCopy() {
    if (!currentEmailDraft) {
        return;
    }

    try {
        await navigator.clipboard.writeText(currentEmailDraft);

        // Visual feedback
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px; vertical-align: middle;">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Copied!
        `;
        copyBtn.classList.remove('btn-outline-primary');
        copyBtn.classList.add('btn-success');

        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-outline-primary');
        }, 2000);

    } catch (error) {
        console.error('Failed to copy:', error);

        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = currentEmailDraft;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            alert('Email draft copied to clipboard!');
        } catch (err) {
            alert('Failed to copy. Please select and copy manually.');
        }

        document.body.removeChild(textarea);
    }
}

/**
 * Show/hide loading overlay
 */
function showLoading(show) {
    loadingOverlay.style.display = show ? 'flex' : 'none';
}

/**
 * Reset try counter (for testing - can be called from console)
 */
window.resetTries = function() {
    triesRemaining = MAX_FREE_TRIES;
    saveTryCounter();
    updateTryCounterDisplay();
    generateBtn.disabled = false;
    generateBtn.textContent = 'Generate Email Draft';
    console.log('Try counter reset to', MAX_FREE_TRIES);
};
