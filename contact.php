<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, bool $success, string $message): void
{
    http_response_code($status);
    echo json_encode(
        ['success' => $success, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Please submit the contact form to send your enquiry.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$project = trim((string) ($_POST['project'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$honeypot = trim((string) ($_POST['website'] ?? ''));

if ($honeypot !== '') {
    respond(422, false, 'Please check the form and try again.');
}

$allowedProjects = [
    'Website subscription',
    'SEO and search visibility',
    'Paid campaigns',
    'Social media and content',
    'Multiple digital marketing services',
    'Something else',
];

if (
    $name === '' || strlen($name) > 480 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 ||
    !in_array($project, $allowedProjects, true) ||
    $message === '' || strlen($message) > 20000
) {
    respond(422, false, 'Please check your details and message, then try again.');
}

$recipient = 'work.ritvoo@gmail.com';
$safeName = str_replace(["\r", "\n"], ' ', $name);
$subject = 'Website enquiry from ' . $safeName;
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$body = implode("\n", [
    'A new enquiry was sent from the Ritvoo website.',
    '',
    'Name: ' . $safeName,
    'Email: ' . $email,
    'Interested in: ' . $project,
    '',
    'Message:',
    $message,
]);
$headers = [
    'From: Ritvoo Website <hello@ritvoo.in>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

if (!mail($recipient, $encodedSubject, $body, implode("\r\n", $headers))) {
    error_log('Ritvoo contact form: PHP mail() failed to accept an enquiry.');
    respond(500, false, 'We couldn’t send your enquiry right now. Please email hello@ritvoo.in directly.');
}

respond(200, true, 'Thanks — we’ve received your enquiry. We’ll be in touch soon.');
