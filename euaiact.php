<?php
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$sections = [
  'AI System Classification' => [
    'q1'  => ['Have you identified all AI systems used in your organisation?', 3],
    'q2'  => ['Have you assessed whether any AI systems fall under prohibited uses (EU AI Act Annex I)?', 3],
    'q3'  => ['Have you classified your AI systems by risk level (high-risk, limited, minimal, unacceptable)?', 3],
    'q4'  => ['Have you checked if your AI systems are listed in Annex III (high-risk categories)?', 3],
  ],
  'Risk Management' => [
    'q5'  => ['Is there a documented risk management system for AI throughout the full lifecycle?', 3],
    'q6'  => ['Are technical robustness and safety measures implemented for high-risk AI systems?', 3],
    'q7'  => ['Is adversarial testing or red-teaming conducted for high-risk AI systems?', 3],
  ],
  'Data Governance' => [
    'q8'  => ['Are training, validation, and testing datasets documented and managed?', 3],
    'q9'  => ['Are data quality criteria defined (relevance, representativeness, completeness)?', 3],
    'q10' => ['Are measures in place to detect and address bias in training data?', 3],
  ],
  'Transparency and Documentation' => [
    'q11' => ['Is technical documentation maintained for all high-risk AI systems?', 2],
    'q12' => ['Are logs automatically generated to enable traceability of AI outputs?', 2],
    'q13' => ['Are users informed when they are interacting with an AI system?', 2],
    'q14' => ['Is there a clear description of AI capabilities and limitations available to users?', 2],
  ],
  'Human Oversight' => [
    'q15' => ['Are human oversight measures designed into high-risk AI systems?', 3],
    'q16' => ['Can operators effectively monitor AI system operation in real-time?', 3],
    'q17' => ['Is there a clear process for human intervention or override of AI decisions?', 3],
    'q18' => ['Are staff trained to understand AI system limitations and oversight responsibilities?', 3],
  ],
  'Conformity Assessment' => [
    'q19' => ['Has a conformity assessment been conducted for high-risk AI systems?', 2],
    'q20' => ['Is there a EU Declaration of Conformity prepared for applicable systems?', 2],
    'q21' => ['Are CE marking requirements considered for applicable high-risk AI systems?', 2],
  ],
  'Accuracy and Robustness' => [
    'q22' => ['Are accuracy metrics defined and regularly monitored for AI systems?', 2],
    'q23' => ['Is the AI system tested against cybersecurity threats and adversarial attacks?', 2],
    'q24' => ['Are fallback mechanisms in place when AI system performance degrades?', 2],
  ],
  'Post-Market Monitoring' => [
    'q25' => ['Is there a post-market monitoring plan for all deployed AI systems?', 2],
    'q26' => ['Are serious incidents and near-misses reported to relevant authorities?', 2],
    'q27' => ['Is there a process for AI system updates, version control, and re-assessment?', 2],
  ],
  'Governance and Accountability' => [
    'q28' => ['Is there a designated AI compliance officer or responsible person for AI governance?', 2],
    'q29' => ['Are roles and responsibilities for AI governance clearly defined and documented?', 2],
    'q30' => ['Is there an internal AI ethics policy or code of conduct in place?', 2],
  ],
];

$options = [
  'yes'     => ['Yes', 'Fully implemented'],
  'partial' => ['Partially', 'Partly in place'],
  'planned' => ['Planned', 'Planned / in progress'],
  'no'      => ['No', 'Not in place'],
];

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EU AI Act Readiness Assessment — AIEI</title>
  <link rel="icon" href="https://ai-ei.org/favicon.ico">
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Extra form-specific overrides */
    .formWrap { max-width: 880px; margin: 0 auto; }
  </style>
</head>
<body>
  <div class="wrap">

    <header class="topbar">
    <a class="brand" href="https://ai-ei.org" target="_blank" rel="noopener" style="text-decoration: none;">
        <svg width="76" height="30" viewBox="0 0 76 30" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M0 30L10.9392 0H21.547L32.3204 30H22.0442L17.1705 16.4929C16.8542 15.6164 15.6166 15.6107 15.2924 16.4844L10.2762 30H0ZM29.337 9.44751V0H38.9503V9.44751H29.337ZM38.9503 12.4309H29.337L29.4432 13.4774C30.1138 20.0878 33.5722 26.0983 38.9503 30V12.4309ZM41.768 0V30H63.6464V24.3646H51.3812V17.7348H60.9945V12.4309H51.3812V5.96685H63.6464V0H41.768ZM75.2495 4.80641C75.2495 7.3695 73.1717 9.4473 70.6087 9.4473C68.0456 9.4473 65.9678 7.3695 65.9678 4.80641C65.9678 2.24332 68.0456 0.165527 70.6087 0.165527C73.1717 0.165527 75.2495 2.24332 75.2495 4.80641ZM66.0138 9.77893H65.9678V29.9999H75.2495V9.77893H75.2024C74.2853 11.3643 72.5712 12.4309 70.6081 12.4309C68.6449 12.4309 66.9308 11.3643 66.0138 9.77893Z" fill="#151B22"/>
        </svg>
      </a>

      <div class="topRight">
        <span class="pill pill-blue">🇪🇺 EU AI Act · Self-Assessment</span>
      </div>
    </header>

    <div class="formWrap">

      <div class="card">
        <h1 style="font-size: 28px; margin-bottom: 6px;">EU AI Act Readiness Check</h1>
        <p class="muted">Two steps: organisation context → 30 questions. Receive a readiness score, risk classification, and AI-powered recommendations.</p>

        <div class="steps">
          <span class="pill" id="stepLabel">Step 1 of 2</span>
          <div class="bar"><div class="fill" id="fill"></div></div>
          <span class="pill pill-blue small">~10–15 min</span>
        </div>

        <form method="post" action="result.php" id="form">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

          <!-- ======== STEP 1: Context ======== -->
          <div id="step1">
            <h2>Organisation context</h2>

            <div class="grid2">
              <div>
                <label>Organisation name</label>
                <input name="company" placeholder="e.g., Acme GmbH" maxlength="120" required>
              </div>

              <div>
                <label>Email address (to receive results)</label>
                <input name="email" type="email" placeholder="name@organisation.eu" maxlength="120" required>
              </div>

              <div>
                <label>Industry / sector</label>
                <select name="industry" required>
                  <option value="">Select…</option>
                  <option>Financial Services</option>
                  <option>Healthcare / MedTech</option>
                  <option>Public Sector / Government</option>
                  <option>Education</option>
                  <option>HR / Recruitment</option>
                  <option>Retail / E-commerce</option>
                  <option>Manufacturing / Industry</option>
                  <option>SaaS / Technology</option>
                  <option>Legal / Professional Services</option>
                  <option>Research / Academia</option>
                  <option>Other</option>
                </select>
              </div>

              <div>
                <label>Organisation size</label>
                <select name="size" required>
                  <option value="">Select…</option>
                  <option>1–10 (Micro)</option>
                  <option>11–50 (Small)</option>
                  <option>51–250 (Medium)</option>
                  <option>250+ (Large)</option>
                </select>
              </div>

              <div>
                <label>Your role in AI</label>
                <select name="ai_role" required>
                  <option value="">Select…</option>
                  <option>AI Provider (develop/supply AI systems)</option>
                  <option>AI Deployer (deploy AI in products/services)</option>
                  <option>AI User (use AI internally)</option>
                  <option>Both Provider and Deployer</option>
                  <option>Not sure yet</option>
                </select>
              </div>

              <div>
                <label>EU market presence</label>
                <select name="eu_presence" required>
                  <option value="">Select…</option>
                  <option>Established in EU / EEA</option>
                  <option>Outside EU but serving EU users</option>
                  <option>Considering EU market</option>
                </select>
              </div>
            </div>

            <div class="btnRow">
              <button type="button" class="btn btnPrimary" id="toStep2">Next: Assessment Questions →</button>
            </div>

            <p class="small" style="margin-top: 10px;">Disclaimer: self-assessment only. Not legal advice or certification.</p>
          </div>

          <!-- ======== STEP 2: Questions ======== -->
          <div id="step2" class="hide">
            <h2>Readiness questions</h2>
            <p class="muted" style="margin-bottom: 16px;">Answer all 30 questions as honestly as possible. There are no right or wrong answers — this helps generate the most useful recommendations.</p>

            <?php foreach ($sections as $sectionName => $questions): ?>
              <div class="sectionHeader">📋 <?= h($sectionName) ?></div>

              <?php foreach ($questions as $id => [$text, $w]): ?>
                <div class="q">
                  <div class="qtitle"><?= h($text) ?></div>
                  <div class="qweight">Weight: <?= (int)$w ?> — <?= $w === 3 ? 'Critical requirement' : 'Important requirement' ?></div>

                  <div class="radioRow">
                    <?php foreach ($options as $val => [$label, $sub]): ?>
                      <label class="opt opt-<?= h($val) ?>">
                        <input type="radio" name="<?= h($id) ?>" value="<?= h($val) ?>" required>
                        <div>
                          <div class="opt-label"><?= h($label) ?></div>
                          <div class="opt-sub"><?= h($sub) ?></div>
                        </div>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>

            <?php endforeach; ?>

            <div class="btnRow" style="margin-top: 24px; justify-content: space-between;">
              <button type="button" class="btn" id="back">← Back</button>
              <button type="submit" class="btn btnPrimary">Get my EU AI Act score →</button>
            </div>

            <p class="small" style="margin-top: 10px; text-align: right;">Results are generated instantly. Self-assessment only — not an audit.</p>
          </div>

        </form>
      </div>

    </div><!-- /formWrap -->

    <div class="footer">
      <p>
        &copy; <?= date('Y') ?> <a href="https://ai-ei.org" target="_blank" rel="noopener">AIEI — AI Ethics and Integrity International Association</a> ·
        <a href="index.php">← Back to home</a>
      </p>
    </div>

  </div><!-- /wrap -->

  <script>
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const toStep2 = document.getElementById('toStep2');
    const back = document.getElementById('back');
    const fill = document.getElementById('fill');
    const stepLabel = document.getElementById('stepLabel');

    function setStep(n) {
      if (n === 1) {
        step1.classList.remove('hide');
        step2.classList.add('hide');
        fill.style.width = '50%';
        stepLabel.textContent = 'Step 1 of 2';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        step1.classList.add('hide');
        step2.classList.remove('hide');
        fill.style.width = '100%';
        stepLabel.textContent = 'Step 2 of 2';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    }

    function step1Valid() {
      const required = step1.querySelectorAll('input[required], select[required]');
      for (const el of required) {
        if (!el.value) return false;
      }
      return true;
    }

    toStep2.addEventListener('click', () => {
      if (!step1Valid()) {
        alert('Please fill in all organisation context fields before continuing.');
        return;
      }
      setStep(2);
    });

    back.addEventListener('click', () => setStep(1));
    setStep(1);
  </script>
</body>
</html>
