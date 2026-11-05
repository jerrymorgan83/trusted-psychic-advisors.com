/* I Ching Counsel: три монети, шість кидків, гексаграма (розрахунок у браузері) + AI-нотатка від одного з трьох голосів.
   Конфіг із <script id="ic-config">. Worker: /api/i-ching/ (локально — порт 8866 за наявності порту в адресі). */
(function () {
  'use strict';
  var root = document.getElementById('ic-app');
  var cfgEl = document.getElementById('ic-config');
  if (!root || !cfgEl) return;
  var CFG = JSON.parse(cfgEl.textContent);
  var ENDPOINT = (window.TPA_IC && window.TPA_IC.endpoint) || '/api/i-ching/';
  // Лише в локальній розробці (є порт у адресі) — локальний Worker; на проді порту немає.
  if (location.port) ENDPOINT = location.protocol + '//' + location.hostname + ':8866' + ENDPOINT;

  var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var S = { voice: CFG.voices[0].id, lines: [], tosses: [], busy: false, rot: [0, 0, 0] };

  function esc(t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function rnd3() {
    var a = new Uint8Array(3);
    (window.crypto || window.msCrypto).getRandomValues(a);
    return [a[0] & 1, a[1] & 1, a[2] & 1]; // 1 = орел (3), 0 = решка (2)
  }
  function lineValue(c) { return c.reduce(function (s, h) { return s + (h ? 3 : 2); }, 0); }
  function isYang(v) { return v === 7 || v === 9; }
  function isMoving(v) { return v === 6 || v === 9; }
  function hexOf(yang) {
    var key = function (a) { return a.map(function (y) { return y ? '1' : '0'; }).join(''); };
    var lo = CFG.bits.indexOf(key(yang.slice(0, 3))), up = CFG.bits.indexOf(key(yang.slice(3, 6)));
    var n = CFG.matrix[lo][up];
    return { n: n, name: CFG.hexagrams[n - 1], lower: CFG.trigrams[lo], upper: CFG.trigrams[up], yang: yang };
  }
  var KIND = { 6: 'old yin, changing', 7: 'young yang', 8: 'young yin', 9: 'old yang, changing' };

  function hexSvg(yang, moving) {
    var h = '<svg viewBox="0 0 120 112" class="ic-hex-svg" role="img" aria-hidden="true" focusable="false">';
    for (var i = 0; i < 6; i++) {
      var y = 100 - i * 18, mv = moving && moving[i];
      var cls = mv ? 'ic-bar ic-bar--moving' : 'ic-bar';
      if (yang[i]) h += '<rect class="' + cls + '" x="6" y="' + (y - 5) + '" width="108" height="10" rx="2"/>';
      else h += '<rect class="' + cls + '" x="6" y="' + (y - 5) + '" width="44" height="10" rx="2"/><rect class="' + cls + '" x="70" y="' + (y - 5) + '" width="44" height="10" rx="2"/>';
    }
    return h + '</svg>';
  }

  var voicesHtml = CFG.voices.map(function (v, i) {
    return '<label class="ic-voice"><input type="radio" name="ic-voice" value="' + v.id + '"' + (i === 0 ? ' checked' : '') + '>' +
      '<span class="ic-voice-card"><span class="ic-voice-mark" aria-hidden="true">' + esc(v.name.charAt(0)) + '</span>' +
      '<strong>' + esc(v.name) + '</strong><em>' + esc(v.role) + '</em><small>' + esc(v.blurb) + '</small><span class="ic-ai-tag">AI voice</span></span></label>';
  }).join('');

  var coinHtml = [0, 1, 2].map(function (i) {
    return '<div class="ic-coin" data-i="' + i + '"><div class="ic-coin-in"><span class="ic-face ic-face--h" aria-hidden="true"><i></i><b>3</b></span><span class="ic-face ic-face--t" aria-hidden="true"><i></i><b>2</b></span></div></div>';
  }).join('');

  root.innerHTML =
    '<div class="ic-grid">' +
      '<section class="ic-ask" aria-label="Your question">' +
        '<h2 class="ic-h"><span class="ic-n">1</span>Hold one question</h2>' +
        '<label class="ic-label" for="ic-q">What would you like to look at more closely?</label>' +
        '<textarea id="ic-q" maxlength="300" rows="3" placeholder="For example: Should I take the new role, or stay where I am another year?"></textarea>' +
        '<div class="ic-qmeta"><span id="ic-qcount">0 / 300</span><span>Open questions work best. Please leave out names and contact details.</span></div>' +
        '<h2 class="ic-h"><span class="ic-n">2</span>Pick a writing voice</h2>' +
        '<div class="ic-voices" role="radiogroup" aria-label="Writing voice">' + voicesHtml + '</div>' +
        '<p class="ic-fine">The coin tosses are plain random numbers from your own browser. The AI voice only writes the note afterwards.</p>' +
      '</section>' +
      '<section class="ic-table" aria-label="Coin tray">' +
        '<h2 class="ic-h ic-h--inv"><span class="ic-n">3</span>Toss six times</h2>' +
        '<div class="ic-tray" id="ic-tray">' + coinHtml + '</div>' +
        '<p class="ic-toss-status" id="ic-tosslabel" role="status" aria-live="polite">Write your question, then toss.</p>' +
        '<div class="ic-actions">' +
          '<button type="button" class="ic-btn" id="ic-toss">Toss the coins</button>' +
          '<button type="button" class="ic-btn ic-btn--ghost" id="ic-all">Toss all six</button>' +
          '<button type="button" class="ic-btn ic-btn--ghost" id="ic-reset" hidden>Start over</button>' +
        '</div>' +
        '<ol class="ic-stack" id="ic-stack" aria-label="Lines cast so far, bottom to top"></ol>' +
      '</section>' +
    '</div>' +
    '<section class="ic-result" id="ic-result" hidden aria-live="polite"></section>';

  var q = root.querySelector('#ic-q'), qcount = root.querySelector('#ic-qcount');
  var tray = root.querySelector('#ic-tray'), stack = root.querySelector('#ic-stack'), label = root.querySelector('#ic-tosslabel');
  var btnToss = root.querySelector('#ic-toss'), btnAll = root.querySelector('#ic-all'), btnReset = root.querySelector('#ic-reset');
  var result = root.querySelector('#ic-result');

  q.addEventListener('input', function () { qcount.textContent = q.value.length + ' / 300'; });
  root.addEventListener('change', function (e) { if (e.target.name === 'ic-voice') S.voice = e.target.value; });

  function qok() { return q.value.trim().length >= 3; }

  function renderStack() {
    var h = '';
    for (var i = 0; i < 6; i++) {
      var v = S.lines[i];
      if (v === undefined) h += '<li class="ic-slot ic-slot--empty" aria-label="Line ' + (i + 1) + ', not cast yet"><span class="ic-ln">' + (i + 1) + '</span></li>';
      else {
        var y = isYang(v), m = isMoving(v);
        h += '<li class="ic-slot' + (m ? ' is-moving' : '') + (i === S.lines.length - 1 ? ' is-new' : '') + '"><span class="ic-ln">' + (i + 1) + '</span>' +
          '<span class="ic-line ' + (y ? 'yang' : 'yin') + '" aria-hidden="true"><i></i><i></i></span>' +
          '<span class="ic-lv">' + (m ? (y ? '○ ' : '× ') : '') + esc(KIND[v]) + '</span></li>';
      }
    }
    stack.innerHTML = h;
  }

  function spinTo(faces) {
    var els = tray.querySelectorAll('.ic-coin-in');
    for (var i = 0; i < 3; i++) {
      // орел — кратне 360, решка — 180 за модулем 360; щоразу ще ≥2 повних оберти
      S.rot[i] = (Math.floor(S.rot[i] / 360) + 3) * 360 + (faces[i] ? 0 : 180);
      els[i].style.transitionDelay = (i * 90) + 'ms';
      els[i].style.transform = 'rotateX(' + S.rot[i] + 'deg)';
      var c = tray.children[i];
      c.classList.remove('is-hop'); void c.offsetWidth; c.classList.add('is-hop');
    }
  }

  function toss() {
    if (S.busy || S.lines.length >= 6) return Promise.resolve();
    if (!qok()) { label.textContent = 'Please write a short question first (at least 3 characters).'; q.focus(); return Promise.resolve(); }
    q.readOnly = true;
    S.busy = true;
    var faces = rnd3(), v = lineValue(faces);
    S.tosses.push({ faces: faces, v: v });
    spinTo(faces);
    label.textContent = 'Tossing line ' + (S.lines.length + 1) + ' of 6.';
    return new Promise(function (res) {
      setTimeout(function () {
        S.lines.push(v);
        renderStack();
        label.textContent = 'Line ' + S.lines.length + ': ' + faces.map(function (h) { return h ? 'heads' : 'tails'; }).join(', ') + ' makes ' + v + ', ' + KIND[v] + '.';
        S.busy = false;
        btnReset.hidden = false;
        if (S.lines.length === 6) finish(); else btnToss.textContent = 'Toss line ' + (S.lines.length + 1);
        res();
      }, REDUCED ? 30 : 950);
    });
  }

  function tossAll() {
    if (S.busy) return;
    (function next() {
      if (S.lines.length >= 6) return;
      toss().then(function () { if (qok() && S.lines.length < 6) setTimeout(next, REDUCED ? 0 : 250); });
    })();
  }

  function finish() {
    btnToss.disabled = true; btnAll.disabled = true;
    btnToss.textContent = 'Cast complete';
    var yang = S.lines.map(isYang), mv = S.lines.map(isMoving);
    var p = hexOf(yang), moving = mv.map(function (m, i) { return m ? i + 1 : 0; }).filter(Boolean);
    var rel = moving.length ? hexOf(yang.map(function (y, i) { return mv[i] ? !y : y; })) : null;
    var rows = S.tosses.map(function (t, i) {
      return '<tr><td>' + (i + 1) + '</td><td>' + t.faces.map(function (h) { return h ? 'H' : 'T'; }).join(' ') + '</td><td>' + t.v + '</td><td>' + esc(KIND[t.v]) + '</td></tr>';
    }).join('');
    result.hidden = false;
    result.innerHTML =
      '<div class="ic-cards">' +
        '<article class="ic-hexcard"><span class="ic-kicker">Your hexagram</span>' + hexSvg(p.yang, mv) +
          '<h3>No. ' + p.n + ' &middot; ' + esc(p.name) + '</h3><p>' + esc(p.upper) + ' above, ' + esc(p.lower) + ' below</p></article>' +
        (rel ? '<div class="ic-arrow" aria-hidden="true">&rarr;</div><article class="ic-hexcard ic-hexcard--rel"><span class="ic-kicker">It moves toward</span>' + hexSvg(rel.yang, null) +
          '<h3>No. ' + rel.n + ' &middot; ' + esc(rel.name) + '</h3><p>' + esc(rel.upper) + ' above, ' + esc(rel.lower) + ' below</p></article>' :
          '<article class="ic-hexcard ic-hexcard--steady"><span class="ic-kicker">A steady cast</span><p class="ic-steady">No line is changing, so there is no second hexagram. Traditionally that reads as a situation holding its shape for now.</p></article>') +
      '</div>' +
      '<p class="ic-moving">' + (moving.length ? 'Changing line' + (moving.length > 1 ? 's' : '') + ': ' + moving.join(', ') + ' (counted from the bottom).' : '') + '</p>' +
      '<details class="ic-work"><summary>Show your work</summary><table><thead><tr><th>Line</th><th>Coins</th><th>Sum</th><th>Meaning</th></tr></thead><tbody>' + rows + '</tbody></table>' +
        '<p class="ic-fine">Heads counts 3, tails counts 2. A total of 6 or 9 is a changing line; 7 and 9 are solid, 6 and 8 are broken. Lines are stacked from the bottom; the lower three lines form the lower trigram and the upper three the upper trigram.</p></details>' +
      '<div class="ic-note-box"><div class="ic-note-head"><div><span class="ic-kicker">Counsel note</span><h3>Ask for a written reflection</h3></div><span class="ic-ai-tag ic-ai-tag--lg">AI-written</span></div>' +
        '<p class="ic-fine">A short note in the voice you picked. It is a writing aid for reflection, not a person and not a prediction. Traditions word the hexagram images differently.</p>' +
        '<div class="ic-actions"><button type="button" class="ic-btn" id="ic-note-btn">Write my counsel note</button></div>' +
        '<div class="ic-note" id="ic-note" tabindex="-1" aria-live="polite"></div><p class="ic-status" id="ic-status" role="status"></p>' +
        '<div class="ic-actions ic-actions--after" id="ic-after" hidden><button type="button" class="ic-btn ic-btn--ghost" id="ic-copy">Copy note</button><button type="button" class="ic-btn ic-btn--ghost" id="ic-again">Write it again</button></div>' +
      '</div>';
    S.hex = p; S.rel = rel;
    result.querySelector('#ic-note-btn').addEventListener('click', writeNote);
    result.scrollIntoView({ behavior: REDUCED ? 'auto' : 'smooth', block: 'start' });
  }

  function render(text) {
    var safe = esc(text).replace(/\*\*([^*]+?)\*\*/g, '<strong>$1</strong>');
    return safe.split(/\n{2,}/).map(function (p) { return '<p>' + p.replace(/\n/g, '<br>') + '</p>'; }).join('');
  }

  function request(onText) {
    var payload = { voice: S.voice, question: q.value.trim().slice(0, 300), lines: S.lines };
    return fetch(ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }).then(function (res) {
      if (!res.ok) return res.json().catch(function () { return {}; }).then(function (j) { throw new Error(j.error || 'The note is unavailable right now.'); });
      var reader = res.body.getReader(), dec = new TextDecoder(), buf = '', full = '', cut = false;
      function pump() {
        return reader.read().then(function (r) {
          if (r.value) buf += dec.decode(r.value, { stream: !r.done });
          var idx;
          while ((idx = buf.indexOf('\n')) >= 0) {
            var line = buf.slice(0, idx).trim(); buf = buf.slice(idx + 1);
            if (line.indexOf('data:') !== 0) continue;
            var d = line.slice(5).trim();
            if (d === '[DONE]') continue;
            try { var j = JSON.parse(d); if (typeof j.t === 'string') { full += j.t; onText(full); } if (j.cut) cut = true; } catch (e) { /* неповний рядок */ }
          }
          if (r.done) return { text: full, cut: cut || !/Take to a person you trust[\s\S]{40,}/.test(full) };
          return pump();
        });
      }
      return pump();
    });
  }

  function writeNote() {
    var btn = result.querySelector('#ic-note-btn'), note = result.querySelector('#ic-note'), status = result.querySelector('#ic-status'), after = result.querySelector('#ic-after');
    btn.disabled = true; after.hidden = true; note.innerHTML = ''; status.textContent = 'Writing your note...';
    var n = 0;
    function go() {
      return request(function (t) { note.innerHTML = render(t); }).then(function (r) {
        // обірваний потік — автоматично пробуємо ще (до 3 разів)
        if (r.cut && n < 3) { n++; status.textContent = 'Finishing the note...'; return go(); }
        if (r.cut && !r.text) throw new Error('The note is unavailable right now. Please try again in a moment.');
        status.textContent = r.cut ? 'This note ended early. You can write it again.' : '';
        after.hidden = false; btn.hidden = true;
        note.focus();
      });
    }
    go().catch(function (err) {
      status.textContent = err.message || 'The note is unavailable right now. Please try again in a moment.';
      btn.disabled = false;
    });
  }

  result.addEventListener('click', function (e) {
    if (e.target.id === 'ic-again') { var b = result.querySelector('#ic-note-btn'); b.hidden = false; writeNote(); }
    if (e.target.id === 'ic-copy') {
      var t = result.querySelector('#ic-note').innerText + '\n\n(AI-written reflection from I Ching Counsel. Not a prediction.)';
      var st = result.querySelector('#ic-status');
      (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { st.textContent = 'Copied.'; }, function () { st.textContent = 'Copy is not available in this browser.'; });
    }
  });

  function reset() {
    S.lines = []; S.tosses = []; S.busy = false;
    q.readOnly = false; btnToss.disabled = false; btnAll.disabled = false; btnToss.textContent = 'Toss the coins';
    btnReset.hidden = true; result.hidden = true; result.innerHTML = '';
    label.textContent = 'Write your question, then toss.';
    renderStack();
    q.focus();
  }

  btnToss.addEventListener('click', toss);
  btnAll.addEventListener('click', tossAll);
  btnReset.addEventListener('click', reset);
  renderStack();
})();
