<?php
session_start();
require_once __DIR__ . "/config.php";

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function statusScore($v) {
  if ($v === 'yes')     return 1.0;
  if ($v === 'partial') return 0.6;
  if ($v === 'planned') return 0.3;
  return 0.0;
}

/* ================== PAGE SHELL ================== */
function render_shell($title, $bodyHtml) {
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> — AIEI</title>
    <link rel="icon" href="https://ai-ei.org/favicon.ico">
    <link rel="stylesheet" href="styles.css">
  </head>
  <body>
    <div class="wrap">

      <header class="topbar">
        <a class="brand" href="index.php">
          <div>
            <div class="brandName">AIEI</div>
            <div class="brandSub">EU AI Act Assessment</div>
          </div>
        </a>
        <div class="topRight">
          <a class="btn" href="index.php">← Home</a>
          <a class="btn btnPrimary" href="euaiact.php">Retake Assessment</a>
        </div>
      </header>

      <?= $bodyHtml ?>

      <div class="footer">
        <p>
          &copy; <?= date('Y') ?> <a href="https://ai-ei.org" target="_blank" rel="noopener">AIEI — AI Ethics and Integrity International Association</a> ·
          Self-assessment only · Not legal advice · Not a certification
        </p>
      </div>

    </div>
  </body>
  </html>
  <?php
  exit;
}

/* ================== HANDLE DIRECT GET ================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $html = '
    <div class="card" style="max-width: 600px; margin: 40px auto;">
      <h1 style="font-size: 26px;">Results page</h1>
      <p class="muted">This page displays your results after completing the EU AI Act self-assessment.</p>
      <div class="btnRow">
        <a class="btn btnPrimary" href="euaiact.php">Start EU AI Act Assessment →</a>
      </div>
      <p class="small" style="margin-top: 12px;">This is a self-assessment only — not an audit, legal opinion, or certification.</p>
    </div>';
  render_shell("EU AI Act Assessment Result", $html);
}

/* ================== CSRF CHECK ================== */
if (
  empty($_POST['csrf']) ||
  empty($_SESSION['csrf']) ||
  !hash_equals($_SESSION['csrf'], $_POST['csrf'])
) {
  $html = '
    <div class="card" style="max-width: 600px; margin: 40px auto;">
      <h1 style="font-size: 26px;">Session expired</h1>
      <p class="muted">Please restart the assessment — your session has expired.</p>
      <div class="btnRow">
        <a class="btn btnPrimary" href="euaiact.php">Restart Assessment →</a>
      </div>
    </div>';
  render_shell("Session Expired", $html);
}

/* ================== QUESTIONS + WEIGHTS ================== */
$sections = [
  'AI System Classification' => [
    'q1'  => ['Have you identified all AI systems used in your organisation?', 3],
    'q2'  => ['Have you assessed whether any AI systems fall under prohibited uses (EU AI Act Annex I)?', 3],
    'q3'  => ['Have you classified your AI systems by risk level?', 3],
    'q4'  => ['Have you checked if your AI systems are listed in Annex III (high-risk categories)?', 3],
  ],
  'Risk Management' => [
    'q5'  => ['Documented risk management system for AI throughout the full lifecycle', 3],
    'q6'  => ['Technical robustness and safety measures for high-risk AI systems', 3],
    'q7'  => ['Adversarial testing or red-teaming for high-risk AI systems', 3],
  ],
  'Data Governance' => [
    'q8'  => ['Training, validation, and testing datasets documented and managed', 3],
    'q9'  => ['Data quality criteria defined (relevance, representativeness, completeness)', 3],
    'q10' => ['Measures to detect and address bias in training data', 3],
  ],
  'Transparency and Documentation' => [
    'q11' => ['Technical documentation maintained for all high-risk AI systems', 2],
    'q12' => ['Logs automatically generated to enable traceability of AI outputs', 2],
    'q13' => ['Users informed when interacting with an AI system', 2],
    'q14' => ['Clear description of AI capabilities and limitations available to users', 2],
  ],
  'Human Oversight' => [
    'q15' => ['Human oversight measures designed into high-risk AI systems', 3],
    'q16' => ['Operators can effectively monitor AI system operation in real-time', 3],
    'q17' => ['Clear process for human intervention or override of AI decisions', 3],
    'q18' => ['Staff trained to understand AI system limitations and oversight responsibilities', 3],
  ],
  'Conformity Assessment' => [
    'q19' => ['Conformity assessment conducted for high-risk AI systems', 2],
    'q20' => ['EU Declaration of Conformity prepared for applicable systems', 2],
    'q21' => ['CE marking requirements considered for applicable high-risk AI systems', 2],
  ],
  'Accuracy and Robustness' => [
    'q22' => ['Accuracy metrics defined and regularly monitored for AI systems', 2],
    'q23' => ['AI system tested against cybersecurity threats and adversarial attacks', 2],
    'q24' => ['Fallback mechanisms in place when AI system performance degrades', 2],
  ],
  'Post-Market Monitoring' => [
    'q25' => ['Post-market monitoring plan for all deployed AI systems', 2],
    'q26' => ['Serious incidents and near-misses reported to relevant authorities', 2],
    'q27' => ['Process for AI system updates, version control, and re-assessment', 2],
  ],
  'Governance and Accountability' => [
    'q28' => ['Designated AI compliance officer or responsible person for AI governance', 2],
    'q29' => ['Roles and responsibilities for AI governance clearly defined and documented', 2],
    'q30' => ['Internal AI ethics policy or code of conduct in place', 2],
  ],
];

// Flatten for scoring
$weights = [];
$qText   = [];
foreach ($sections as $section => $qs) {
  foreach ($qs as $qid => [$text, $w]) {
    $weights[$qid] = $w;
    $qText[$qid]   = $text;
  }
}

/* ================== CONTEXT ================== */
$company     = trim($_POST['company']     ?? '');
$email       = trim($_POST['email']       ?? '');
$industry    = trim($_POST['industry']    ?? '');
$size        = trim($_POST['size']        ?? '');
$ai_role     = trim($_POST['ai_role']     ?? '');
$eu_presence = trim($_POST['eu_presence'] ?? '');

/* ================== SCORE ================== */
$sum  = 0.0;
$max  = 0.0;
$gaps = [];

foreach ($weights as $qid => $w) {
  $ans = isset($_POST[$qid]) ? (string)$_POST[$qid] : '';
  $s   = statusScore($ans);
  $sum += $w * $s;
  $max += $w;

  if (($ans === 'no' || $ans === 'planned') && $w >= 2) {
    $gaps[] = ['qid' => $qid, 'w' => $w, 'ans' => $ans];
  }
}

$score      = ($max > 0) ? (int)round(($sum / $max) * 100) : 0;
$plainStatus = ($score >= 80) ? "Compliant / Ready"
             : (($score >= 55) ? "Partially Ready"
             : "Significant Gaps");

if ($score >= 80) {
  $statusIcon  = '🟢';
  $statusClass = 'scoreGreen';
} elseif ($score >= 55) {
  $statusIcon  = '🟡';
  $statusClass = 'scoreAmber';
} else {
  $statusIcon  = '🔴';
  $statusClass = 'scoreRed';
}

usort($gaps, function($a, $b){ return $b['w'] <=> $a['w']; });
$topGaps = array_slice($gaps, 0, 5);

/* ================== SECTION SCORES ================== */
$sectionScores = [];
foreach ($sections as $section => $qs) {
  $sSum = 0; $sMax = 0;
  foreach ($qs as $qid => [$text, $w]) {
    $ans = isset($_POST[$qid]) ? (string)$_POST[$qid] : '';
    $sSum += $w * statusScore($ans);
    $sMax += $w;
  }
  $sectionScores[$section] = ($sMax > 0) ? (int)round(($sSum / $sMax) * 100) : 0;
}

/* ================== CSV SAVE ================== */
$saveOk = false;
$dir  = __DIR__ . '/data';
$file = $dir . '/submissions.csv';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
if (is_dir($dir) && is_writable($dir)) {
  $row = [date('c'), $company, $email, $industry, $size, $ai_role, $eu_presence, $score, $plainStatus];
  $fp  = @fopen($file, 'a');
  if ($fp) { fputcsv($fp, $row); fclose($fp); $saveOk = true; }
}

/* ================== EMAIL CTA ================== */
$gapLines = "";
foreach ($topGaps as $g) {
  $label      = $qText[$g['qid']] ?? $g['qid'];
  $gapLines  .= "- {$label} ({$g['ans']})\n";
}
if (!$gapLines) $gapLines = "- (none detected)\n";

$emailSubject = "EU AI Act self-assessment: {$score}% ({$plainStatus})";
$emailBody    =
"Hello AIEI team,\n\n".
"We completed the EU AI Act readiness self-assessment.\n\n".
"Organisation: {$company}\n".
"Score: {$score}%\n".
"Status: {$plainStatus}\n\n".
"Top gaps:\n{$gapLines}\n".
"Context:\n".
"- Industry: {$industry}\n".
"- Size: {$size}\n".
"- AI role: {$ai_role}\n".
"- EU presence: {$eu_presence}\n\n".
"We are interested in learning more about AIEI membership and EU AI Act compliance support.\n\n".
"Best regards,\n{$company}\n";

$mailto = "mailto:info@ai-ei.org"
        . "?subject=" . rawurlencode($emailSubject)
        . "&body="    . rawurlencode($emailBody);

/* ================== AI PAYLOAD ================== */
$aiData = [
  "standard" => "EU AI Act",
  "organisation_context" => [
    "industry"    => $industry,
    "size"        => $size,
    "ai_role"     => $ai_role,
    "eu_presence" => $eu_presence,
  ],
  "readiness" => [
    "score_percent"  => $score,
    "traffic_light"  => $plainStatus,
    "section_scores" => $sectionScores,
  ],
  "top_gaps" => array_map(function($g) use ($qText) {
    return [
      "title"  => $qText[$g['qid']] ?? $g['qid'],
      "answer" => $g['ans'],
      "weight" => $g['w'],
    ];
  }, $topGaps),
  "aiei_positioning" => "AIEI (AI Ethics and Integrity International Association) can help with EU AI Act compliance through membership resources, community of practice, guidance tools, training, and connections to expert practitioners.",
];

/* ================== RENDER PAGE ================== */
ob_start();
?>

<!-- Score card -->
<div class="card" style="margin-bottom: 20px;">
  <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
    <div>
      <h1 style="font-size: 22px; margin-bottom: 4px;">EU AI Act Self-Assessment Result</h1>
      <p class="muted" style="margin: 0 0 16px;">Organisation: <strong style="color: var(--navy);"><?= esc($company) ?></strong></p>

      <div class="score <?= $statusClass ?>"><?= $score ?>%</div>
      <div class="scoreLabel <?= $statusClass ?>"><?= $statusIcon ?> <?= esc($plainStatus) ?></div>
    </div>

    <div style="text-align: right;">
      <div class="small" style="margin-bottom: 8px;">Industry: <?= esc($industry) ?> · Size: <?= esc($size) ?></div>
      <div class="small" style="margin-bottom: 8px;">Role: <?= esc($ai_role) ?></div>
      <div class="small">EU presence: <?= esc($eu_presence) ?></div>
    </div>
  </div>

  <div class="divider"></div>

  <p class="small">
    ⚠️ <strong>Disclaimer:</strong> This is an initial self-assessment based on your answers. It is not an audit, not a legal opinion, and does not confirm compliance or certification with the EU AI Act.
  </p>
</div>

<!-- Section scores + gaps -->
<div class="grid2" style="margin-bottom: 20px;">

  <div class="card">
    <h2>Scores by category</h2>
    <?php foreach ($sectionScores as $section => $pct): ?>
      <div style="margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
          <span style="font-size: 13px; font-weight: 600; color: var(--navy);"><?= esc($section) ?></span>
          <span style="font-size: 13px; font-weight: 700; color: <?= $pct >= 75 ? '#16a34a' : ($pct >= 45 ? '#d97706' : '#dc2626') ?>;"><?= $pct ?>%</span>
        </div>
        <div class="bar" style="min-width: 0; height: 6px; flex: none;">
          <div class="fill" style="width: <?= $pct ?>%; background: <?= $pct >= 75 ? '#16a34a' : ($pct >= 45 ? '#f59e0b' : '#dc2626') ?>;"></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Key gaps identified</h2>
    <?php if (count($topGaps) === 0): ?>
      <div class="muted">🎉 No major gaps detected based on your answers. Well done!</div>
    <?php else: ?>
      <ul style="margin-top: 0;">
        <?php foreach ($topGaps as $g): ?>
          <li style="margin-bottom: 8px;">
            <strong style="color: var(--navy);"><?= esc($qText[$g['qid']] ?? $g['qid']) ?></strong>
            <span class="small"> — <?= $g['ans'] === 'no' ? '🔴 Not in place' : '🟡 Planned only' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="divider"></div>

    <h2 style="margin-top: 0;">How AIEI can help</h2>
    <p class="muted" style="font-size: 14px;">AIEI supports organisations navigating EU AI Act compliance:</p>
    <ul style="margin-top: 0;">
      <li>Membership resources and guidance library</li>
      <li>Community of AI practitioners across Europe</li>
      <li>Training and awareness programmes</li>
      <li>Connections to compliance and legal experts</li>
      <li>Policy updates and regulatory monitoring</li>
    </ul>

    <div class="btnRow">
      <a class="btn btnNavy" href="https://ai-ei.org" target="_blank" rel="noopener">Join AIEI →</a>
      <a class="btn" href="<?= esc($mailto) ?>">Contact AIEI →</a>
    </div>
  </div>

</div>

<!-- AI Recommendations -->
<div class="card" style="margin-bottom: 20px;">
  <h2>AI-Powered Recommendations</h2>
  <p class="small">Generated from your answers using OpenAI. Self-assessment only — not legal advice or an audit.</p>

  <div id="aiLoader" class="loader">
    <span class="spinner"></span>
    Generating your personalised EU AI Act recommendations…
  </div>

  <div id="aiError" class="errorBox" style="display: none;"></div>
  <div id="aiText" class="mono" style="margin-top: 14px; display: none;"></div>
</div>

<div class="card" style="background: var(--bg2);">
  <p class="small" style="margin: 0;">
    <?= $saveOk ? "✅ Submission recorded." : "ℹ️ Could not save submission locally (check /data directory permissions)." ?>
    &nbsp;·&nbsp;
    Retake: <a href="euaiact.php">euaiact.php</a>
  </p>
</div>

<script>
  const payload = <?= json_encode($aiData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

  const loader = document.getElementById('aiLoader');
  const errBox = document.getElementById('aiError');
  const txtBox = document.getElementById('aiText');

  fetch('ai.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(async (r) => {
    const j = await r.json().catch(() => null);
    if (!r.ok || !j || !j.ok) {
      const msg = j && j.error ? j.error : ('Request failed (HTTP ' + r.status + ')');
      throw new Error(msg);
    }
    return j.markdown || '';
  })
  .then((md) => {
    loader.style.display = 'none';
    txtBox.style.display = 'block';
    txtBox.textContent   = md;
  })
  .catch((e) => {
    loader.style.display = 'none';
    errBox.style.display = 'block';
    errBox.textContent   = 'AI recommendations could not be generated: ' + (e && e.message ? e.message : String(e));
  });
</script>

<?php
$body = ob_get_clean();
render_shell("EU AI Act Assessment Result — " . ($company ?: "AIEI"), $body);
