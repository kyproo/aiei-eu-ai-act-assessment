<?php
session_start();
require_once __DIR__ . "/config.php";

header("Content-Type: application/json; charset=utf-8");

function json_out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(["ok" => false, "error" => "Method not allowed"], 405);
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
  json_out(["ok" => false, "error" => "Invalid JSON"], 400);
}

if (!defined('OPENAI_API_KEY') || !OPENAI_API_KEY || OPENAI_API_KEY === 'PASTE_YOUR_OPENAI_KEY_HERE') {
  json_out(["ok" => false, "error" => "Missing API key — please configure config.php"], 500);
}

if (!function_exists('curl_init')) {
  json_out(["ok" => false, "error" => "PHP cURL is not enabled on the server"], 500);
}

$url = "https://api.openai.com/v1/responses";

$system = "You are a senior AI compliance expert specialising in the EU AI Act, ISO 42001, and AI governance frameworks. Provide practical, business-friendly guidance.\n\n"
        . "Rules:\n"
        . "- This is a SELF-ASSESSMENT, not a formal audit opinion\n"
        . "- Do NOT provide legal advice\n"
        . "- Be specific: actions, suggested owners, documents needed\n"
        . "- Output in clean Markdown with headings\n"
        . "- Reference EU AI Act articles where relevant\n\n"
        . "Required sections:\n"
        . "1) Executive Summary (5 bullets)\n"
        . "2) Risk Classification Assessment\n"
        . "3) Key Compliance Gaps (top 5)\n"
        . "4) 30/60/90 Day Action Plan\n"
        . "5) Evidence and Documentation Checklist\n"
        . "6) How AIEI Can Help (membership, resources, community)";

$user = "Generate EU AI Act compliance recommendations based on this self-assessment data:\n"
      . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

$payload = [
  "model" => defined('OPENAI_MODEL') ? OPENAI_MODEL : "gpt-4o-mini",
  "input" => [
    ["role" => "system", "content" => [["type" => "input_text", "text" => $system]]],
    ["role" => "user", "content" => [["type" => "input_text", "text" => $user]]],
  ],
  "store" => false
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "Authorization: Bearer " . OPENAI_API_KEY
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 60,
]);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) json_out(["ok" => false, "error" => "cURL error: " . $err], 500);
if ($http < 200 || $http >= 300) json_out(["ok" => false, "error" => "HTTP $http: " . $resp], 500);

$json = json_decode($resp, true);

$text = "";
if (isset($json["output_text"]) && is_string($json["output_text"])) {
  $text = $json["output_text"];
} else {
  if (!empty($json["output"]) && is_array($json["output"])) {
    foreach ($json["output"] as $item) {
      if (!empty($item["content"]) && is_array($item["content"])) {
        foreach ($item["content"] as $c) {
          if (!empty($c["text"])) $text .= $c["text"] . "\n";
        }
      }
    }
  }
}

$text = trim($text);
if (!$text) $text = "AI generated a response, but it could not be parsed. Please try again.";

json_out(["ok" => true, "markdown" => $text]);
