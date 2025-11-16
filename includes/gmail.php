<?php
// Gmail API Helper Functions

function getGmailService($accessToken) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $client = new Google_Client();
    $client->setAccessToken($accessToken);

    return new Google_Service_Gmail($client);
}

function fetchRecentEmails($accessToken, $maxResults = 50) {
    try {
        $service = getGmailService($accessToken);

        // Fetch message list (unread or from last 7 days)
        $optParams = [
            'maxResults' => $maxResults,
            'q' => 'is:unread OR newer_than:7d'
        ];

        $results = $service->users_messages->listUsersMessages('me', $optParams);
        $messages = $results->getMessages();

        if (empty($messages)) {
            return [];
        }

        $emails = [];

        foreach ($messages as $message) {
            $messageId = $message->getId();
            $messageDetail = $service->users_messages->get('me', $messageId, ['format' => 'full']);

            $headers = $messageDetail->getPayload()->getHeaders();
            $subject = '';
            $from = '';
            $date = '';

            foreach ($headers as $header) {
                if ($header->getName() === 'Subject') {
                    $subject = $header->getValue();
                } elseif ($header->getName() === 'From') {
                    $from = $header->getValue();
                } elseif ($header->getName() === 'Date') {
                    $date = $header->getValue();
                }
            }

            $snippet = $messageDetail->getSnippet();

            $emails[] = [
                'id' => $messageId,
                'subject' => $subject ?: 'No Subject',
                'from' => $from ?: 'Unknown Sender',
                'date' => $date ?: date('c'),
                'snippet' => $snippet ?: ''
            ];
        }

        return $emails;
    } catch (Exception $e) {
        error_log('Error fetching emails: ' . $e->getMessage());
        throw new Exception('Failed to fetch emails from Gmail');
    }
}
