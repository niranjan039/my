<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PlantScan — AI Disease Detection</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --green-deep:  #0d2818;
    --green-mid:   #1a4a2e;
    --green-main:  #2d7a47;
    --green-light: #4ade80;
    --green-glow:  #86efac;
    --cream:       #f5f0e8;
    --cream-dark:  #e8e0d0;
    --gold:        #c9a84c;
    --gold-light:  #f0d080;
    --text-dark:   #0d1f0f;
    --text-mid:    #2d4a35;
    --text-soft:   #5a7a62;
    --white:       #ffffff;
    --shadow-green: 0 20px 60px rgba(13,40,24,0.3);
    --shadow-soft:  0 8px 30px rgba(13,40,24,0.15);
  }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--green-deep);
    min-height: 100vh;
    overflow-x: hidden;
    cursor: default;
  }

  /* ── Animated background ── */
  .bg-canvas {
    position: fixed; inset: 0; z-index: 0;
    background:
      radial-gradient(ellipse 80% 60% at 20% 10%, rgba(45,122,71,0.25) 0%, transparent 60%),
      radial-gradient(ellipse 60% 80% at 80% 80%, rgba(26,74,46,0.4) 0%, transparent 60%),
      linear-gradient(160deg, #0a1f12 0%, #0d2818 40%, #0b1c10 100%);
  }
  .bg-canvas::before {
    content:'';
    position:absolute; inset:0;
    background-image:
      radial-gradient(circle 1px at 15% 25%, rgba(74,222,128,0.15) 0%, transparent 100%),
      radial-gradient(circle 1px at 75% 60%, rgba(74,222,128,0.1) 0%, transparent 100%),
      radial-gradient(circle 1px at 45% 80%, rgba(201,168,76,0.1) 0%, transparent 100%);
  }
  .leaf-float {
    position:fixed; pointer-events:none; z-index:0; opacity:0.06; font-size:120px;
    animation: floatLeaf 18s ease-in-out infinite;
  }
  .leaf-float:nth-child(1) { top:-5%; left:-2%; animation-delay:0s; }
  .leaf-float:nth-child(2) { top:60%; right:-3%; font-size:90px; animation-delay:-7s; animation-duration:22s; }
  .leaf-float:nth-child(3) { bottom:-5%; left:40%; font-size:150px; animation-delay:-12s; animation-duration:25s; }
  @keyframes floatLeaf {
    0%,100% { transform: translateY(0) rotate(0deg); }
    33%     { transform: translateY(-30px) rotate(8deg); }
    66%     { transform: translateY(15px) rotate(-5deg); }
  }

  /* ── Layout ── */
  .container {
    position: relative; z-index: 1;
    max-width: 780px; margin: 0 auto;
    padding: 60px 24px 80px;
  }

  /* ── Header ── */
  header { text-align: center; margin-bottom: 56px; animation: fadeDown 0.8s ease both; }
  .logo-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, rgba(45,122,71,0.3), rgba(13,40,24,0.5));
    border: 1px solid rgba(74,222,128,0.25);
    border-radius: 50px; padding: 8px 20px;
    margin-bottom: 28px; backdrop-filter: blur(10px);
  }
  .logo-badge span { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: var(--green-glow); font-weight: 600; }
  .logo-dot { width:6px; height:6px; background: var(--green-light); border-radius:50%; animation: pulse 2s infinite; }
  @keyframes pulse { 0%,100%{opacity:1; transform:scale(1);} 50%{opacity:0.5; transform:scale(1.4);} }

  h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(42px, 8vw, 68px);
    font-weight: 900; line-height: 1.05;
    color: var(--cream);
    letter-spacing: -1px;
  }
  h1 em {
    font-style: normal;
    background: linear-gradient(135deg, var(--green-light), var(--gold-light));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .subtitle {
    margin-top: 16px; font-size: 16px; color: var(--text-soft);
    font-weight: 400; line-height: 1.6; max-width: 480px; margin-inline: auto;
  }

  /* ── Card ── */
  .card {
    background: linear-gradient(145deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border: 1px solid rgba(74,222,128,0.15);
    border-radius: 24px;
    backdrop-filter: blur(20px);
    box-shadow: var(--shadow-green), inset 0 1px 0 rgba(255,255,255,0.08);
    padding: 44px 44px;
    animation: fadeUp 0.8s 0.2s ease both;
  }
  @media (max-width: 600px) { .card { padding: 28px 22px; } }

  /* ── Upload zone ── */
  #step-upload { display: block; }
  #step-predict { display: none; }
  #step-result  { display: none; }

  .upload-zone {
    border: 2px dashed rgba(74,222,128,0.3);
    border-radius: 18px;
    padding: 56px 32px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative; overflow: hidden;
    background: rgba(45,122,71,0.04);
  }
  .upload-zone:hover, .upload-zone.drag-over {
    border-color: var(--green-light);
    background: rgba(74,222,128,0.07);
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(74,222,128,0.1);
  }
  .upload-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, rgba(45,122,71,0.4), rgba(13,40,24,0.6));
    border: 1.5px solid rgba(74,222,128,0.3);
    border-radius: 22px;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; margin: 0 auto 20px;
    transition: transform 0.3s ease;
  }
  .upload-zone:hover .upload-icon { transform: scale(1.08) rotate(-3deg); }
  .upload-title { font-family:'Playfair Display',serif; font-size: 22px; font-weight:700; color: var(--cream); margin-bottom: 8px; }
  .upload-sub { font-size: 14px; color: var(--text-soft); line-height: 1.5; }
  .upload-sub strong { color: var(--green-glow); }
  #file-input { display: none; }

  .btn-browse {
    margin-top: 24px;
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, var(--green-main), #1e5c35);
    color: var(--cream); font-family:'DM Sans',sans-serif;
    font-size: 14px; font-weight: 600; letter-spacing: 0.5px;
    padding: 12px 28px; border-radius: 50px; border: none; cursor: pointer;
    transition: all 0.25s ease; box-shadow: 0 4px 20px rgba(45,122,71,0.4);
  }
  .btn-browse:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(45,122,71,0.5); }

  .format-pills {
    display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;
    margin-top: 20px;
  }
  .pill {
    font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;
    padding: 4px 12px; border-radius: 20px;
    background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.2);
    color: var(--green-glow);
  }

  /* ── Preview ── */
  .preview-wrap {
    border-radius: 16px; overflow: hidden;
    border: 1.5px solid rgba(74,222,128,0.25);
    position: relative; margin-bottom: 24px;
    box-shadow: var(--shadow-soft);
  }
  #preview-img {
    width: 100%; max-height: 340px; object-fit: cover; display: block;
  }
  .preview-overlay {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(13,40,24,0.9));
    padding: 20px 20px 16px;
    display: flex; align-items: center; gap: 12px;
  }
  .file-info { flex: 1; }
  .file-name { font-size: 14px; font-weight: 600; color: var(--cream); }
  .file-size { font-size: 12px; color: var(--text-soft); margin-top: 2px; }
  .check-icon {
    width: 32px; height: 32px; background: var(--green-main); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
  }

  .btn-change {
    background: transparent; border: 1px solid rgba(74,222,128,0.3);
    color: var(--green-glow); font-family:'DM Sans',sans-serif;
    font-size: 13px; font-weight: 500; padding: 8px 18px;
    border-radius: 50px; cursor: pointer; transition: all 0.2s;
    display: inline-block; margin-bottom: 20px;
  }
  .btn-change:hover { background: rgba(74,222,128,0.1); }

  /* ── Predict button ── */
  .btn-predict {
    width: 100%; padding: 18px;
    background: linear-gradient(135deg, #2d7a47 0%, #1e5c35 50%, #2d7a47 100%);
    background-size: 200% 100%;
    color: var(--cream); font-family:'Playfair Display',serif;
    font-size: 20px; font-weight: 700; letter-spacing: 0.5px;
    border: none; border-radius: 16px; cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 32px rgba(45,122,71,0.5);
    display: flex; align-items: center; justify-content: center; gap: 10px;
    position: relative; overflow: hidden;
  }
  .btn-predict::before {
    content:''; position:absolute; inset:0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
    opacity:0; transition: opacity 0.3s;
  }
  .btn-predict:hover { transform: translateY(-3px); box-shadow: 0 14px 40px rgba(45,122,71,0.6); background-position: 100% 0; }
  .btn-predict:hover::before { opacity:1; }
  .btn-predict:active { transform: translateY(0); }

  /* ── Analyzing loader ── */
  .loader-wrap {
    text-align: center; padding: 48px 0; display: none;
  }
  .dna-loader {
    width: 60px; height: 60px; margin: 0 auto 20px;
    border: 3px solid rgba(74,222,128,0.15);
    border-top-color: var(--green-light);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .loader-text { color: var(--green-glow); font-size: 15px; font-weight: 500; }
  .loader-sub  { color: var(--text-soft); font-size: 13px; margin-top: 6px; }

  /* ── Result ── */
  .result-header {
    text-align: center; padding: 28px 0 24px;
    border-bottom: 1px solid rgba(74,222,128,0.1);
    margin-bottom: 28px;
  }
  .disease-emoji { font-size: 52px; display: block; margin-bottom: 10px; }
  .disease-name {
    font-family:'Playfair Display',serif; font-size: 30px; font-weight: 900;
    color: var(--cream); margin-bottom: 6px;
  }
  .severity-badge {
    display: inline-block; padding: 4px 16px; border-radius: 20px;
    font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
    margin-bottom: 14px;
  }
  .confidence-bar-wrap { max-width: 300px; margin: 0 auto; }
  .conf-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-soft); margin-bottom: 6px; }
  .conf-bar { height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; }
  .conf-fill { height: 100%; border-radius: 3px; transition: width 1s ease; background: linear-gradient(90deg, var(--green-main), var(--green-light)); }

  .desc-box {
    background: rgba(45,122,71,0.12); border: 1px solid rgba(74,222,128,0.15);
    border-radius: 14px; padding: 18px 20px; margin-bottom: 28px;
    font-size: 15px; color: var(--cream-dark); line-height: 1.65;
  }

  /* ── Precaution ask ── */
  .precaution-ask {
    background: linear-gradient(135deg, rgba(201,168,76,0.08), rgba(45,122,71,0.08));
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 18px; padding: 28px;
    text-align: center; margin-bottom: 8px;
  }
  .ask-title { font-family:'Playfair Display',serif; font-size: 20px; font-weight:700; color: var(--cream); margin-bottom: 8px; }
  .ask-sub { font-size: 14px; color: var(--text-soft); margin-bottom: 24px; }
  .ask-btns { display: flex; gap: 12px; justify-content: center; }
  .btn-yes {
    flex: 1; max-width: 160px; padding: 14px;
    background: linear-gradient(135deg, var(--green-main), #1e5c35);
    color: var(--cream); font-family:'DM Sans',sans-serif;
    font-size: 15px; font-weight: 700; border: none; border-radius: 14px;
    cursor: pointer; transition: all 0.25s;
    box-shadow: 0 4px 20px rgba(45,122,71,0.4);
  }
  .btn-yes:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(45,122,71,0.5); }
  .btn-no {
    flex: 1; max-width: 160px; padding: 14px;
    background: transparent; border: 1.5px solid rgba(255,255,255,0.15);
    color: var(--text-soft); font-family:'DM Sans',sans-serif;
    font-size: 15px; font-weight: 600; border-radius: 14px;
    cursor: pointer; transition: all 0.25s;
  }
  .btn-no:hover { background: rgba(255,255,255,0.05); color: var(--cream); border-color: rgba(255,255,255,0.25); }

  /* ── Precautions list ── */
  .precautions-section { display: none; }
  .prec-title {
    font-family:'Playfair Display',serif; font-size: 20px; font-weight:700;
    color: var(--cream); margin-bottom: 18px; display: flex; align-items: center; gap: 10px;
  }
  .prec-list { list-style: none; display: flex; flex-direction: column; gap: 12px; }
  .prec-item {
    display: flex; align-items: flex-start; gap: 14px;
    background: rgba(45,122,71,0.08); border: 1px solid rgba(74,222,128,0.1);
    border-radius: 12px; padding: 14px 16px;
    font-size: 14px; color: var(--cream-dark); line-height: 1.5;
    animation: fadeUp 0.4s ease both;
  }
  .prec-num {
    min-width: 24px; height: 24px;
    background: linear-gradient(135deg, var(--green-main), #1e5c35);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--cream); flex-shrink: 0;
  }

  /* ── Reset button ── */
  .btn-reset {
    width: 100%; margin-top: 28px; padding: 15px;
    background: transparent; border: 1.5px solid rgba(74,222,128,0.25);
    color: var(--green-glow); font-family:'DM Sans',sans-serif;
    font-size: 15px; font-weight: 600; border-radius: 14px;
    cursor: pointer; transition: all 0.25s; letter-spacing: 0.3px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .btn-reset:hover { background: rgba(74,222,128,0.08); border-color: rgba(74,222,128,0.5); transform: translateY(-1px); }

  /* ── Popup ── */
  .popup-overlay {
    display: none; position: fixed; inset: 0; z-index: 1000;
    background: rgba(5,15,8,0.85); backdrop-filter: blur(8px);
    align-items: center; justify-content: center;
    animation: fadeIn 0.2s ease;
  }
  .popup-overlay.active { display: flex; }
  .popup-box {
    background: linear-gradient(145deg, rgba(26,74,46,0.95), rgba(13,40,24,0.98));
    border: 1px solid rgba(74,222,128,0.25);
    border-radius: 24px; padding: 40px 36px; max-width: 380px; width: 90%;
    text-align: center; box-shadow: 0 30px 80px rgba(0,0,0,0.6);
    animation: popIn 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
  }
  .popup-icon { font-size: 54px; display: block; margin-bottom: 16px; }
  .popup-title { font-family:'Playfair Display',serif; font-size: 24px; font-weight:900; color: var(--cream); margin-bottom: 10px; }
  .popup-msg { font-size: 15px; color: var(--text-soft); line-height: 1.6; margin-bottom: 28px; }
  .popup-btn {
    padding: 13px 36px; border-radius: 50px; border: none; cursor: pointer;
    font-family:'DM Sans',sans-serif; font-size: 15px; font-weight: 700;
    transition: all 0.25s;
  }
  .popup-btn.ok { background: linear-gradient(135deg, var(--green-main), #1e5c35); color: var(--cream); box-shadow: 0 4px 20px rgba(45,122,71,0.5); }
  .popup-btn.ok:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(45,122,71,0.6); }
  .popup-btn.thankyou { background: linear-gradient(135deg, var(--gold), #a07828); color: var(--text-dark); }

  /* ── Misc ── */
  .divider { height:1px; background: rgba(74,222,128,0.1); margin: 28px 0; }

  @keyframes fadeDown { from{opacity:0;transform:translateY(-20px);} to{opacity:1;transform:none;} }
  @keyframes fadeUp   { from{opacity:0;transform:translateY(20px);}  to{opacity:1;transform:none;} }
  @keyframes fadeIn   { from{opacity:0;} to{opacity:1;} }
  @keyframes popIn    { from{opacity:0;transform:scale(0.7);} to{opacity:1;transform:scale(1);} }
</style>
</head>
<body>

<div class="bg-canvas"></div>
<div class="leaf-float">🌿</div>
<div class="leaf-float">🍃</div>
<div class="leaf-float">🌱</div>

<div class="container">

  <!-- Header -->
  <header>
    <div class="logo-badge">
      <div class="logo-dot"></div>
      <span>AI-Powered Analysis</span>
    </div>
    <h1>Plant<em>Scan</em></h1>
    <p class="subtitle">Upload a leaf photo and our trained AI model will instantly detect plant diseases and provide expert treatment guidance.</p>
  </header>

  <!-- Main Card -->
  <div class="card">

    <!-- STEP 1: Upload -->
    <div id="step-upload">
      <div class="upload-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
        <div class="upload-icon">🌿</div>
        <div class="upload-title">Drop your leaf photo here</div>
        <div class="upload-sub">
          Drag & drop or click to browse<br>
          <strong>JPG, PNG, WEBP</strong> supported
        </div>
        <button class="btn-browse" onclick="event.stopPropagation(); document.getElementById('file-input').click()">
          📁 Browse Files
        </button>
        <div class="format-pills">
          <span class="pill">JPG</span>
          <span class="pill">PNG</span>
          <span class="pill">WEBP</span>
          <span class="pill">GIF</span>
          <span class="pill">BMP</span>
        </div>
      </div>
      <input type="file" id="file-input" accept="image/*">
    </div>

    <!-- STEP 2: Preview + Predict -->
    <div id="step-predict">
      <div class="preview-wrap">
        <img id="preview-img" src="" alt="Uploaded plant">
        <div class="preview-overlay">
          <div class="file-info">
            <div class="file-name" id="file-name-display">leaf.jpg</div>
            <div class="file-size" id="file-size-display">0 KB</div>
          </div>
          <div class="check-icon">✓</div>
        </div>
      </div>
      <button class="btn-change" onclick="resetToUpload()">← Change image</button>

      <!-- Loader (hidden by default) -->
      <div class="loader-wrap" id="loader">
        <div class="dna-loader"></div>
        <div class="loader-text">Analyzing your plant...</div>
        <div class="loader-sub">Running AI disease detection model</div>
      </div>

      <button class="btn-predict" id="btn-predict" onclick="runPrediction()">
        🔬 Analyze & Detect Disease
      </button>
    </div>

    <!-- STEP 3: Result -->
    <div id="step-result">
      <div class="result-header">
        <span class="disease-emoji" id="res-emoji">🌿</span>
        <div class="disease-name" id="res-name">Loading…</div>
        <div class="severity-badge" id="res-severity"></div>
        <div class="confidence-bar-wrap">
          <div class="conf-label">
            <span>Confidence</span>
            <span id="res-conf-pct">0%</span>
          </div>
          <div class="conf-bar"><div class="conf-fill" id="res-conf-fill" style="width:0%"></div></div>
        </div>
      </div>

      <div class="desc-box" id="res-desc"></div>

      <!-- Ask for precautions -->
      <div class="precaution-ask" id="prec-ask">
        <div class="ask-title">🌱 Want Treatment Advice?</div>
        <div class="ask-sub">Get expert precautions and treatment steps for this condition</div>
        <div class="ask-btns">
          <button class="btn-yes" onclick="showPrecautions()">✓ Yes, Show Me</button>
          <button class="btn-no"  onclick="showThankyou()">✗ No Thanks</button>
        </div>
      </div>

      <!-- Precautions list (hidden until Yes) -->
      <div class="precautions-section" id="prec-section">
        <div class="divider"></div>
        <div class="prec-title">💊 Treatment & Precautions</div>
        <ul class="prec-list" id="prec-list"></ul>
      </div>

      <button class="btn-reset" onclick="resetAll()">↺ &nbsp;Scan Another Plant</button>
    </div>

  </div><!-- .card -->
</div><!-- .container -->

<!-- Error Popup -->
<div class="popup-overlay" id="popup-error">
  <div class="popup-box">
    <span class="popup-icon">⚠️</span>
    <div class="popup-title" id="popup-error-title">Invalid File</div>
    <div class="popup-msg" id="popup-error-msg">Only image files are allowed.</div>
    <button class="popup-btn ok" onclick="closePopup('popup-error')">Got It</button>
  </div>
</div>

<!-- Thank You Popup -->
<div class="popup-overlay" id="popup-thankyou">
  <div class="popup-box">
    <span class="popup-icon">💚</span>
    <div class="popup-title">Thank You!</div>
    <div class="popup-msg">Hope the diagnosis was helpful. Take care of your plants! 🌱</div>
    <button class="popup-btn thankyou" onclick="closeAndReset()">Start Over</button>
  </div>
</div>

<script>
const API_URL = 'http://localhost:5000/predict';

let currentFile = null;
let currentResult = null;

// ── File input handling ──────────────────────────────────────────────────────
document.getElementById('file-input').addEventListener('change', function(e) {
  handleFile(e.target.files[0]);
});

// Drag & drop
const dropZone = document.getElementById('drop-zone');
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', ()=> dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('drag-over');
  if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
});

function handleFile(file) {
  if (!file) return;

  // Validate: only image types
  const allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif','image/bmp'];
  if (!allowed.includes(file.type.toLowerCase())) {
    let ext = file.name.split('.').pop().toUpperCase();
    showError(
      `❌ ${ext} File Not Allowed`,
      `Only image files (JPG, PNG, WEBP, GIF, BMP) can be analyzed.\n\nPDF, video, and document files are not supported.`
    );
    // reset input
    document.getElementById('file-input').value = '';
    return;
  }

  currentFile = file;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('preview-img').src = e.target.result;
    document.getElementById('file-name-display').textContent = file.name;
    document.getElementById('file-size-display').textContent = formatSize(file.size);
    show('step-upload', false);
    show('step-predict', true);
    show('step-result', false);
  };
  reader.readAsDataURL(file);
}

// ── Prediction ───────────────────────────────────────────────────────────────
async function runPrediction() {
  if (!currentFile) return;

  document.getElementById('btn-predict').style.display = 'none';
  document.getElementById('loader').style.display = 'block';

  const formData = new FormData();
  formData.append('image', currentFile);

  try {
    const resp = await fetch(API_URL, { method: 'POST', body: formData });
    const data = await resp.json();

    if (!resp.ok || data.error) {
      throw new Error(data.error || 'Prediction failed');
    }

    currentResult = data;
    renderResult(data);

    document.getElementById('loader').style.display = 'none';
    show('step-predict', false);
    show('step-result', true);

  } catch (err) {
    document.getElementById('loader').style.display = 'none';
    document.getElementById('btn-predict').style.display = 'flex';
    showError('Analysis Failed', `Could not connect to the AI model.\n\nMake sure the Python API is running:\npython api.py\n\nError: ${err.message}`);
  }
}

function renderResult(d) {
  document.getElementById('res-emoji').textContent = d.emoji;
  document.getElementById('res-name').textContent  = d.label;
  document.getElementById('res-desc').textContent  = d.description;
  document.getElementById('res-conf-pct').textContent = d.confidence + '%';

  const sevBadge = document.getElementById('res-severity');
  sevBadge.textContent = 'Severity: ' + d.severity;
  sevBadge.style.background = d.color + '22';
  sevBadge.style.color      = d.color;
  sevBadge.style.border     = '1px solid ' + d.color + '44';

  // Animate confidence bar
  setTimeout(() => {
    document.getElementById('res-conf-fill').style.width = d.confidence + '%';
  }, 200);

  // Build precautions list (hidden until Yes)
  const list = document.getElementById('prec-list');
  list.innerHTML = '';
  d.precautions.forEach((p, i) => {
    const li = document.createElement('li');
    li.className = 'prec-item';
    li.style.animationDelay = (i * 0.07) + 's';
    li.innerHTML = `<div class="prec-num">${i+1}</div><span>${p}</span>`;
    list.appendChild(li);
  });

  // Reset precaution UI
  document.getElementById('prec-ask').style.display = 'block';
  document.getElementById('prec-section').style.display = 'none';
}

// ── Precautions ──────────────────────────────────────────────────────────────
function showPrecautions() {
  document.getElementById('prec-ask').style.display = 'none';
  document.getElementById('prec-section').style.display = 'block';
}

function showThankyou() {
  document.getElementById('popup-thankyou').classList.add('active');
}

function closeAndReset() {
  closePopup('popup-thankyou');
  setTimeout(resetAll, 300);
}

// ── Navigation ───────────────────────────────────────────────────────────────
function resetToUpload() {
  currentFile = null;
  document.getElementById('file-input').value = '';
  document.getElementById('btn-predict').style.display = 'flex';
  document.getElementById('loader').style.display = 'none';
  show('step-upload', true);
  show('step-predict', false);
  show('step-result', false);
}

function resetAll() {
  currentFile = null; currentResult = null;
  document.getElementById('file-input').value = '';
  document.getElementById('res-conf-fill').style.width = '0%';
  document.getElementById('btn-predict').style.display = 'flex';
  document.getElementById('loader').style.display = 'none';
  show('step-upload', true);
  show('step-predict', false);
  show('step-result', false);
}

// ── Popup helpers ────────────────────────────────────────────────────────────
function showError(title, msg) {
  document.getElementById('popup-error-title').textContent = title;
  document.getElementById('popup-error-msg').textContent   = msg;
  document.getElementById('popup-error').classList.add('active');
}

function closePopup(id) {
  document.getElementById(id).classList.remove('active');
}

// ── Utilities ────────────────────────────────────────────────────────────────
function show(id, visible) {
  document.getElementById(id).style.display = visible ? 'block' : 'none';
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/1048576).toFixed(1) + ' MB';
}

// Close popups on overlay click
document.querySelectorAll('.popup-overlay').forEach(el => {
  el.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
  });
});
</script>
</body>
</html>
