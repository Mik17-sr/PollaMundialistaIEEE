const API = 'https://api.anthropic.com/v1/messages';

const FLAGS = {
  'México': 'mx', 'Sudáfrica': 'za', 'Corea del Sur': 'kr', 'República Checa': 'cz',
  'Canadá': 'ca', 'Bosnia & Herzegovina': 'ba', 'Catar': 'qa', 'Suiza': 'ch',
  'Brasil': 'br', 'Marruecos': 'ma', 'Haití': 'ht', 'Escocia': 'gb-sct',
  'Estados Unidos': 'us', 'Paraguay': 'py', 'Australia': 'au', 'Turquía': 'tr',
  'Alemania': 'de', 'Curazao': 'cw', 'Costa de Marfil': 'ci', 'Ecuador': 'ec',
  'Países Bajos': 'nl', 'Japón': 'jp', 'Suecia': 'se', 'Túnez': 'tn',
  'Bélgica': 'be', 'Egipto': 'eg', 'Irán': 'ir', 'Nueva Zelanda': 'nz',
  'España': 'es', 'Cabo Verde': 'cv', 'Arabia Saudita': 'sa', 'Uruguay': 'uy',
  'Francia': 'fr', 'Senegal': 'sn', 'Irak': 'iq', 'Noruega': 'no',
  'Argentina': 'ar', 'Argelia': 'dz', 'Austria': 'at', 'Jordania': 'jo',
  'Portugal': 'pt', 'República Democrática del Congo': 'cd', 'Uzbekistán': 'uz',
  'Colombia': 'co', 'Inglaterra': 'gb-eng', 'Croacia': 'hr', 'Ghana': 'gh', 'Panamá': 'pa'
};

const RONDA_MAP = {
  'R32': 'R32', 'R16': 'R16', 'QF': 'Cuartos',
  'SF': 'Semifinales', 'F': 'Final', 'TP': '3er Puesto',
  'Octavos': 'R16', 'Cuartos': 'Cuartos', 'Semifinales': 'Semifinales',
  'Final': 'Final', '3er Puesto': '3er Puesto'
};

const ROUND_ORDER_DISPLAY = ['R32', 'R16', 'Cuartos', 'Semifinales', 'Final'];

const ROUND_LABELS = {
  R32: 'Dieciseisavos', R16: 'Octavos', Cuartos: 'Cuartos',
  Semifinales: 'Semis', Final: 'Final', '3er Puesto': '3.er puesto'
};

const ROUND_MATCH_START = {
  R32: 57, R16: 73, Cuartos: 89, Semifinales: 97, '3er Puesto': 101, Final: 103
};

function maxMatchesInRound(byRound) {
  return Math.max(...ROUND_ORDER_DISPLAY.map(r => (byRound[r] || []).length || 1));
}

const MAX_PTS_3000 = 539;
const MAX_PTS_FREE = 40;

function flagImg(name, size = 20) {
  const code = FLAGS[name];
  if (!code) return '';
  return `<img src="https://flagcdn.com/w${size}/${code}.png" alt="${name}" style="width:${size}px;height:${Math.round(size * 0.72)}px;object-fit:cover;border-radius:2px;flex-shrink:0;">`;
}

function teamCell(name) {
  return `<span class="modal-team-cell">${flagImg(name)} <span>${name || '—'}</span></span>`;
}

function calcPtsFree(pred_podio, real_podio) {
  if (!real_podio.campeon) return { total: 0, breakdown: [], hasReal: false };
  const positions = ['campeon', 'subcampeon', 'tercero', 'cuarto'];
  const realTop4 = positions.map(p => real_podio[p]).filter(Boolean);
  let total = 0;
  const breakdown = positions.map(pos => {
    const pred = pred_podio[pos];
    const real = real_podio[pos];
    if (!pred) return { pos, pred, pts: 0, reason: 'Sin predicción' };
    if (pred === real) return { pos, pred, pts: 10, reason: 'Posición exacta +10' };
    if (realTop4.includes(pred)) return { pos, pred, pts: 5, reason: 'En top 4 +5' };
    return { pos, pred, pts: 0, reason: 'Fuera del top 4' };
  });
  total = breakdown.reduce((s, b) => s + b.pts, 0);
  return { total, breakdown, hasReal: true };
}


function calcPts3000(pred, real, id_pred, predGrupos, predElim, predTerceros){
  if (!real.podio.campeon) return { total: 0, sections: {}, hasReal: false };
  let sections = { grupos: 0, terceros: 0, eliminatorias: 0, podio: 0 };
  let grupoDet = [], tercerosDet = [], elimDet = [], podioDet = []; 

   const realThirds = real.terceros || [];
const realThirdTeams = realThirds.map(t => t.equipo);

for (const equipo of (predTerceros || [])) {
  if (realThirdTeams.includes(equipo)) {
    sections.terceros += 4;
    tercerosDet.push({ equipo, pts: 4, reason: 'Tercero correcto +4' });
  } else {
    tercerosDet.push({ equipo, pts: 0, reason: 'No clasificó' });
  }
}


  const realGrupos = real.grupos || {};
  for (const pg of (predGrupos || [])) {
    const rg = realGrupos[pg.grupo];
    if (!rg) continue;
    const poss = ['primero', 'segundo', 'tercero', 'cuarto'];
    const realClassified = [rg.primero, rg.segundo].filter(Boolean);
    for (let i = 0; i < 4; i++) {
      const pTeam = pg[poss[i]];
      const rTeam = rg[poss[i]];
      if (!pTeam) continue;
      if (pTeam === rTeam) {
        sections.grupos += 5;
        grupoDet.push({ team: pTeam, pts: 5, reason: 'Posición exacta' });
      } else if (i < 2 && realClassified.includes(pTeam)) {
        sections.grupos += 3;
        grupoDet.push({ team: pTeam, pts: 3, reason: 'Clasificó, otra pos.' });
      } else {
        grupoDet.push({ team: pTeam, pts: 0, reason: 'Sin puntos' });
      }
    }
  }

const progPts  = { R32: 1, R16: 2, Cuartos: 3, Semifinales: 4, '3er Puesto': 3, Final: 5 };
const cruzPts  = { R32: 2, R16: 3, Cuartos: 4, Semifinales: 5, '3er Puesto': 4, Final: 6 };
const ganadPts = { R32: 1, R16: 1, Cuartos: 2, Semifinales: 2, '3er Puesto': 2, Final: 3 };
  const realElim = real.eliminatorias || {};

  for (const pe of (predElim || [])) {
    const ronda = RONDA_MAP[pe.ronda] || pe.ronda; 
    const rKey  = pe.partido_id;
    const re    = realElim[rKey];
    const pp = progPts[ronda] || 1, cp = cruzPts[ronda] || 2, gp = ganadPts[ronda] || 1;
    let pts = 0; let reasons = [];
    const realTeamsInRound = Object.values(realElim)
        .filter(r => (RONDA_MAP[r.ronda] || r.ronda) === ronda)
        .flatMap(r => [r.equipo1, r.equipo2])
        .filter(Boolean);
    if (realTeamsInRound.includes(pe.equipo1)) { pts += pp; reasons.push('Progreso E1 +' + pp); }
    if (realTeamsInRound.includes(pe.equipo2)) { pts += pp; reasons.push('Progreso E2 +' + pp); }
    if (re && ((pe.equipo1 === re.equipo1 && pe.equipo2 === re.equipo2) || (pe.equipo1 === re.equipo2 && pe.equipo2 === re.equipo1))) {
        pts += cp; reasons.push('Cruce exacto +' + cp);
        if (pe.ganador && pe.ganador === re.ganador) { pts += gp; reasons.push('Ganador correcto +' + gp); }
    }
    if (pts > 0 || reasons.length > 0)
        elimDet.push({ partido_id: rKey, ronda, equipo1: pe.equipo1, equipo2: pe.equipo2, ganador: pe.ganador, pts, reasons });
    sections.eliminatorias += pts;
    }

  const pp2 = pred.podio || {};
  const rp = real.podio;
  const rTop4 = ['campeon', 'subcampeon', 'tercero', 'cuarto'].map(p => rp[p]).filter(Boolean);
  const posLabels = { campeon: 'Campeón', subcampeon: 'Subcampeón', tercero: '3.er lugar', cuarto: '4.° lugar' };
  const posBonus  = { campeon: 5, subcampeon: 4, tercero: 3, cuarto: 2 };
  ['campeon', 'subcampeon', 'tercero', 'cuarto'].forEach(pos => {
    const pred_t = pp2[pos]; const real_t = rp[pos];
    if (!pred_t) { podioDet.push({ pos: posLabels[pos], pred_t, pts: 0, reason: 'Sin predicción' }); return; }
    if (pred_t === real_t) {
      sections.podio += posBonus[pos];
      podioDet.push({ pos: posLabels[pos], pred_t, pts: posBonus[pos], reason: 'Posición exacta +' + posBonus[pos] });
    } else if (rTop4.includes(pred_t)) {
      sections.podio += 1;
      podioDet.push({ pos: posLabels[pos], pred_t, pts: 1, reason: 'En podio, mal posición +1' });
    } else {
      podioDet.push({ pos: posLabels[pos], pred_t, pts: 0, reason: 'Fuera del podio' });
    }
  });

  const total = sections.grupos + sections.terceros + sections.eliminatorias + sections.podio;
  return { total, sections, grupoDet, tercerosDet, elimDet, podioDet, hasReal: true };
}

/* ─── STATE ─── */
let currentTab = 'free';
let allData    = [];
let REAL       = {};

/* ─── INIT ─── */
async function init() {
  try {
    const res  = await fetch('backend/leaderboard.php');
    const data = await res.json();

    REAL = data.real;
    const usersMap = {};
    data.usuarios.forEach(u => usersMap[u.id] = u);

    allData = data.predicciones.map(pred => {
      const user   = usersMap[pred.id_usuario] || {};
      const pod    = data.podios[pred.id]        || {};
      const des    = data.desempates[pred.id]    || null;
      const grupos = data.grupos[pred.id]        || [];
      const elim   = data.eliminatorias[pred.id] || [];
      const pregs  = data.preguntas[pred.id]     || null;
      const thirds = data.terceros?.[pred.id]    || [];
      let pts = 0, result = {};
      if (pred.tipo === 'free') { result = calcPtsFree(pod, REAL.podio); pts = result.total; }
      else { result = calcPts3000({ podio: pod }, REAL, pred.id, grupos, elim, thirds); pts = result.total; }
      return { pred, user, pts, result, pod, des, pregs, grupos, elim, thirds };
    });

    document.getElementById('last-updated').textContent =
      'Actualizado: ' + new Date().toLocaleString('es-CO', {
        timeZone: 'America/Bogota', hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short'
      });

    renderTable();
  } catch (e) {
    console.error('Error cargando datos:', e);
    document.getElementById('lb-card').innerHTML =
      '<div class="empty-state"><i class="bi bi-wifi-off"></i> Error al cargar datos. Revisa la conexión.</div>';
  }
}

function switchTab(tab, el) {
  currentTab = tab;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderTable();
}

function renderTable() {
  const card = document.getElementById('lb-card');
  const filtered = allData.filter(d => {
    if (currentTab === 'free') return d.pred.tipo === 'free';
    if (currentTab === '3000') return d.pred.tipo === '3000' && d.pred.estado === 'activa';
    return false;
  });
  filtered.sort((a, b) => b.pts - a.pts);

  if (!filtered.length) {
    card.innerHTML = '<div class="empty-state">No hay predicciones para mostrar.</div>';
    return;
  }

  const hasReal = REAL.podio?.campeon !== null && REAL.podio?.campeon !== undefined;
  const maxPts  = currentTab === 'free' ? MAX_PTS_FREE : MAX_PTS_3000;

  let html = '<table class="lb-table"><thead><tr>';
  html += '<th class="center">#</th>';
  html += '<th>Participante</th>';
  html += '<th class="center">Código</th>';
  if (currentTab === '3000') html += '<th class="center">Estado</th>';
  html += '<th class="center">Polla ID</th>';
  html += '<th class="center">Pts</th>';
  html += '<th class="center">Detalle</th>';
  html += '</tr></thead><tbody>';

  filtered.forEach((d, i) => {
    const rank = i + 1;
    const rankIcon = rank === 1
      ? '<i class="bi bi-trophy-fill rank-1"></i>'
      : rank === 2
        ? '<i class="bi bi-award-fill rank-2"></i>'
        : rank === 3
          ? '<i class="bi bi-award-fill rank-3"></i>'
          : `<span class="rank-num">${rank}</span>`;
    html += `<tr onclick="openModal(${d.pred.id})">`;
    html += `<td class="center">${rankIcon}</td>`;
    html += `<td><div style="font-weight:600;font-size:.85rem;">${d.user.nombre}</div><div style="font-size:.72rem;color:var(--text-m);">${d.user.proyecto}</div></td>`;
    html += `<td class="center"><span class="code-pill">${d.user.codigo}</span></td>`;
    if (currentTab === '3000')
      html += `<td class="center"><span class="status-badge status-${d.pred.estado}">${d.pred.estado === 'activa' ? '<i class="bi bi-check-circle-fill"></i> Activa' : '<i class="bi bi-hourglass-split"></i> Pendiente'}</span></td>`;
    html += `<td class="center" style="color:var(--text-m);font-size:.8rem;">#${d.pred.id}</td>`;
    html += `<td class="center"><span class="pts-cell">${hasReal ? d.pts : '—'}</span>${hasReal ? `<div class="pts-max">/ ${maxPts} pts</div>` : ''}</td>`;
    html += `<td class="center"><button class="btn-info" onclick="event.stopPropagation();openModal(${d.pred.id})"><i class="bi bi-eye"></i> Ver pronóstico</button></td>`;
    html += '</tr>';
  });

  html += '</tbody></table>';
  card.innerHTML = html;
}

function matchCardHTML(pe, matchNum, ronda, elimDet = []) {
  const isFinal = ronda === 'Final';
  const isTP    = ronda === '3er Puesto';
  const w = pe.ganador;

  const cls1 = w ? (w === pe.equipo1 ? 'ko-team winner' : 'ko-team') : 'ko-team';
  const cls2 = w ? (w === pe.equipo2 ? 'ko-team winner' : 'ko-team') : 'ko-team';

  const badge1 = w === pe.equipo1 ? '<span class="ko-win-badge"><i class="bi bi-check2"></i></span>' : '';
  const badge2 = w === pe.equipo2 ? '<span class="ko-win-badge"><i class="bi bi-check2"></i></span>' : '';

  const numLabel = `P${matchNum}${isTP ? ' · 3.er puesto' : ''}`;
  const det = elimDet.find(x => x.partido_id === pe.partido_id);
  const ptsTag = det
    ? `<span class="ko-pts-badge ${det.pts > 0 ? 'ko-pts-pos' : 'ko-pts-zero'}" title="${det.reasons.join(', ')}">+${det.pts} pts</span>`
    : '';

  return `
    <div class="ko-match${isFinal ? ' ko-final' : isTP ? ' tp-ko' : ''}">
      <div class="ko-match-num">${numLabel} ${ptsTag}</div>
      <div class="${cls1}">
        <span class="ko-name">
          ${flagImg(pe.equipo1, 20)}
          <span>${pe.equipo1 || '?'}</span>
          ${badge1}
        </span>
      </div>
      <div class="${cls2}">
        <span class="ko-name">
          ${flagImg(pe.equipo2, 20)}
          <span>${pe.equipo2 || '?'}</span>
          ${badge2}
        </span>
      </div>
    </div>`;
}

function maxMatchesInRound(byRound) {
  return Math.max(...ROUND_ORDER_DISPLAY.map(r => (byRound[r] || []).length || 1));
}

function buildBracketHTML(elim, elimDet = []) {
  const byRound = {};
  ROUND_ORDER_DISPLAY.forEach(r => byRound[r] = []);
  byRound['3er Puesto'] = [];

  elim.forEach(pe => {
    const r = RONDA_MAP[pe.ronda] || pe.ronda;
    if (!byRound[r]) byRound[r] = [];
    byRound[r].push({ ...pe, ronda: r });
  });

  const maxMatches = maxMatchesInRound(byRound);
  const bracketH = Math.max(480, maxMatches * 110);

  let cols = ROUND_ORDER_DISPLAY.map(ronda => {
    const partidos = byRound[ronda] || [];
    const matches = partidos.length
      ? partidos.map((pe, i) =>
          matchCardHTML(pe, (ROUND_MATCH_START[ronda] || 0) + i, ronda, elimDet) // ← pasa elimDet
        ).join('')
      : `<div class="ko-match empty">Sin partidos</div>`;
    return `
      <div class="bracket-col-modal">
        <div class="bracket-col-title">${ROUND_LABELS[ronda]}</div>
        <div class="bracket-col-matches">${matches}</div>
      </div>`;
  }).join('');

  const tpPartidos = byRound['3er Puesto'] || [];
  const tpCol = tpPartidos.length ? `
    <div class="bracket-col-modal bracket-col-tp">
      <div class="bracket-col-title">3.er puesto</div>
      <div class="bracket-col-matches">
        ${tpPartidos.map((pe, i) => matchCardHTML(pe, (ROUND_MATCH_START['3er Puesto'] || 0) + i, '3er Puesto', elimDet)).join('')}
      </div>
    </div>` : '';

  return `
    <div class="sec-lbl" style="margin-top:1.25rem;">
      <i class="bi bi-diagram-2-fill"></i> Fase Eliminatoria
    </div>
    <div class="bracket-modal-wrap">
      <div class="bracket-modal-scroll">
        <div class="bracket-modal-inner" style="min-height:${bracketH}px">
          ${cols}
          ${tpCol}
        </div>
      </div>
    </div>`;
}

/* ─── MODAL ─── */
function openModal(predId) {
  const d = allData.find(x => x.pred.id == predId);
  if (!d) return;

  
  const overlay = document.getElementById('modalOverlay');
  const body    = document.getElementById('modal-body');
  document.getElementById('modal-title').textContent = `Polla #${d.pred.id} — ${d.user.nombre}`;
  overlay.classList.remove('hidden');

  const hasReal = REAL.podio?.campeon !== null && REAL.podio?.campeon !== undefined;
  const r   = d.result;
  const pod = d.pod;

  const posIcons = {
    campeon:    '<i class="bi bi-trophy-fill" style="color:var(--gold);"></i>',
    subcampeon: '<i class="bi bi-2-circle-fill" style="color:var(--silver);"></i>',
    tercero:    '<i class="bi bi-3-circle-fill" style="color:var(--bronze);"></i>',
    cuarto:     '<i class="bi bi-4-circle-fill" style="color:var(--text-s);"></i>',
    'Campeón':    '<i class="bi bi-trophy-fill" style="color:var(--gold);"></i>',
    'Subcampeón': '<i class="bi bi-2-circle-fill" style="color:var(--silver);"></i>',
    '3.er lugar': '<i class="bi bi-3-circle-fill" style="color:var(--bronze);"></i>',
    '4.° lugar':  '<i class="bi bi-4-circle-fill" style="color:var(--text-s);"></i>',
  };

  let html = '';

  if (!hasReal) {
    html += `<div class="no-real"><i class="bi bi-exclamation-triangle-fill"></i> Aún no hay resultados reales registrados. Se muestra el pronóstico sin puntuación.</div>`;
  }

  /* ══ FREE ══ */
  if (d.pred.tipo === 'free') {
    html += `<div class="pts-summary">
      <div class="pts-card highlight">
        <div class="pts-card-val">${hasReal ? r.total : '—'}</div>
        <div class="pts-card-lbl">Pts Total</div>
      </div>
      <div class="pts-card">
        <div class="pts-card-val" style="font-size:.9rem;">/ ${MAX_PTS_FREE}</div>
        <div class="pts-card-lbl">Máx posible</div>
      </div>
    </div>`;

    html += `<div class="sec-lbl"><i class="bi bi-trophy"></i> Pronóstico de Podio</div>`;
    html += '<div class="pred-list">';
    if (hasReal && r.breakdown) {
      r.breakdown.forEach(b => {
        const cls = b.pts > 7 ? 'pts-pos' : b.pts > 0 ? 'pts-par' : 'pts-neg';
        html += `<div class="pred-row">
          <span class="pred-row-icon">${posIcons[b.pos] || ''}</span>
          <div class="pred-row-team">${teamCell(b.pred || '—')}</div>
          <div class="pred-row-right">
            <span class="pts-earned ${cls}">${b.pts > 0 ? '+' : ''}${b.pts} pts</span>
            <span class="pred-row-reason">${b.reason}</span>
          </div>
        </div>`;
      });
    } else {
      ['campeon', 'subcampeon', 'tercero', 'cuarto'].forEach(pos => {
        html += `<div class="pred-row">
          <span class="pred-row-icon">${posIcons[pos]}</span>
          <div class="pred-row-team">${teamCell(pod[pos] || '—')}</div>
        </div>`;
      });
    }
    html += '</div>';

    if (d.des) {
      html += `<div class="sec-lbl"><i class="bi bi-lightning-charge-fill"></i> Desempate</div><div class="pred-list">`;
      [
        ['goleador','Goleador (Bota de Oro)','bi-dribbble'],
        ['mejor_arquero','Mejor Portero','bi-shield-fill'],
        ['goles_final','Goles en la Final','bi-123'],
        ['tarjetas_rojas','Tarjetas Rojas','bi-exclamation-triangle-fill'],
        ['goles_grupos','Goles Fase Grupos','bi-123']
      ].forEach(([k, lbl, icon]) => {
        if (d.des[k] !== undefined)
          html += `<div class="pred-row">
            <span class="pred-row-icon"><i class="bi ${icon}" style="color:var(--accent-l);"></i></span>
            <div class="pred-row-team"><span class="pred-row-label">${lbl}</span></div>
            <div class="pred-row-right"><span class="pred-row-value">${d.des[k]}</span></div>
          </div>`;
      });
      html += '</div>';
    }

    if (d.pregs) {
      html += `<div class="sec-lbl"><i class="bi bi-question-circle-fill"></i> Preguntas Extra</div><div class="pred-list">`;
      [
        ['equipo_sorpresa','Equipo Sorpresa','bi-lightning-charge'],
        ['equipo_decepcion','Equipo Decepción','bi-emoji-frown'],
        ['jugador_joven','Jugador Joven','bi-person-bounding-box'],
        ['seleccion_goles','Selección más Goleadora','bi-people-fill'],
        ['seleccion_defensa','Selección menos Goleada','bi-shield-x'],
        ['prorroga_final','Prórroga en la Final','bi-arrow-repeat']
      ].forEach(([k, lbl, icon]) => {
        if (d.pregs[k])
          html += `<div class="pred-row">
            <span class="pred-row-icon"><i class="bi ${icon}" style="color:var(--accent-l);"></i></span>
            <div class="pred-row-team"><span class="pred-row-label">${lbl}</span></div>
            <div class="pred-row-right"><span class="pred-row-value">${d.pregs[k]}</span></div>
          </div>`;
      });
      html += '</div>';
    }

  /* ══ $3000 ══ */
  } else {
    html += `<div class="pts-summary">
      <div class="pts-card highlight"><div class="pts-card-val">${hasReal ? r.total : '—'}</div><div class="pts-card-lbl">Total</div></div>
      <div class="pts-card"><div class="pts-card-val">${hasReal ? r.sections.grupos : '—'}</div><div class="pts-card-lbl">Grupos</div></div>
      <div class="pts-card"><div class="pts-card-val">${hasReal ? (r.sections.terceros ?? '0') : '—'}</div><div class="pts-card-lbl">Terceros</div></div>
      <div class="pts-card"><div class="pts-card-val">${hasReal ? r.sections.eliminatorias : '—'}</div><div class="pts-card-lbl">Eliminat.</div></div>
      <div class="pts-card"><div class="pts-card-val">${hasReal ? r.sections.podio : '—'}</div><div class="pts-card-lbl">Podio</div></div>
      <div class="pts-card"><div class="pts-card-val" style="font-size:.9rem;">/ ${MAX_PTS_3000}</div><div class="pts-card-lbl">Máx posible</div></div>
    </div>`;

    console.log('GRUPOS:', JSON.stringify(d.grupos[0]));
    if (d.grupos.length) {
      html += `<div class="sec-lbl"><i class="bi bi-grid-3x3-gap-fill"></i> Fase de Grupos</div>`;
      html += `<div class="group-grid-m">`;
      d.grupos.forEach(pg => {
        html += `<div class="group-block"><div class="group-block-hdr">Grupo ${pg.grupo}</div>`;
        ['primero', 'segundo', 'tercero', 'cuarto'].forEach((pos, i) => {
          let indicator = '';
          if (hasReal && r.grupoDet) {
            const dets = r.grupoDet.filter(x => x.team === pg[pos]);
            const det = dets.length ? dets.reduce((a, b) => a.pts >= b.pts ? a : b) : null;
            if (det) indicator = det.pts >= 5
              ? '<span class="g-pts g-pts-ok">+5</span>'
              : det.pts >= 3
                ? '<span class="g-pts g-pts-warn">+3</span>'
                : '<span class="g-pts g-pts-err">0</span>';
          }
          html += `<div class="group-team-row">
                <span class="g-pos">${i + 1}.</span>
                <span class="g-team-name-wrap">
                    ${flagImg(pg[pos], 20)}
                    <span class="g-name">${pg[pos] || '—'}</span>
                </span>
                ${indicator}
                </div>`;
        });
        html += '</div>';
      });
      html += '</div>';
    }

    if (d.thirds && d.thirds.length) {
    html += `<div class="sec-lbl"><i class="bi bi-3-circle-fill"></i> Mejores Terceros</div>`;
    html += `<div class="pred-list">`;
    d.thirds.forEach(grupo => {
        const det = (r.tercerosDet || []).find(x => x.equipo === grupo);
        const pts = det ? det.pts : 0;
        const cls = pts > 0 ? 'pts-pos' : 'pts-neg';
        const reason = det ? det.reason : 'Sin resultado aún';
        html += `<div class="pred-row">
        <span class="pred-row-icon"><i class="bi bi-3-circle-fill" style="color:var(--accent-l);"></i></span>
        <span class="g-team-name-wrap">
            ${flagImg(grupo, 20)}
            <span style="font-weight:600;">${grupo}</span>
        </span>
        <div class="pred-row-right">
            <span class="pts-earned ${cls}">${pts > 0 ? '+' + pts : '0'} pts</span>
            <span class="pred-row-reason">${reason}</span>
        </div>
        </div>`;
    });
    html += `</div>`;
    }

    html += buildBracketHTML(d.elim, r.elimDet || []);

    /* Podio */
    html += `<div class="sec-lbl"><i class="bi bi-trophy-fill"></i> Pronóstico de Podio</div>`;
    html += `<div class="podium-grid-modal">`;
    [
      ['campeon','Campeón','p1'],
      ['subcampeon','Subcampeón','p2'],
      ['tercero','3.er lugar','p3'],
      ['cuarto','4.° lugar','']
    ].forEach(([k, lbl, cls]) => {
      let ptsHtml = '';
      if (hasReal && r.podioDet) {
        const det = r.podioDet.find(x => x.pos === lbl);
        if (det) ptsHtml = `<div class="podium-pts" style="color:${det.pts > 1 ? 'var(--ok)' : det.pts > 0 ? 'var(--warn)' : 'var(--err)'};">${det.pts > 0 ? '+' + det.pts + ' pts' : '0 pts'}</div>`;
      }
      html += `<div class="podium-slot-modal ${cls}">
        <div class="podium-pos-icon">${posIcons[lbl]}</div>
        <div class="podium-pos-lbl">${lbl}</div>
        <div class="podium-team-row">${flagImg(pod[k], 20)}<span class="podium-team-name">${pod[k] || '—'}</span></div>
        ${ptsHtml}
      </div>`;
    });
    html += '</div>';

    /* Desempate */
    if (d.des) {
      html += `<div class="sec-lbl"><i class="bi bi-lightning-charge-fill"></i> Desempate</div><div class="pred-list">`;
      [
        ['goleador','Goleador (Bota de Oro)','bi-dribbble'],
        ['mejor_arquero','Mejor Portero','bi-shield-fill'],
        ['goles_final','Goles en la Final','bi-123'],
        ['tarjetas_rojas','Tarjetas Rojas','bi-exclamation-triangle-fill'],
        ['goles_grupos','Goles Fase Grupos','bi-123']
      ].forEach(([k, lbl, icon]) => {
        if (d.des[k] !== undefined)
          html += `<div class="pred-row">
            <span class="pred-row-icon"><i class="bi ${icon}" style="color:var(--accent-l);"></i></span>
            <div class="pred-row-team"><span class="pred-row-label">${lbl}</span></div>
            <div class="pred-row-right"><span class="pred-row-value">${d.des[k]}</span></div>
          </div>`;
      });
      html += '</div>';
    }

    /* Preguntas Extra */
    if (d.pregs) {
      html += `<div class="sec-lbl"><i class="bi bi-question-circle-fill"></i> Preguntas Extra</div><div class="pred-list">`;
      [
        ['equipo_sorpresa','Equipo Sorpresa','bi-lightning-charge'],
        ['equipo_decepcion','Equipo Decepción','bi-emoji-frown'],
        ['jugador_joven','Jugador Joven','bi-person-bounding-box'],
        ['seleccion_goles','Selección más Goleadora','bi-people-fill'],
        ['seleccion_defensa','Selección menos Goleada','bi-shield-x'],
        ['prorroga_final','Prórroga en la Final','bi-arrow-repeat']
      ].forEach(([k, lbl, icon]) => {
        if (d.pregs[k])
          html += `<div class="pred-row">
            <span class="pred-row-icon"><i class="bi ${icon}" style="color:var(--accent-l);"></i></span>
            <div class="pred-row-team"><span class="pred-row-label">${lbl}</span></div>
            <div class="pred-row-right"><span class="pred-row-value">${d.pregs[k]}</span></div>
          </div>`;
      });
      html += '</div>';
    }
  }

  body.innerHTML = html;
}

    

function closeModal() {
  document.getElementById('modalOverlay').classList.add('hidden');
}
function closeModalOutside(e) {
  if (e.target === document.getElementById('modalOverlay')) closeModal();
}

init();