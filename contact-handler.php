<?php
/**
 * ClemRae LLC Universal Contact Handler
 * Place this file in the same folder as your HTML pages.
 */

declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

$siteName = "ClemRae LLC";
$toEmail = "clemraellc@gmail.com";
$fromEmail = "no-reply@clemrae.com";
$fromName = "ClemRae Website";
$motto = "Bringing ideas to light";

function respond(bool $success, string $message, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);
    exit;
}

function clean_input(string $value, int $maxLength = 1000): string {
    $value = trim($value);
    $value = str_replace(["\0", "\r"], "", $value);
    $value = strip_tags($value);
    if (function_exists("mb_substr")) {
        $value = mb_substr($value, 0, $maxLength, "UTF-8");
    } else {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

function clean_email(string $email): string {
    $email = trim($email);
    $email = str_replace(["\r", "\n", "%0a", "%0d"], "", $email);
    return filter_var($email, FILTER_SANITIZE_EMAIL) ?: "";
}

function esc_html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function valid_url_or_blank(string $url): bool {
    if ($url === "") {
        return true;
    }
    return (bool) filter_var($url, FILTER_VALIDATE_URL);
}

function build_rows(array $fields): string {
    $rows = "";
    foreach ($fields as $label => $value) {
        if ($value === "") {
            continue;
        }
        $rows .= "
            <tr>
                <td style=\"padding:12px 14px;border-bottom:1px solid #e8eceb;font-weight:700;color:#152B29;width:34%;vertical-align:top;\">" . esc_html($label) . "</td>
                <td style=\"padding:12px 14px;border-bottom:1px solid #e8eceb;color:#334b48;vertical-align:top;\">" . nl2br(esc_html($value)) . "</td>
            </tr>";
    }
    return $rows;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, "Method not allowed.", 405);
}

/* Honeypot spam protection */
$honeypot = trim($_POST["website_confirm"] ?? "");
if ($honeypot !== "") {
    respond(true, "Thank you for your inquiry.");
}

/* Collect fields */
$division = clean_input($_POST["Division"] ?? "General Contact", 80);
$name = clean_input($_POST["Name"] ?? "", 100);
$email = clean_email($_POST["Email"] ?? "");
$phone = clean_input($_POST["Phone"] ?? "", 40);
$service = clean_input($_POST["Service Interest"] ?? "", 140);
$company = clean_input($_POST["Company"] ?? "", 160);
$currentWebsite = clean_input($_POST["Current Website"] ?? "", 220);
$timeline = clean_input($_POST["Timeline"] ?? "", 100);
$budget = clean_input($_POST["Budget"] ?? "", 100);
$message = clean_input($_POST["Message"] ?? "", 3000);

/* Validate required fields */
if ($name === "" || $email === "" || $message === "") {
    respond(false, "Please complete your name, email, and message.", 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, "Please enter a valid email address.", 400);
}

if (!valid_url_or_blank($currentWebsite)) {
    respond(false, "Please enter a valid website URL beginning with https:// or leave the website field blank.", 400);
}

if ((function_exists("mb_strlen") ? mb_strlen($message, "UTF-8") : strlen($message)) < 10) {
    respond(false, "Please include a little more detail in your message.", 400);
}

/* Allowed divisions */
$allowedDivisions = [
    "ClemRae Digital",
    "ClemRae Learning",
    "ClemRae Education",
    "First Class Realty",
    "General Contact"
];

if (!in_array($division, $allowedDivisions, true)) {
    $division = "General Contact";
}

/* Simple spam keyword protection */
$spamPattern = "/\b(viagra|casino|crypto investment|loan offer|adult dating|porn|sex|hack|seo backlinks|backlinks)\b/i";
if (preg_match($spamPattern, $message . " " . $name . " " . $company)) {
    respond(false, "Your message could not be submitted.", 400);
}

$subject = "New Inquiry - " . $division;

$fields = [
    "Division" => $division,
    "Name" => $name,
    "Email" => $email,
    "Phone" => $phone,
    "Service Interest" => $service,
    "Company / Organization / Area" => $company,
    "Current Website" => $currentWebsite,
    "Timeline" => $timeline,
    "Budget / Price Range" => $budget,
    "Message" => $message
];

$rows = build_rows($fields);

$adminEmailHtml = "
<!DOCTYPE html>
<html>
<head>
  <meta charset=\"UTF-8\">
  <title>" . esc_html($subject) . "</title>
</head>
<body style=\"margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#152B29;\">
  <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#f4f7f6;padding:28px 12px;\">
    <tr>
      <td align=\"center\">
        <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"max-width:680px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #d8e0de;\">
          <tr>
            <td style=\"background:#152B29;color:#ffffff;padding:28px 30px;\">
              <div style=\"font-size:26px;font-weight:700;letter-spacing:.2px;\">ClemRae LLC</div>
              <div style=\"font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:#F4d35e;margin-top:6px;\">New Website Inquiry</div>
            </td>
          </tr>
          <tr>
            <td style=\"padding:26px 30px;\">
              <p style=\"margin:0 0 18px;font-size:16px;line-height:1.6;color:#334b48;\">
                A new inquiry was submitted through the ClemRae website.
              </p>
              <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"border:1px solid #e8eceb;border-radius:14px;overflow:hidden;\">
                " . $rows . "
              </table>
            </td>
          </tr>
          <tr>
            <td style=\"background:#f9fbfa;color:#6d7c79;padding:18px 30px;font-size:13px;\">
              " . esc_html($motto) . " · This message was generated from clemrae.com.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>";

$replySubject = "Thank you for contacting ClemRae";

$visitorEmailHtml = "
<!DOCTYPE html>
<html>
<head>
  <meta charset=\"UTF-8\">
  <title>Thank you for contacting ClemRae</title>
</head>
<body style=\"margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#152B29;\">
  <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#f4f7f6;padding:28px 12px;\">
    <tr>
      <td align=\"center\">
        <table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #d8e0de;\">
          <tr>
            <td style=\"background:#152B29;color:#ffffff;padding:30px;\">
              <div style=\"font-size:28px;font-weight:700;\">ClemRae LLC</div>
              <div style=\"font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:#F4d35e;margin-top:6px;\">" . esc_html($motto) . "</div>
            </td>
          </tr>
          <tr>
            <td style=\"padding:30px;\">
              <h1 style=\"margin:0 0 14px;font-size:26px;line-height:1.2;color:#152B29;\">Thank you for contacting ClemRae.</h1>
              <p style=\"margin:0 0 16px;font-size:16px;line-height:1.7;color:#334b48;\">
                Hello " . esc_html($name) . ",
              </p>
              <p style=\"margin:0 0 16px;font-size:16px;line-height:1.7;color:#334b48;\">
                Thank you for reaching out to ClemRae LLC. We received your inquiry regarding <strong>" . esc_html($division) . "</strong>.
              </p>
              <p style=\"margin:0 0 18px;font-size:16px;line-height:1.7;color:#334b48;\">
                A member of the ClemRae team will review your request and get back to you shortly.
              </p>
              <div style=\"background:#f9fbfa;border-left:4px solid #F4d35e;padding:16px 18px;border-radius:10px;color:#334b48;font-size:15px;line-height:1.6;\">
                We appreciate the opportunity to learn more about your goals.
              </div>
            </td>
          </tr>
          <tr>
            <td style=\"background:#f9fbfa;color:#6d7c79;padding:18px 30px;font-size:13px;\">
              ClemRae LLC · " . esc_html($motto) . "
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>";

$adminHeaders = [];
$adminHeaders[] = "MIME-Version: 1.0";
$adminHeaders[] = "Content-Type: text/html; charset=UTF-8";
$adminHeaders[] = "From: " . $fromName . " <" . $fromEmail . ">";
$adminHeaders[] = "Reply-To: " . $name . " <" . $email . ">";
$adminHeaders[] = "X-Mailer: PHP/" . phpversion();

$visitorHeaders = [];
$visitorHeaders[] = "MIME-Version: 1.0";
$visitorHeaders[] = "Content-Type: text/html; charset=UTF-8";
$visitorHeaders[] = "From: " . $siteName . " <" . $fromEmail . ">";
$visitorHeaders[] = "Reply-To: " . $toEmail;
$visitorHeaders[] = "X-Mailer: PHP/" . phpversion();

$adminSent = mail($toEmail, $subject, $adminEmailHtml, implode("\r\n", $adminHeaders));
$visitorSent = mail($email, $replySubject, $visitorEmailHtml, implode("\r\n", $visitorHeaders));

if (!$adminSent) {
    respond(false, "The message could not be sent. Please try again later.", 500);
}

respond(true, "Thank you for your inquiry. A member of the ClemRae team will get back to you.");
?>
