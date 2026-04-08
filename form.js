document.addEventListener('DOMContentLoaded', function () {
  let debounceTimer = null;
  document.getElementById("codigo").addEventListener("input", function () {
    const codigo = this.value.trim();
    const indicator = document.getElementById("codigo-status");
    if (!codigo || codigo.length < 5) {
      indicator.textContent = "";
      indicator.className = "";
      return;
    }
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      fetch("backend/buscar_usuario.php?codigo=" + encodeURIComponent(codigo))
        .then(res => res.json())
        .then(data => {
          if (data.existe) {
            indicator.textContent = "✓ Código encontrado";
            indicator.className = "status-ok";
            const nombre = document.getElementById('nombre');
            nombre.value = data.nombre;
            nombre.readOnly = true;
            nombre.style.color = '#333'; 
            ['correo', 'telefono'].forEach(campo => {
              const el = document.getElementById(campo);
              el.value = data[campo];
              el.readOnly = true;
              el.style.color = '#bbb';
              el.style.letterSpacing = '2px';
            });
            const proyecto = document.getElementById('proyecto');
            proyecto.value = data.proyecto;
            proyecto.disabled = true;
            proyecto.style.color = '#bbb';
          }
          else {
            indicator.textContent = "✗ Código no registrado";
            indicator.className = "status-err";

            ['nombre','correo','telefono','proyecto'].forEach(campo => {
              const el = document.getElementById(campo);
              el.value = '';
              el.readOnly = false;
              el.style.color = '';
              el.style.letterSpacing = '';
          });
          document.getElementById('proyecto').disabled = false; 
          }
        })
        .catch(() => {
          indicator.textContent = "⚠ Error de conexión";
          indicator.className = "status-err";
        });
    }, 400);
  });

});

const GROUP_IDS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
const BASE_TEAMS = {
  A: ['México', 'Sudáfrica', 'Corea del Sur', 'República Checa'],
  B: ['Canadá', 'Bosnia & Herzegovina', 'Catar', 'Suiza'],
  C: ['Brasil', 'Marruecos', 'Haití', 'Escocia'],
  D: ['Estados Unidos', 'Paraguay', 'Australia', 'Turquía'],
  E: ['Alemania', 'Curazao', 'Costa de Marfil', 'Ecuador'],
  F: ['Países Bajos', 'Japón', 'Suecia', 'Túnez'],
  G: ['Bélgica', 'Egipto', 'Irán', 'Nueva Zelanda'],
  H: ['España', 'Cabo Verde', 'Arabia Saudita', 'Uruguay'],
  I: ['Francia', 'Senegal', 'Irak', 'Noruega'],
  J: ['Argentina', 'Argelia', 'Austria', 'Jordania'],
  K: ['Portugal', 'República Democrática del Congo', 'Uzbekistán', 'Colombia'],
  L: ['Inglaterra', 'Croacia', 'Ghana', 'Panamá']
};
const GROUP_TEAMS = JSON.parse(JSON.stringify(BASE_TEAMS));

const FLAGS = {
  'México': 'mx', 'Sudáfrica': 'za', 'Corea del Sur': 'kr', 'República Checa' : 'cz', 'Canadá': 'ca', 'Bosnia & Herzegovina': 'ba', 
  'Catar': 'qa', 'Suiza': 'ch', 'Brasil': 'br', 'Marruecos': 'ma', 'Haití': 'ht', 'Escocia': 'gb-sct', 'Estados Unidos': 'us',
  'Paraguay': 'py', 'Australia': 'au', 'Turquía' : 'tr', 'Alemania': 'de', 'Curazao': 'cw', 'Costa de Marfil': 'ci',
  'Ecuador': 'ec', 'Países Bajos': 'nl', 'Japón': 'jp', 'Suecia' : 'se', 'Túnez': 'tn', 'Bélgica': 'be', 'Egipto': 'eg',
  'Irán': 'ir', 'Nueva Zelanda': 'nz', 'España': 'es', 'Cabo Verde': 'cv', 'Arabia Saudita': 'sa',
  'Uruguay': 'uy', 'Francia': 'fr', 'Senegal': 'sn', 'Irak' : 'iq', 'Noruega': 'no', 'Argentina': 'ar', 'Argelia': 'dz',
  'Austria': 'at', 'Jordania': 'jo', 'Portugal': 'pt','República Democrática del Congo': 'cd', 'Uzbekistán': 'uz', 'Colombia': 'co',
  'Inglaterra': 'gb-eng', 'Croacia': 'hr', 'Ghana': 'gh', 'Panamá': 'pa'
};

const POOL_TEAMS = [
  { name: 'México', code: 'mx' }, { name: 'Sudáfrica', code: 'za' }, { name: 'República Checa', code: 'cz' }, { name: 'Corea del Sur', code: 'kr' },
  { name: 'Canadá', code: 'ca' }, { name: 'Bosnia & Herzegovina', code: 'ba' }, { name: 'Suiza', code: 'ch' }, { name: 'Catar', code: 'qa' },                 
  { name: 'Brasil', code: 'br' }, { name: 'Marruecos', code: 'ma' }, { name: 'Haití', code: 'ht' }, { name: 'Escocia', code: 'gb-sct' },
  { name: 'Estados Unidos', code: 'us' }, { name: 'Paraguay', code: 'py' }, { name: 'Australia', code: 'au' }, { name: 'Turquía', code: 'tr' },               
  { name: 'Alemania', code: 'de' }, { name: 'Curazao', code: 'cw' }, { name: 'Costa de Marfil', code: 'ci' }, { name: 'Ecuador', code: 'ec' },
  { name: 'Países Bajos', code: 'nl' }, { name: 'Japón', code: 'jp' }, { name: 'Suecia', code: 'se' }, { name: 'Túnez', code: 'tn' },
  { name: 'Bélgica', code: 'be' }, { name: 'Egipto', code: 'eg' }, { name: 'Irán', code: 'ir' }, { name: 'Nueva Zelanda', code: 'nz' },
  { name: 'España', code: 'es' }, { name: 'Cabo Verde', code: 'cv' }, { name: 'Arabia Saudita', code: 'sa' }, { name: 'Uruguay', code: 'uy' },
  { name: 'Francia', code: 'fr' }, { name: 'Senegal', code: 'sn' }, { name: 'Irak', code: 'iq' }, { name: 'Noruega', code: 'no' },
  { name: 'Argentina', code: 'ar' }, { name: 'Argelia', code: 'dz' }, { name: 'Austria', code: 'at' }, { name: 'Jordania', code: 'jo' },
  { name: 'Portugal', code: 'pt' }, { name: 'República Democrática del Congo', code: 'cd' }, { name: 'Uzbekistán', code: 'uz' }, { name: 'Colombia', code: 'co' },
  { name: 'Inglaterra', code: 'gb-eng' }, { name: 'Croacia', code: 'hr' }, { name: 'Ghana', code: 'gh' }, { name: 'Panamá', code: 'pa' }
];

const BRACKET = {
  R32: [
    { id: 'R32-1', num: 73, slot1: '2A', slot2: '2B' }, { id: 'R32-2', num: 74, slot1: '1E', thirds: ['A', 'B', 'C', 'D', 'F'] },
    { id: 'R32-3', num: 75, slot1: '1F', slot2: '2C' }, { id: 'R32-4', num: 76, slot1: '1C', slot2: '2F' },
    { id: 'R32-5', num: 77, slot1: '1I', thirds: ['C', 'D', 'F', 'G', 'H'] }, { id: 'R32-6', num: 78, slot1: '2E', slot2: '2I' },
    { id: 'R32-7', num: 79, slot1: '1A', thirds: ['C', 'E', 'F', 'H', 'I'] }, { id: 'R32-8', num: 80, slot1: '1L', thirds: ['E', 'H', 'I', 'J', 'K'] },
    { id: 'R32-9', num: 81, slot1: '1D', thirds: ['B', 'E', 'F', 'I', 'J'] }, { id: 'R32-10', num: 82, slot1: '1G', thirds: ['A', 'E', 'H', 'I', 'J'] },
    { id: 'R32-11', num: 83, slot1: '2K', slot2: '2L' }, { id: 'R32-12', num: 84, slot1: '1H', slot2: '2J' },
    { id: 'R32-13', num: 85, slot1: '1B', thirds: ['E', 'F', 'G', 'I', 'J'] }, { id: 'R32-14', num: 86, slot1: '1J', slot2: '2H' },
    { id: 'R32-15', num: 87, slot1: '1K', thirds: ['D', 'E', 'I', 'J', 'L'] }, { id: 'R32-16', num: 88, slot1: '2D', slot2: '2G' }
  ],
  R16: [
    { id: 'R16-1', num: 89, from1: 'R32-2', from2: 'R32-5' }, { id: 'R16-2', num: 90, from1: 'R32-1', from2: 'R32-3' },
    { id: 'R16-3', num: 91, from1: 'R32-4', from2: 'R32-6' }, { id: 'R16-4', num: 92, from1: 'R32-7', from2: 'R32-8' },
    { id: 'R16-5', num: 93, from1: 'R32-11', from2: 'R32-12' }, { id: 'R16-6', num: 94, from1: 'R32-9', from2: 'R32-10' },
    { id: 'R16-7', num: 95, from1: 'R32-14', from2: 'R32-16' }, { id: 'R16-8', num: 96, from1: 'R32-13', from2: 'R32-15' }
  ],
  QF: [
    { id: 'QF-1', num: 97, from1: 'R16-1', from2: 'R16-2' }, { id: 'QF-2', num: 98, from1: 'R16-5', from2: 'R16-6' },
    { id: 'QF-3', num: 99, from1: 'R16-3', from2: 'R16-4' }, { id: 'QF-4', num: 100, from1: 'R16-7', from2: 'R16-8' }
  ],
  SF: [{ id: 'SF-1', num: 101, from1: 'QF-1', from2: 'QF-2' }, { id: 'SF-2', num: 102, from1: 'QF-3', from2: 'QF-4' }],
  TP: [{ id: 'TP-1', num: 103, loserFrom1: 'SF-1', loserFrom2: 'SF-2' }],
  F: [{ id: 'F-1', num: 104, from1: 'SF-1', from2: 'SF-2' }]
};
const R32_LEFT = ['R32-2', 'R32-5', 'R32-1', 'R32-3', 'R32-11', 'R32-12', 'R32-9', 'R32-10'];
const R32_RIGHT = ['R32-4', 'R32-6', 'R32-7', 'R32-8', 'R32-14', 'R32-16', 'R32-13', 'R32-15'];
const R16_L = [BRACKET.R16[0], BRACKET.R16[1], BRACKET.R16[4], BRACKET.R16[5]];
const R16_R = [BRACKET.R16[2], BRACKET.R16[3], BRACKET.R16[6], BRACKET.R16[7]];
const QF_L = [BRACKET.QF[0], BRACKET.QF[1]];
const QF_R = [BRACKET.QF[2], BRACKET.QF[3]];

let currentStep = 1;
let pollaType = null;
const groupRanks = {};
const selectedThirds = new Set();
const selectedThirdsOrder = [];
const knockoutWinners = {};
const koDom = {};
const bracketCols = {};
let bracketReady = false;
let simCurrentStep = 1;
const podiumData = { 1: null, 2: null, 3: null, 4: null };
let uploadedFile = null;

function updateStepper(step) {
  [1, 2, 3].forEach(n => {
    const sn = document.getElementById('sn' + n);
    const sc = document.getElementById('sc' + n);
    sn.classList.remove('active', 'done');
    if (n < step) { sn.classList.add('done'); sc.textContent = '✓'; }
    else if (n === step) { sn.classList.add('active'); sc.textContent = n; }
    else { sc.textContent = n; }
  });
  document.getElementById('stepCounter').textContent = `Paso ${step} de 3`;
  document.getElementById('btnBack').disabled = (step === 1);
  const nb = document.getElementById('btnNext');
  nb.textContent = step === 3 ? 'Enviar ✓' : 'Siguiente →';
}

function showPanel(id) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}

function nextStep() {
  if (currentStep === 1) {
    if (!validateStep1()) return;
    pollaType = document.querySelector('input[name="tipoPolla"]:checked')?.value;
    currentStep = 2;
    if (pollaType === 'free') { buildPool(); showPanel('panel2free'); }
    else { buildGroups(); buildBracket(); showPanel('panel2sim'); }
    updateStepper(2);
  } else if (currentStep === 2) {
    if (pollaType === 'free') {
      if (!validatePodium()) return;
    } else {
      if (!knockoutWinners['F-1']) { alert('Completa el bracket y selecciona un campeón para continuar.'); return; }
      if (!knockoutWinners['TP-1']) { alert('Selecciona el ganador del partido por el 3.er puesto (P103) para continuar.'); return; }
    }
    currentStep = 3;
    buildConfirm();
    showPanel('panel3');
    updateStepper(3);
  } else if (currentStep === 3) {
    submitForm();
  }
}

function prevStep() {
  if (currentStep === 2) {
    currentStep = 1; showPanel('panel1'); updateStepper(1);
  } else if (currentStep === 3) {
    currentStep = 2;
    if (pollaType === 'free') showPanel('panel2free');
    else showPanel('panel2sim');
    updateStepper(2);
  }
}

function validateStep1() {
  let ok = true;
  const checks = [
    { id: 'fg-nombre', val: () => document.getElementById('nombre').value.trim().length > 1 },
    { id: 'fg-codigo', val: () => document.getElementById('codigo').value.trim().length > 4 },
    { id: 'fg-correo', val: () => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.getElementById('correo').value.trim()) },
    { id: 'fg-telefono', val: () => document.getElementById('telefono').value.trim().length >= 7 },
    { id: 'fg-proyecto', val: () => document.getElementById('proyecto').value !== '' },
  ];
  checks.forEach(c => {
    const fg = document.getElementById(c.id);
    if (!c.val()) { fg.classList.add('has-err'); ok = false; }
    else fg.classList.remove('has-err');
  });
  const tipoSel = document.querySelector('input[name="tipoPolla"]:checked');
  const tipoErr = document.getElementById('tipo-err');
  if (!tipoSel) { tipoErr.style.display = 'block'; ok = false; }
  else tipoErr.style.display = 'none';
  return ok;
}

document.querySelectorAll('.type-card').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('sel'));
    card.classList.add('sel');
    card.querySelector('input').checked = true;
  });
});


function buildPool() {
  const pool = document.getElementById('teamsPool');
  pool.innerHTML = '';
  POOL_TEAMS.forEach(t => {
    if (podiumData[1] === t.name || podiumData[2] === t.name || podiumData[3] === t.name || podiumData[4] === t.name) return;
    pool.appendChild(makeTeamCard(t));
  });
}

function makeTeamCard(t) {
  const div = document.createElement('div');
  div.className = 'team-card'; div.draggable = true;
  div.dataset.team = t.name; div.dataset.code = t.code;
  div.innerHTML = `<img src="https://flagcdn.com/w40/${t.code}.png" alt="${t.name}"> ${t.name}`;

  div.addEventListener('dragstart', e => {
    e.dataTransfer.setData('team', t.name);
    e.dataTransfer.setData('code', t.code);
    div.classList.add('dragging-out');
  });
  div.addEventListener('dragend', () => div.classList.remove('dragging-out'));

  div.addEventListener('touchstart', e => {
    touchDragData = { team: t.name, code: t.code, el: div };
    div.classList.add('dragging-out');
  }, { passive: true });

  div.addEventListener('touchend', e => {
    div.classList.remove('dragging-out');
    const touch  = e.changedTouches[0];
    const target = document.elementFromPoint(touch.clientX, touch.clientY);
    const zone   = target?.closest('.drop-zone');
    if(zone) dropOnZone(zone, touchDragData.team, touchDragData.code);
    touchDragData = null;
  });

  return div;
}

let touchDragData = null;

function dropOnZone(zone, team, code) {
  const rank = parseInt(zone.dataset.rank);
  Object.keys(podiumData).forEach(r => { if(podiumData[r] === team) podiumData[r] = null; });
  if(podiumData[rank]) returnToPool(podiumData[rank]);
  podiumData[rank] = team;
  setZone(zone, rank, team, code);
  removeFromPool(team);
}

document.querySelectorAll('.drop-zone').forEach(zone => {
  zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('over'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('over');
    dropOnZone(zone, e.dataTransfer.getData('team'), e.dataTransfer.getData('code'));
  });

  zone.addEventListener('touchmove', e => {
    e.preventDefault();
    const touch  = e.touches[0];
    const target = document.elementFromPoint(touch.clientX, touch.clientY);
    document.querySelectorAll('.drop-zone').forEach(z => z.classList.remove('over'));
    target?.closest('.drop-zone')?.classList.add('over');
  }, { passive: false });

  zone.addEventListener('touchend', () => zone.classList.remove('over'));
});

function filterPool() {
  const q = document.getElementById('teamSearch').value.toLowerCase();
  document.querySelectorAll('#teamsPool .team-card').forEach(c => {
    c.style.display = c.dataset.team.toLowerCase().includes(q) ? '' : 'none';
  });
}

document.querySelectorAll('.drop-zone').forEach(zone => {
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('over'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('over'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('over');
    const team = e.dataTransfer.getData('team');
    const code = e.dataTransfer.getData('code');
    const rank = parseInt(zone.dataset.rank);
    Object.keys(podiumData).forEach(r => { if (podiumData[r] === team) podiumData[r] = null; });
    if (podiumData[rank]) returnToPool(podiumData[rank]);
    podiumData[rank] = team;
    setZone(zone, rank, team, code);
    removeFromPool(team);
  });
});

function setZone(zone, rank, team, code) {
  zone.classList.add('filled');
  zone.innerHTML = `<div class="rank-badge">${['', '1', '2', '3', '4'][rank]}</div>
    <div class="drop-filled"><img src="https://flagcdn.com/w40/${code}.png"> ${team}</div>
    <button class="drop-remove" onclick="clearZone(${rank})">✕</button>`;
}
function clearZone(rank) {
  const team = podiumData[rank]; if (!team) return;
  returnToPool(team); podiumData[rank] = null;
  const zone = document.getElementById('puesto' + rank);
  zone.classList.remove('filled');
  zone.innerHTML = `<div class="rank-badge">${['', '1', '2', '3', '4'][rank]}</div><div class="drop-placeholder">${['', 'Arrastra Campeón', 'Arrastra Subcampeón', 'Arrastra 3° Puesto', 'Arrastra 4° Puesto'][rank]}</div>`;
}
function removeFromPool(team) { document.querySelectorAll(`#teamsPool .team-card[data-team="${team}"]`).forEach(c => c.remove()); }
function returnToPool(team) {
  const t = POOL_TEAMS.find(x => x.name === team); if (!t) return;
  const pool = document.getElementById('teamsPool'); pool.appendChild(makeTeamCard(t));
}
function validatePodium() {
  if (!podiumData[1]) { alert('Debes seleccionar al menos el Campeón.'); return false; }
  return true;
}

function flagImg(name) {
  const code = FLAGS[name];
  return code ? `<img class="flag" src="https://flagcdn.com/w20/${code}.png" alt="${name}" loading="lazy">` : '';
}
function teamPillHTML(name) {
  return `<div class="team-pill" data-team-name="${name}">${flagImg(name)}<span class="team-name-txt">${name}</span></div>`;
}

function buildGroups() {
  const grid = document.getElementById('groups-grid');
  if (grid.innerHTML !== '') return;
  GROUP_IDS.forEach(g => {
    const card = document.createElement('section');
    card.className = 'group-card'; card.dataset.group = g;
    const teams = GROUP_TEAMS[g];
    const rows = teams.map((t, i) => {
      const pos = i + 1;
      const label = ['1°', '2°', '3°', '4°'][i];
      return `<div class="rank-row pos-${pos}" data-code="${g}${pos}">
        <span class="rank-pos">${label}</span>
        <span class="drag-handle">⠿</span>
        ${teamPillHTML(t)}
      </div>`;
    }).join('');
    card.innerHTML = `<div class="group-card-head"><h2>Grupo ${g}</h2></div><div class="rank-list">${rows}</div>`;
    grid.appendChild(card);
    setupGroupInteraction(card);
  });
}

function updateRankLabels(card) {
  card.querySelectorAll('.rank-row').forEach((r, i) => {
    r.querySelector('.rank-pos').textContent = ['1°', '2°', '3°', '4°'][i];
    r.classList.remove('pos-1', 'pos-2', 'pos-3', 'pos-4'); r.classList.add(`pos-${i + 1}`);
  });
}

function setupGroupInteraction(card) {
  const list = card.querySelector('.rank-list'); let sel = null;
  list.querySelectorAll('.rank-row').forEach(row => {
    row.addEventListener('click', () => {
      if (!sel) { sel = row; row.classList.add('selected'); return; }
      if (sel === row) { row.classList.remove('selected'); sel = null; return; }
      const ph = document.createElement('div');
      list.insertBefore(ph, sel); list.insertBefore(sel, row); list.insertBefore(row, ph); list.removeChild(ph);
      sel.classList.remove('selected'); sel = null; updateRankLabels(card);
    });
  });
  if (typeof Sortable !== 'undefined') {
    Sortable.create(list, { animation: 120, ghostClass: 'dragging', onEnd: () => updateRankLabels(card) });
  }
}

function getGroupRanksFromDOM() {
  GROUP_IDS.forEach(g => {
    const card = document.querySelector(`#groups-grid .group-card[data-group="${g}"]`); if (!card) return;
    const rows = card.querySelectorAll('.rank-row'); const names = [];
    rows.forEach((r, i) => { const p = r.querySelector('.team-pill'); names.push(p ? (p.dataset.teamName || p.querySelector('.team-name-txt')?.textContent || `${g}${i + 1}`) : `${g}${i + 1}`); });
    groupRanks[g] = { first: names[0], second: names[1], third: names[2], fourth: names[3] };
  });
}

function buildThirdsTable() {
  getGroupRanksFromDOM();
  const tbody = document.getElementById('third-tbody');
  const countEl = document.getElementById('third-count');
  tbody.innerHTML = '';
  GROUP_IDS.forEach(g => {
    const name = (groupRanks[g] || {}).third || `${g}3`;
    const tr = document.createElement('tr'); tr.dataset.group = g;
    if (selectedThirds.has(g)) tr.classList.add('sel');
    tr.innerHTML = `<td>Grupo ${g}</td><td><div class="team-cell">${flagImg(name)} ${name}</div></td>`;
    tr.addEventListener('click', () => {
      if (selectedThirds.has(g)) { selectedThirds.delete(g); const i = selectedThirdsOrder.indexOf(g); if (i > -1) selectedThirdsOrder.splice(i, 1); tr.classList.remove('sel'); }
      else {
        if (selectedThirds.size >= 8) { const old = selectedThirdsOrder.shift(); selectedThirds.delete(old); tbody.querySelector(`tr[data-group="${old}"]`)?.classList.remove('sel'); }
        selectedThirds.add(g); selectedThirdsOrder.push(g); tr.classList.add('sel');
      }
      countEl.textContent = selectedThirds.size;
    });
    tbody.appendChild(tr);
  });
  countEl.textContent = selectedThirds.size;
}

function renderKoSpan(span, name) {
  span.dataset.teamName = name; span.innerHTML = '';
  if (span.dataset.placeholder === name) { span.classList.add('placeholder'); span.textContent = name; }
  else { span.classList.remove('placeholder'); const code = FLAGS[name]; if (code) { const img = document.createElement('img'); img.className = 'flag'; img.src = `https://flagcdn.com/w20/${code}.png`; img.alt = name; span.appendChild(img); } const s = document.createElement('span'); s.textContent = name; span.appendChild(s); }
}
function isPlaceholder(span) { return span && span.dataset.placeholder && span.dataset.teamName === span.dataset.placeholder; }

function createMatchEl(round, cfg) {
  const div = document.createElement('div'); div.className = 'ko-match'; div.dataset.matchId = cfg.id;
  const num = document.createElement('div'); num.className = 'ko-match-num'; num.textContent = `P${cfg.num}`; div.appendChild(num);
  let ph1, ph2;
  if (round === 'R32') { ph1 = cfg.slot1 || '?'; ph2 = cfg.slot2 ? cfg.slot2 : (cfg.thirds ? '3° ' + cfg.thirds.join('/') : '?'); }
  else { ph1 = 'W ' + cfg.from1; ph2 = 'W ' + cfg.from2; }
  const t1 = document.createElement('div'); t1.className = 'ko-team';
  const s1 = document.createElement('span'); s1.className = 'ko-name'; s1.dataset.placeholder = ph1;
  const t2 = document.createElement('div'); t2.className = 'ko-team';
  const s2 = document.createElement('span'); s2.className = 'ko-name'; s2.dataset.placeholder = ph2;
  renderKoSpan(s1, ph1); renderKoSpan(s2, ph2);
  t1.appendChild(s1); t2.appendChild(s2); div.appendChild(t1); div.appendChild(t2);
  t1.addEventListener('click', () => { if (!isPlaceholder(s1) && !isPlaceholder(s2)) toggleWinner(cfg.id, 1, t1, t2, s1.dataset.teamName); });
  t2.addEventListener('click', () => { if (!isPlaceholder(s1) && !isPlaceholder(s2)) toggleWinner(cfg.id, 2, t1, t2, s2.dataset.teamName); });
  koDom[cfg.id] = { el: div, t1row: t1, t2row: t2, t1span: s1, t2span: s2 };
  return div;
}

function makeCol(title, extraClass = '') {
  const col = document.createElement('div'); col.className = 'round-col' + (extraClass ? ' ' + extraClass : '');
  const t = document.createElement('div'); t.className = 'round-title-b'; t.textContent = title; col.appendChild(t); return col;
}

function createTpMatchEl() {
  const div = document.createElement('div'); div.className = 'ko-match tp-ko'; div.dataset.matchId = 'TP-1';
  const num = document.createElement('div'); num.className = 'ko-match-num'; num.textContent = 'P103 · 3.er puesto'; div.appendChild(num);
  const ph = 'Perdedor semifinal';
  const t1 = document.createElement('div'); t1.className = 'ko-team';
  const s1 = document.createElement('span'); s1.className = 'ko-name'; s1.dataset.placeholder = ph;
  const t2 = document.createElement('div'); t2.className = 'ko-team';
  const s2 = document.createElement('span'); s2.className = 'ko-name'; s2.dataset.placeholder = ph;
  renderKoSpan(s1, ph); renderKoSpan(s2, ph);
  t1.appendChild(s1); t2.appendChild(s2); div.appendChild(t1); div.appendChild(t2);
  t1.addEventListener('click', () => {
    if (isPlaceholder(s1) || isPlaceholder(s2)) return;
    if (knockoutWinners['TP-1'] === s1.dataset.teamName) { delete knockoutWinners['TP-1']; t1.classList.remove('winner'); return; }
    knockoutWinners['TP-1'] = s1.dataset.teamName; t1.classList.add('winner'); t2.classList.remove('winner');
  });
  t2.addEventListener('click', () => {
    if (isPlaceholder(s1) || isPlaceholder(s2)) return;
    if (knockoutWinners['TP-1'] === s2.dataset.teamName) { delete knockoutWinners['TP-1']; t2.classList.remove('winner'); return; }
    knockoutWinners['TP-1'] = s2.dataset.teamName; t2.classList.add('winner'); t1.classList.remove('winner');
  });
  koDom['TP-1'] = { el: div, t1row: t1, t2row: t2, t1span: s1, t2span: s2 };
  return div;
}

function buildBracket() {
  const br = document.getElementById('bracket');
  if (br.innerHTML !== '') return;
  const col = (title, cfgs, roundKey, isR32 = false, ids = null, extraClass = '') => {
    const c = makeCol(title, extraClass);
    if (isR32) { ids.forEach(id => { const cfg = BRACKET.R32.find(m => m.id === id); if (cfg) c.appendChild(createMatchEl(roundKey, cfg)); }); }
    else { cfgs.forEach(cfg => c.appendChild(createMatchEl(roundKey, cfg))); }
    return c;
  };
  const cR32L = col('Dieciseisavos', null, 'R32', true, R32_LEFT); br.appendChild(cR32L); bracketCols.r32l = cR32L;
  const cR16L = col('Octavos', R16_L, 'R16'); br.appendChild(cR16L); bracketCols.r16l = cR16L;
  const cQFL = col('Cuartos', QF_L, 'QF'); br.appendChild(cQFL); bracketCols.qfl = cQFL;
  const cSFL = col('Semis', BRACKET.SF.slice(0, 1), 'SF'); br.appendChild(cSFL); bracketCols.sfl = cSFL;
  const cTP = makeCol('3.er puesto', 'tp-col');
  const tpMatchEl = createTpMatchEl();
  cTP.appendChild(tpMatchEl);
  br.appendChild(cTP); bracketCols.tp = cTP;
  const cF = col('Final', BRACKET.F, 'F'); br.appendChild(cF); bracketCols.f = cF;
  const cSFR = col('Semis', BRACKET.SF.slice(1), 'SF'); br.appendChild(cSFR); bracketCols.sfr = cSFR;
  const cQFR = col('Cuartos', QF_R, 'QF'); br.appendChild(cQFR); bracketCols.qfr = cQFR;
  const cR16R = col('Octavos', R16_R, 'R16'); br.appendChild(cR16R); bracketCols.r16r = cR16R;
  const cR32R = col('Dieciseisavos', null, 'R32', true, R32_RIGHT); br.appendChild(cR32R); bracketCols.r32r = cR32R;
  bracketReady = true;
}

function buildSlotMap() {
  const s = {}; GROUP_IDS.forEach(g => { const r = groupRanks[g]; if (!r) return; s['1' + g] = r.first; s['2' + g] = r.second; s['3' + g] = r.third; }); return s;
}
function computeThirds(slot) {
  const qual = Array.from(selectedThirds);
  const matches = BRACKET.R32.filter(c => c.thirds);
  const map = {}; const used = new Set();
  function bt(i) { if (i === matches.length) return true; const cfg = matches[i]; for (const g of qual) { if (used.has(g) || !cfg.thirds.includes(g)) continue; map[cfg.id] = { group: g, team: slot['3' + g] }; used.add(g); if (bt(i + 1)) return true; used.delete(g); delete map[cfg.id]; } return false; }
  if (!bt(0)) { let i = 0; matches.forEach(cfg => { const g = qual[i++ % qual.length]; map[cfg.id] = { group: g, team: slot['3' + g] || '3°' + g }; }); }
  return map;
}
function seedBracket() {
  if (!bracketReady) return;
  const slot = buildSlotMap(); const thirds = computeThirds(slot);
  BRACKET.R32.forEach(cfg => {
    const d = koDom[cfg.id]; if (!d) return;
    const n1 = cfg.slot1 ? (slot[cfg.slot1] || d.t1span.dataset.placeholder) : d.t1span.dataset.placeholder;
    let n2; if (cfg.slot2) n2 = slot[cfg.slot2] || d.t2span.dataset.placeholder; else if (cfg.thirds) { const a = thirds[cfg.id]; n2 = a ? a.team : d.t2span.dataset.placeholder; } else n2 = d.t2span.dataset.placeholder;
    renderKoSpan(d.t1span, n1); renderKoSpan(d.t2span, n2);
  });
  resetWinners();
}
function resetWinners() {
  Object.keys(knockoutWinners).forEach(k => delete knockoutWinners[k]);
  document.querySelectorAll('.ko-team').forEach(r => r.classList.remove('winner'));
  ['R16', 'QF', 'SF', 'F'].forEach(round => { BRACKET[round].forEach(cfg => { const d = koDom[cfg.id]; if (!d) return; renderKoSpan(d.t1span, d.t1span.dataset.placeholder); renderKoSpan(d.t2span, d.t2span.dataset.placeholder); }); });
  // reset TP
  if (koDom['TP-1']) { const d = koDom['TP-1']; const ph = 'Perdedor semifinal'; d.t1span.dataset.placeholder = ph; d.t2span.dataset.placeholder = ph; renderKoSpan(d.t1span, ph); renderKoSpan(d.t2span, ph); }
  const ch = document.getElementById('champ-name'); if (ch) { ch.innerHTML = ''; ch.textContent = '?'; }
  setTimeout(layoutBracket, 0);
}
function toggleWinner(matchId, which, r1, r2, name) {
  if (knockoutWinners[matchId] === name) { delete knockoutWinners[matchId]; r1.classList.remove('winner'); r2.classList.remove('winner'); propagateWinners(); }
  else { r1.classList.remove('winner'); r2.classList.remove('winner'); (which === 1 ? r1 : r2).classList.add('winner'); knockoutWinners[matchId] = name; propagateWinners(); setTimeout(layoutBracket, 0); if (matchId === 'F-1' && name) onChampionSelected(name); }
}
function propagateWinners() {
  ['R16', 'QF', 'SF', 'F'].forEach(round => { BRACKET[round].forEach(cfg => { const d = koDom[cfg.id]; if (!d) return; const n1 = knockoutWinners[cfg.from1] || d.t1span.dataset.placeholder; const n2 = knockoutWinners[cfg.from2] || d.t2span.dataset.placeholder; renderKoSpan(d.t1span, n1); renderKoSpan(d.t2span, n2); }); });
  const ch = document.getElementById('champ-name'); const champ = knockoutWinners['F-1'] || '?'; ch.innerHTML = ''; if (FLAGS[champ]) { const img = document.createElement('img'); img.className = 'flag'; img.src = `https://flagcdn.com/w20/${FLAGS[champ]}.png`; img.alt = champ; ch.appendChild(img); } ch.appendChild(document.createTextNode(' ' + champ));
  propagateThirdPlace();
}
function onChampionSelected(name) {
  document.getElementById('tie3000-wrap').style.display = 'block';
  launchConfetti();
}

function getLoser(sfId) {
  const d = koDom[sfId]; if (!d) return null;
  const w = knockoutWinners[sfId]; if (!w) return null;
  const t1 = d.t1span.dataset.teamName; const t2 = d.t2span.dataset.teamName;
  return w === t1 ? t2 : t1;
}

function propagateThirdPlace() {
  const loser1 = getLoser('SF-1');
  const loser2 = getLoser('SF-2');

  const d = koDom['TP-1'];
  if (!d) return;

  const ready = loser1 && loser2 && !loser1.startsWith('W ') && !loser2.startsWith('W ');

  if (!ready) {
    const ph = 'Perdedor semifinal';
    renderKoSpan(d.t1span, ph);
    renderKoSpan(d.t2span, ph);
    d.t1row.classList.remove('winner');
    d.t2row.classList.remove('winner');
    delete knockoutWinners['TP-1'];
    return;
  }
  const curW = knockoutWinners['TP-1'];


  renderKoSpan(d.t1span, loser1);
  renderKoSpan(d.t2span, loser2);

  d.t1row.classList.remove('winner');
  d.t2row.classList.remove('winner');

  if (curW === loser1) {
    d.t1row.classList.add('winner');
    knockoutWinners['TP-1'] = loser1;
  } else if (curW === loser2) {
    d.t2row.classList.add('winner');
    knockoutWinners['TP-1'] = loser2;
  } else {
    delete knockoutWinners['TP-1'];
  }
}

function layoutBracket() {
  if (!bracketReady) return;
  function alignRound(parentCol, cfgList) {
    if (!parentCol) return; const pRect = parentCol.getBoundingClientRect(); let maxBottom = 0;
    cfgList.forEach(cfg => {
      const d = koDom[cfg.id]; if (!d) return;
      const d1 = koDom[cfg.from1]; const d2 = koDom[cfg.from2]; if (!d1 || !d2) return;
      const r1 = d1.el.getBoundingClientRect(); const r2 = d2.el.getBoundingClientRect();
      const midY = ((r1.top + r1.bottom) / 2 + (r2.top + r2.bottom) / 2) / 2;
      const mh = d.el.getBoundingClientRect().height; const top = midY - pRect.top - mh / 2;
      d.el.style.position = 'absolute'; d.el.style.left = '0'; d.el.style.right = '0'; d.el.style.top = top + 'px';
      maxBottom = Math.max(maxBottom, top + mh);
    });
    parentCol.style.position = 'relative'; parentCol.style.minHeight = (maxBottom + 20) + 'px';
  }
  alignRound(bracketCols.r16l, R16_L); alignRound(bracketCols.r16r, R16_R);
  alignRound(bracketCols.qfl, QF_L); alignRound(bracketCols.qfr, QF_R);
  alignRound(bracketCols.sfl, BRACKET.SF.slice(0, 1)); alignRound(bracketCols.sfr, BRACKET.SF.slice(1));
  alignRound(bracketCols.f, BRACKET.F);
  alignTpMatch();
  drawLines();
}
function alignTpMatch() {
  const tpCol = bracketCols.tp; const d = koDom['TP-1']; if (!tpCol || !d) return;
  const sf1d = koDom['SF-1']; const sf2d = koDom['SF-2']; if (!sf1d || !sf2d) return;
  const pRect = tpCol.getBoundingClientRect();
  const r1 = sf1d.el.getBoundingClientRect(); const r2 = sf2d.el.getBoundingClientRect();
  const midY = ((r1.top + r1.bottom) / 2 + (r2.top + r2.bottom) / 2) / 2;
  const mh = d.el.getBoundingClientRect().height;
  const top = midY - pRect.top - mh / 2;
  d.el.style.position = 'absolute'; d.el.style.left = '0'; d.el.style.right = '0'; d.el.style.top = top + 'px';
  tpCol.style.position = 'relative'; tpCol.style.minHeight = (top + mh + 20) + 'px';
}

function getLoserTP() {
  const d = koDom['TP-1'];
  if (!d) return null;

  const winner = knockoutWinners['TP-1'];
  if (!winner) return null;

  const t1 = d.t1span.dataset.teamName;
  const t2 = d.t2span.dataset.teamName;

  return winner === t1 ? t2 : t1;
}

function drawLines() {
  const svg = document.getElementById('bracket-lines');
  const scroll = document.getElementById('bracket-scroll');
  if (!svg || !scroll) return;

  const rect = scroll.getBoundingClientRect();
  svg.setAttribute('width', rect.width);
  svg.setAttribute('height', rect.height);
  svg.setAttribute('viewBox', `0 0 ${rect.width} ${rect.height}`);

  while (svg.firstChild) svg.removeChild(svg.firstChild);

  function addLine(el1, el2) {
    if (!el1 || !el2) return;
    const r1 = el1.getBoundingClientRect(), r2 = el2.getBoundingClientRect();
    const y1 = (r1.top + r1.bottom) / 2 - rect.top;
    const y2 = (r2.top + r2.bottom) / 2 - rect.top;

    let x1, x2;
    if (r1.right <= r2.left) {
      x1 = r1.right - rect.left;
      x2 = r2.left - rect.left;
    } else {
      x1 = r1.left - rect.left;
      x2 = r2.right - rect.left;
    }

    const mx = (x1 + x2) / 2;

    const poly = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
    poly.setAttribute('points', `${x1},${y1} ${mx},${y1} ${mx},${y2} ${x2},${y2}`);
    poly.setAttribute('fill', 'none');
    poly.setAttribute('stroke', 'rgba(8,95,154,0.4)');
    poly.setAttribute('stroke-width', '1.5');
    poly.setAttribute('stroke-linecap', 'round');
    svg.appendChild(poly);
  }

  [...BRACKET.R16, ...BRACKET.QF, ...BRACKET.SF, ...BRACKET.F].forEach(cfg => {
    const d = koDom[cfg.id], d1 = koDom[cfg.from1], d2 = koDom[cfg.from2];
    if (d && d1) addLine(d1.el, d.el);
    if (d && d2) addLine(d2.el, d.el);
  });
}

function simGoTo(n) {
  ['1', '2', '3'].forEach(i => {
    document.getElementById('ss' + i).classList.toggle('active', parseInt(i) === n);
    document.getElementById('stab' + i).classList.toggle('active', parseInt(i) === n);
  });
  simCurrentStep = n;
  if (n === 3) setTimeout(layoutBracket, 80);
}
function simNext1() { getGroupRanksFromDOM(); buildThirdsTable(); simGoTo(2); }
function simNext2() {
  if (selectedThirds.size !== 8) { alert('Selecciona exactamente 8 terceros clasificados.'); return; }
  getGroupRanksFromDOM(); seedBracket(); simGoTo(3); setTimeout(layoutBracket, 80);
}

function buildConfirm() {
  document.getElementById('cs-nombre').textContent = document.getElementById('nombre').value;
  document.getElementById('cs-codigo').textContent = document.getElementById('codigo').value;
  document.getElementById('cs-correo').textContent = document.getElementById('correo').value;
  document.getElementById('cs-telefono').textContent = document.getElementById('telefono').value;
  document.getElementById('cs-proyecto').textContent = document.getElementById('proyecto').value;
  document.getElementById('cs-tipo').textContent = pollaType === 'free' ? 'Polla Gratis' : 'Polla $3.000';

  if (pollaType === 'free') {
    document.getElementById('upload-section').style.display = 'none';
    document.getElementById('free-note-wrap').style.display = 'block';
    document.getElementById('cs-campeon').textContent = podiumData[1] || '—';
    document.getElementById('cs-sub').textContent = podiumData[2] || '—';
    document.getElementById('cs-3rd').textContent = podiumData[3] || '—';
    document.getElementById('cs-4th').textContent = podiumData[4] || '—';
    document.getElementById('cs-sf-row').style.display = 'none';
    document.getElementById('cs-gol').textContent = document.getElementById('goleador').value || '—';
  } else {
    document.getElementById('upload-section').style.display = 'block';
    document.getElementById('free-note-wrap').style.display = 'none';
    const champ = knockoutWinners['F-1'] || '—';
    const fin = BRACKET.F[0]; const fd = koDom[fin.id];
    const finalist1 = fd?.t1span.dataset.teamName || '—';
    const finalist2 = fd?.t2span.dataset.teamName || '—';
    const sf1d = koDom['SF-1'], sf2d = koDom['SF-2'];
    const sf = [sf1d?.t1span.dataset.teamName, sf1d?.t2span.dataset.teamName, sf2d?.t1span.dataset.teamName, sf2d?.t2span.dataset.teamName].filter(x => x && !x.startsWith('W ')).join(', ');
    document.getElementById('cs-campeon').textContent = champ;
    document.getElementById('cs-sub').textContent = champ === finalist1 ? finalist2 : finalist1;
    const third = knockoutWinners['TP-1'] || '—';
    document.getElementById('cs-3rd').textContent = third;
    document.getElementById('cs-3rd-row').style.display = 'flex';
    const fourth = getLoserTP() || '—';
    document.getElementById('cs-4th').textContent = fourth;
    if (sf) { document.getElementById('cs-sf-row').style.display = 'flex'; document.getElementById('cs-sf').textContent = sf; }
    document.getElementById('cs-gol').textContent = document.getElementById('goleador3').value || '—';
  }
}

function handleFile(input) {
  const file = input.files[0]; if (!file) return;
  if (file.size > 5 * 1024 * 1024) { alert('El archivo supera 5MB.'); return; }
  uploadedFile = file;
  document.getElementById('prevName').textContent = file.name;
  document.getElementById('prevSize').textContent = (file.size / 1024).toFixed(0) + ' KB';
  document.getElementById('uploadPreview').classList.add('show');
  document.getElementById('uploadArea').style.display = 'none';
}
function removeFile() { uploadedFile = null; document.getElementById('uploadPreview').classList.remove('show'); document.getElementById('uploadArea').style.display = 'block'; document.getElementById('fileInput').value = ''; }
function handleDrop(e) { e.preventDefault(); document.getElementById('uploadArea').classList.remove('over'); const file = e.dataTransfer.files[0]; if (file) { const fi = document.getElementById('fileInput'); const dt = new DataTransfer(); dt.items.add(file); fi.files = dt.files; handleFile(fi); } }
document.getElementById('uploadArea')?.addEventListener('dragover', e => { e.preventDefault(); e.currentTarget.classList.add('over'); });
document.getElementById('uploadArea')?.addEventListener('dragleave', e => e.currentTarget.classList.remove('over'));

function submitForm() {
  if (pollaType === '3000' && !uploadedFile) {
    alert('Por favor adjunta tu comprobante de pago.');
    return;
  }
  const formData = new FormData();
  formData.append('nombre',     document.getElementById('nombre').value);
  formData.append('codigo',     document.getElementById('codigo').value);
  formData.append('correo',     document.getElementById('correo').value);
  formData.append('telefono',   document.getElementById('telefono').value);
  formData.append('proyecto',   document.getElementById('proyecto').value);
  formData.append('tipo',       pollaType);
  formData.append('campeon',    document.getElementById('cs-campeon').textContent);
  formData.append('subcampeon', document.getElementById('cs-sub').textContent);
  formData.append('tercero',    document.getElementById('cs-3rd').textContent);
  formData.append('cuarto',     document.getElementById('cs-4th').textContent);

  const is3000 = pollaType === '3000';
  const s = is3000 ? '3' : ''; 

  formData.append('goleador',        document.getElementById(`goleador${s}`)?.value        || '');
  formData.append('mejor_arquero',   document.getElementById(`portero${s}`)?.value         || '');
  formData.append('goles_final',     document.getElementById(`golesFinal${s}`)?.value      || '');
  formData.append('tarjetas_rojas',  document.getElementById(`tarjetasRojas${s}`)?.value   || '');
  formData.append('goles_grupos',    document.getElementById(`golesFaseGrupos${s}`)?.value || '');
  formData.append('equipo_sorpresa',   document.getElementById(`equipoSorpresa${s}`)?.dataset.value  || '');
  formData.append('equipo_decepcion',  document.getElementById(`equipoDecepcion${s}`)?.dataset.value || '');
  formData.append('jugador_joven',    document.getElementById(`jugadorJoven${s}`)?.value    || '');
  formData.append('seleccion_goles',   document.getElementById(`seleccionGoles${s}`)?.dataset.value  || '');
  formData.append('seleccion_defensa', document.getElementById(`seleccionDefensa${s}`)?.dataset.value|| '');
  formData.append('prorroga_final',   document.getElementById(`prorrogaFinal${s}`)?.value   || '');

  if(pollaType === '3000'){
    formData.append('comprobante', uploadedFile);
    GROUP_IDS.forEach(g => {
      const card = document.querySelector(`#groups-grid .group-card[data-group="${g}"]`);
      if(!card) return;
      card.querySelectorAll('.rank-row').forEach((r, i) => {
        const p    = r.querySelector('.team-pill');
        const name = p ? (p.dataset.teamName || p.querySelector('.team-name-txt')?.textContent || '') : '';
        formData.append(`grupo_${g}_${i + 1}`, name);
      });
    });
    selectedThirds.forEach(g => formData.append('terceros[]', g));
    let n = 1;
    [
      { key: 'R32', cfgs: BRACKET.R32 },
      { key: 'R16', cfgs: BRACKET.R16 },
      { key: 'QF',  cfgs: BRACKET.QF  },
      { key: 'SF',  cfgs: BRACKET.SF  },
      { key: 'F',   cfgs: BRACKET.F   }
    ].forEach(({ key, cfgs }) => {
      cfgs.forEach(cfg => {
        const d  = koDom[cfg.id];
        if(!d) return;
        const e1 = d.t1span.dataset.teamName || '';
        const e2 = d.t2span.dataset.teamName || '';
        const gn = knockoutWinners[cfg.id]   || '';
        if(e1 && e2 && !e1.startsWith('W ') && !e2.startsWith('W ')){
          formData.append(`ronda_${n}`,   key);
          formData.append(`partido_${n}`, cfg.id);
          formData.append(`equipo1_${n}`, e1);
          formData.append(`equipo2_${n}`, e2);
          formData.append(`ganador_${n}`, gn);
          n++;
        }
      });
    });
    const tp = koDom['TP-1'];
    if(tp){
      const e1 = tp.t1span.dataset.teamName || '';
      const e2 = tp.t2span.dataset.teamName || '';
      const gn = knockoutWinners['TP-1']    || '';
      if(e1 && e2 && !e1.startsWith('W ') && !e2.startsWith('W ')){
        formData.append(`ronda_${n}`,   'TP');
        formData.append(`partido_${n}`, 'TP-1');
        formData.append(`equipo1_${n}`, e1);
        formData.append(`equipo2_${n}`, e2);
        formData.append(`ganador_${n}`, gn);
      }
    }
  }
  fetch('backend/guardar.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if(data.error){
      alert(data.error);
      return;
    }
    document.querySelector('.form-actions').style.display = 'none';
    document.getElementById('panel3').classList.remove('active');
    const ss = document.getElementById('successScreen');
    ss.classList.add('show');
    document.getElementById('pollNumber').textContent = '#' + data.id;
    launchConfetti();
  })
  .catch(err => {
    console.error(err);
    alert("Error al guardar");
  });
}


function launchConfetti() {
  const existing = document.querySelector('.confetti-wrap'); if (existing) existing.remove();
  const wrap = document.createElement('div'); wrap.className = 'confetti-wrap'; document.body.appendChild(wrap);
  const colors = ['#085F9A', '#3877ff', '#22c55e', '#f59e0b', '#ef4444', '#a855f7'];
  for (let i = 0; i < 120; i++) { const p = document.createElement('div'); p.className = 'cp'; p.style.cssText = `left:${Math.random() * 100}vw;background:${colors[i % colors.length]};width:${4 + Math.random() * 5}px;height:${8 + Math.random() * 8}px;animation-duration:${2 + Math.random() * 1.5}s;animation-delay:${Math.random() * .5}s;`; wrap.appendChild(p); }
  setTimeout(() => { wrap.classList.add('fade'); setTimeout(() => wrap.remove(), 900); }, 3000);
}


window.addEventListener('resize', () => setTimeout(layoutBracket, 50));

const WORLD_CUP_TEAMS = Object.values(BASE_TEAMS)
  .flat()
  .filter(name => !name.startsWith('Ganador'))
  .filter((name, i, arr) => arr.indexOf(name) === i)
  .sort((a, b) => a.localeCompare(b, 'es'))
  .map(name => ({
    name,
    flag: FLAGS[name] ? `https://flagcdn.com/20x15/${FLAGS[name]}.png` : null
  }));

function buildTeamSelect(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  let selectedValue = '';

  container.innerHTML = `
    <div class="ts-trigger" tabindex="0">
      <span class="ts-placeholder">Selecciona un equipo</span>
      <span class="ts-arrow">▼</span>
    </div>
    <div class="ts-dropdown">
      <input class="ts-search" type="text" placeholder="Buscar equipo...">
      <div class="ts-list"></div>
    </div>
  `;

  const trigger  = container.querySelector('.ts-trigger');
  const dropdown = container.querySelector('.ts-dropdown');
  const search   = container.querySelector('.ts-search');
  const list     = container.querySelector('.ts-list');

  function renderList(filter = '') {
    const q = filter.toLowerCase();
    list.innerHTML = '';
    WORLD_CUP_TEAMS
      .filter(t => t.name.toLowerCase().includes(q))
      .forEach(t => {
        const opt = document.createElement('div');
        opt.className = 'ts-option' + (t.name === selectedValue ? ' selected' : '');
        opt.innerHTML = `
          ${t.flag
            ? `<img src="${t.flag}" style="width:20px;height:15px;border-radius:2px;object-fit:cover;">`
            : '<span style="width:20px;display:inline-block;"></span>'}
          <span>${t.name}</span>`;
        opt.addEventListener('click', () => {
          selectedValue = t.name;
          trigger.innerHTML = `
            <span style="display:flex;align-items:center;gap:.5rem;">
              ${t.flag
                ? `<img src="${t.flag}" style="width:20px;height:15px;border-radius:2px;object-fit:cover;">`
                : ''}
              <span>${t.name}</span>
            </span>
            <span class="ts-arrow">▼</span>`;
          dropdown.classList.remove('open');
          container.dataset.value = t.name;
        });
        list.appendChild(opt);
      });
  }

  trigger.addEventListener('click', () => {
    dropdown.classList.toggle('open');
    if (dropdown.classList.contains('open')) {
      search.value = '';
      renderList();
      search.focus();
    }
  });

  search.addEventListener('input', () => renderList(search.value));

  document.addEventListener('click', e => {
    if (!container.contains(e.target)) dropdown.classList.remove('open');
  });

  renderList();
}

['equipoSorpresa3','equipoDecepcion3','seleccionGoles3','seleccionDefensa3'].forEach(buildTeamSelect);
['equipoSorpresa','equipoDecepcion','seleccionGoles','seleccionDefensa'].forEach(buildTeamSelect);