const STRINGS = 6;
const FRETS = 8;
const STRING_NOTES = ['E4', 'B3', 'G3', 'D3', 'A2', 'E2'];
const NOTE_NAMES = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

(function(){
  // ── Canvas setup ──────────────────────────────────────────
  const cv = document.getElementById('pixelCanvas');
  const g  = cv.getContext('2d');
  g.imageSmoothingEnabled = false;
  // Virtual resolution: 160 × 104 "pixels" rendered at 3× scale
  const VW=160, VH=104, SC=3;
  cv.width=VW*SC; cv.height=VH*SC;

  // ── Palette ───────────────────────────────────────────────
  const _ = null; // transparent
  const C = {
    // Sky
    s0:'#090d18', s1:'#0d1526', s2:'#111e35',
    // Ground
    gr:'#173217', gd:'#0f2410', gl:'#1d4a1d', gLine:'#1f5020',
    // Crowd
    cr0:'#181c30', cr1:'#121626', cr2:'#0e1220',
    // Skin
    sk:'#f0a060', skS:'#c07030', skD:'#a05828',
    // Hair
    hr:'#180600',
    // Player 1 — amber
    s1a:'#f59e0b', s1b:'#b57000',
    // Player 2 — blue
    s2a:'#2563eb', s2b:'#1040a0',
    // Clothing
    sh:'#0a0a0a', shS:'#1a1a1a',
    sk1:'#efefef', sk2:'#bbbbbb',
    shoe:'#181818', shoeS:'#333333',
    // Ball
    ba:'#f0f0f0', baS:'#222222', baSh:'#cccccc',
    // Goal
    po:'#d8d8d8',
    // Sign
    sgBg:'#0c0c0c', sgBd:'#f59e0b', sgBdI:'#d97706',
    // IEEE logo
    ie0:'#004f78', ie1:'#0080bb', ieTx:'#ffffff',
    bolt:'#ffd700',
    // FX
    sp0:'#ffffa0', sp1:'#f59e0b',
    conf:[  '#ef4444','#f59e0b','#3b82f6','#22c55e','#fbbf24','#ffffff','#a855f7'],
    // General
    wh:'#ffffff',
  };

  // ── Draw utils ────────────────────────────────────────────
  function px(x,y,c){ if(!c)return; g.fillStyle=c; g.fillRect(x*SC,y*SC,SC,SC); }
  function bk(x,y,w,h,c){ if(!c)return; g.fillStyle=c; g.fillRect(x*SC,y*SC,w*SC,h*SC); }

  // ── Ground Y ──────────────────────────────────────────────
  const GY = 70;

  // ── Pixel font ────────────────────────────────────────────
  const FONT = {
    ' ':[[0]],
    'I':[[0,1,0],[0,1,0],[0,1,0],[0,1,0],[0,1,0]],
    'E':[[1,1,1],[1,0,0],[1,1,0],[1,0,0],[1,1,1]],
    'P':[[1,1,0],[1,0,1],[1,1,0],[1,0,0],[1,0,0]],
    'O':[[0,1,0],[1,0,1],[1,0,1],[1,0,1],[0,1,0]],
    'L':[[1,0],[1,0],[1,0],[1,0],[1,1]],
    'A':[[0,1,0],[1,0,1],[1,1,1],[1,0,1],[1,0,1]],
    'M':[[1,0,0,0,1],[1,1,0,1,1],[1,0,1,0,1],[1,0,0,0,1],[1,0,0,0,1]],
    'U':[[1,0,1],[1,0,1],[1,0,1],[1,0,1],[0,1,0]],
    'N':[[1,0,1],[1,1,1],[1,1,1],[1,0,1],[1,0,1]],
    'D':[[1,1,0],[1,0,1],[1,0,1],[1,0,1],[1,1,0]],
  };
  function txt(str, ox, oy, col, sc=1){
    let dx=ox;
    for(const ch of str){
      const gl=FONT[ch]||FONT[' '];
      for(let r=0;r<gl.length;r++) for(let c2=0;c2<gl[r].length;c2++)
        if(gl[r][c2]) bk(dx+c2*sc, oy+r*sc, sc, sc, col);
      dx += (gl[0].length+1)*sc;
    }
  }

  // ── Player ────────────────────────────────────────────────
  // frame: 0/1=walk  2=kick  3=celebrate
  function drawPlayer(ox, oy, shirtA, shirtB, facingLeft, frame, armsUp){
    g.save();
    if(facingLeft){ g.translate((ox+10)*SC,0); g.scale(-1,1); ox=0; }

    const fr=frame;
    // Shadow
    bk(ox+1,oy+16,8,1,'rgba(0,0,0,0.2)');

    // HEAD
    bk(ox+3,oy,   4,1, C.hr);           // hair top
    bk(ox+2,oy+1, 6,4, C.sk);           // face
    px(ox+2,oy+1, C.hr); px(ox+7,oy+1, C.hr); // sideburn
    px(ox+3,oy+2, '#1a0800'); px(ox+6,oy+2,'#1a0800'); // eyes
    px(ox+4,oy+4, C.skS);               // chin shadow

    // NECK
    bk(ox+4,oy+5,2,1,C.sk);

    // BODY
    bk(ox+2,oy+6, 6,5, shirtA);
    bk(ox+2,oy+6, 1,5, shirtB);         // left shadow
    px(ox+4,oy+6,C.wh); px(ox+5,oy+6,C.wh); // collar

    // ARMS
    if(armsUp){
      bk(ox+0,oy+4,2,3,shirtA); px(ox+0,oy+3,C.sk); px(ox+0,oy+2,C.sk);
      bk(ox+8,oy+4,2,3,shirtA); px(ox+9,oy+3,C.sk); px(ox+9,oy+2,C.sk);
    } else if(fr===2){
      bk(ox+0,oy+6,2,3,shirtA); px(ox+0,oy+9,C.sk);
      bk(ox+8,oy+5,2,2,shirtA); px(ox+9,oy+4,C.sk);
    } else {
      const sw=(fr===0)?1:-1;
      bk(ox+0,oy+6+sw,2,3,shirtA); px(ox+0,oy+9+sw,C.sk);
      bk(ox+8,oy+6-sw,2,3,shirtA); px(ox+9,oy+9-sw,C.sk);
    }

    // SHORTS
    bk(ox+2,oy+11,6,2,C.sh);
    px(ox+2,oy+12,C.shS); px(ox+7,oy+12,C.shS);

    // LEGS
    if(fr===2){
      // standing leg
      bk(ox+2,oy+13,2,3,C.sk1); px(ox+2,oy+14,C.sk2);
      bk(ox+2,oy+16,3,1,C.shoe);
      // kicking leg (raised)
      bk(ox+6,oy+11,3,2,C.sk1);
      bk(ox+7,oy+10,3,1,C.shoe);
    } else if(fr===3){
      bk(ox+1,oy+13,2,3,C.sk1); px(ox+1,oy+14,C.sk2); bk(ox+1,oy+16,3,1,C.shoe);
      bk(ox+6,oy+13,2,3,C.sk1); px(ox+7,oy+14,C.sk2); bk(ox+6,oy+16,3,1,C.shoe);
    } else {
      const lA=(fr===0)?1:0, lB=(fr===0)?0:1;
      bk(ox+2,oy+13+lA,2,3,C.sk1); px(ox+2,oy+14+lA,C.sk2); bk(ox+2+lA,oy+16,3,1,C.shoe);
      bk(ox+5,oy+13+lB,2,3,C.sk1); px(ox+6,oy+14+lB,C.sk2); bk(ox+5+lB,oy+16,3,1,C.shoe);
    }

    g.restore();
  }

  // ── Ball ─────────────────────────────────────────────────
  function drawBall(bx,by,spin){
    const s=Math.floor(spin)%4;
    bk(bx+1,by,  4,1,C.ba); bk(bx,by+1,6,4,C.ba); bk(bx+1,by+5,4,1,C.ba);
    bk(bx+1,by+1,2,1,C.baSh); bk(bx+1,by+5,2,1,C.baSh);
    const seamPt=[[2,2],[1,3],[2,2],[3,3]];
    const[sx,sy]=seamPt[s]; px(bx+sx,by+sy,C.baS); px(bx+sx+1,by+sy,C.baS);
    bk(bx+1,by+6,4,1,'rgba(0,0,0,0.2)');
  }

  // ── Goal ──────────────────────────────────────────────────
  function drawGoal(gx,gy){
    bk(gx,gy-14,1,14,C.po); bk(gx+12,gy-14,1,14,C.po); bk(gx,gy-14,13,1,C.po);
    for(let y=gy-13;y<gy;y+=2) for(let x2=gx+1;x2<gx+12;x2+=2)
      px(x2,y,'rgba(255,255,255,0.06)');
  }

  // ── IEEE Sign ─────────────────────────────────────────────
  function drawSign(cx2,cy2,prog){
    if(prog<=0) return;
    const SW=58,SH=24, ox=Math.round(cx2-SW/2), oy=Math.round(cy2);
    g.save(); g.globalAlpha=Math.min(prog,1);

    // shadow
    bk(ox+2,oy+2,SW,SH,'rgba(0,0,0,0.55)');
    // body
    bk(ox,oy,SW,SH,C.sgBg);
    // border outer
    for(let x=ox;x<ox+SW;x++){ px(x,oy,C.sgBd); px(x,oy+SH-1,C.sgBd); }
    for(let y=oy;y<oy+SH;y++){ px(ox,y,C.sgBd); px(ox+SW-1,y,C.sgBd); }
    // border inner
    for(let x=ox+2;x<ox+SW-2;x++){ px(x,oy+2,C.sgBdI); px(x,oy+SH-3,C.sgBdI); }
    for(let y=oy+2;y<oy+SH-2;y++){ px(ox+2,y,C.sgBdI); px(ox+SW-3,y,C.sgBdI); }

    // IEEE logo circle — lx,ly top-left of 14x14 circle
    const lx=ox+5, ly=oy+5;
    // ring (amber)
    bk(lx+2,ly,   10,1,C.sgBd); bk(lx+2,ly+13,10,1,C.sgBd);
    bk(lx,  ly+2, 1,10,C.sgBd); bk(lx+13,ly+2,1,10,C.sgBd);
    px(lx+1,ly+1,C.sgBd); px(lx+12,ly+1,C.sgBd);
    px(lx+1,ly+12,C.sgBd); px(lx+12,ly+12,C.sgBd);
    // fill outer ring → inner
    bk(lx+1,ly+1,12,12,C.ie0);
    bk(lx+2,ly+2,10,10,C.ie1);
    bk(lx+3,ly+3, 8, 8,C.ie0);
    // lightning bolt inside circle
    px(lx+7,ly+4,C.bolt); px(lx+8,ly+4,C.bolt);
    px(lx+6,ly+5,C.bolt); px(lx+7,ly+5,C.bolt);
    px(lx+5,ly+6,C.bolt); px(lx+6,ly+6,C.bolt); px(lx+7,ly+6,C.bolt);
    px(lx+6,ly+7,C.bolt); px(lx+7,ly+7,C.bolt); px(lx+8,ly+7,C.bolt);
    px(lx+7,ly+8,C.bolt); px(lx+8,ly+8,C.bolt);
    px(lx+6,ly+9,C.bolt); px(lx+7,ly+9,C.bolt);

    // "IEEE" — large 2× text
    txt('IEEE', ox+22, oy+5, C.sgBd, 2);
    // "POLLA MUNDIALISTA" — 1× small underneath... use "MUNDIAL" to fit
    txt('POLLA', ox+21, oy+15, '#888888', 1);

    g.restore();
  }

  // ── Confetti pool ─────────────────────────────────────────
  const CONF = Array.from({length:45},(_,i)=>({
    x: Math.random()*VW, y: -4-Math.random()*24,
    vx:(Math.random()-.5)*.7, vy:.35+Math.random()*.55,
    col: C.conf[Math.floor(Math.random()*C.conf.length)],
    sz: Math.random()<.6?1:2,
    delay: 35+Math.floor(Math.random()*55),
    active:false,
  }));

  // ── Mutable state ─────────────────────────────────────────
  let t=0, phT=0, phase='run';
  let p1={x:-12,y:GY-18}, p2={x:132,y:GY-18};
  let ball={x:60,y:GY-7,vx:0,vy:0,spin:0};
  let sigProg=0, sigBounc=0;

  const phLabel  = document.getElementById('phaseLabel');
  const phaseDts = document.querySelectorAll('.pdot');
  const phTexts  = ['Corriendo...','¡Tiro al arco!','¡GOOOOL!'];
  function setPhase(i){ phaseDts.forEach((d,j)=>d.classList.toggle('active',j===i)); phLabel.textContent=phTexts[i]; }

  // ── Draw background ───────────────────────────────────────
  function drawBG(){
    // Sky gradient rows
    for(let y=0;y<GY;y++){
      const r=Math.floor(9+y*.5), gb=Math.floor(13+y*.9);
      bk(0,y,VW,1,`rgb(${r},${gb},${Math.floor(gb*1.6)})`);
    }
    // Stars
    [[5,4,1],[14,9,0],[25,3,1],[38,11,0],[52,6,1],[68,13,0],[83,4,1],[97,10,0],
     [112,7,1],[125,4,0],[8,16,1],[30,19,0],[58,21,1],[78,16,0],[103,19,1]].forEach(([x,y,ph])=>{
      const tw=Math.sin(t*.055+ph+x*.25)>.35;
      px(x,y,tw?C.wh:'#2e3050');
    });
    // Crowd silhouette
    for(let x=0;x<VW;x++){
      const h=2+(Math.sin(x*.35+1)>.0?1:0)+(Math.sin(x*.71)>.3?1:0);
      const row2=1+(Math.sin(x*.42+2)>.0?1:0);
      bk(x,GY-8-h,1,h+1, x%6===0?C.cr1:C.cr0);
      bk(x,GY-4-row2,1,row2+1,C.cr2);
    }
    // Ground stripes
    for(let x=0;x<VW;x+=10){ bk(x,GY,5,VH-GY,C.gr); bk(x+5,GY,5,VH-GY,C.gd); }
    bk(0,GY,VW,1,C.gLine);
    // Dashed center line
    for(let x=0;x<VW;x+=6){ bk(x,GY,3,1,C.gl); }
  }

  // ── Main render loop ──────────────────────────────────────
  function draw(){
    g.clearRect(0,0,cv.width,cv.height);
    drawBG();
    drawGoal(134,GY);

    phT++;

    if(phase==='run'){
      setPhase(0);
      if(p1.x<44){ p1.x+=1.5; }
      else{ phase='kick'; phT=0; ball.vx=2.6; ball.vy=-2.4; }
    }
    else if(phase==='kick'){
      setPhase(1);
      ball.x+=ball.vx; ball.y+=ball.vy; ball.vy+=.21; ball.spin+=ball.vx*.45;
      if(ball.y>=GY-7){ ball.y=GY-7; ball.vy*=-.38; ball.vx*=.8; }
      // P2 jump reaction
      const jt=phT-18;
      if(jt>0&&jt<24) p2.y=GY-18-Math.sin(jt/24*Math.PI)*6;
      else p2.y=GY-18;
      if(ball.x>128&&phT>28){ phase='celebrate'; phT=0; ball.vx=.08; ball.vy=0; }
    }
    else if(phase==='celebrate'){
      setPhase(2);
      sigProg=Math.min(1,sigProg+.038);
      sigBounc=Math.sin(phT*.14)*2.2;
      // Confetti
      CONF.forEach(c=>{
        if(phT>=c.delay) c.active=true;
        if(!c.active)return;
        c.x+=c.vx; c.y+=c.vy;
        if(c.y>VH+2){ c.y=-2; c.x=Math.random()*VW; }
        bk(Math.floor(c.x),Math.floor(c.y),c.sz,c.sz,c.col);
      });
      // Sparkles
      [[VW/2-31,9],[VW/2+29,9],[VW/2-28,18],[VW/2+26,18]].forEach(([sx,sy])=>{
        if(Math.sin(t*.45+sx*.1)>.2) px(Math.round(sx),Math.round(sy),phT%4<2?C.sp0:C.sp1);
      });
      drawSign(VW/2, 8+sigBounc, sigProg);
      // Player celebration bouncing
      p1.y=GY-18-Math.abs(Math.sin(phT*.17))*5;
      p2.y=GY-18-Math.abs(Math.sin(phT*.17+1.3))*5;
      ball.x+=ball.vx; ball.vx*=.97;
      // Loop
      if(phT>260){
        phase='run'; phT=0; sigProg=0;
        p1.x=-12; p1.y=GY-18;
        ball={x:60,y:GY-7,vx:0,vy:0,spin:0};
        CONF.forEach(c=>{c.active=false; c.y=-4-Math.random()*24;});
      }
    }

    // Entity draws
    // Ball shadow
    bk(Math.floor(ball.x)+1,GY-1,5,1,'rgba(0,0,0,0.22)');
    drawBall(Math.floor(ball.x),Math.floor(ball.y),ball.spin);

    // Kick impact
    if(phase==='kick'&&phT<7){
      const r=phT*2.5;
      for(let a=0;a<360;a+=45){
        const rad=a*Math.PI/180;
        px(Math.round(ball.x+r*Math.cos(rad)),Math.round(ball.y+r*Math.sin(rad)),phT<4?C.sp0:C.sp1);
      }
    }

    // P1 (amber)
    const f1= phase==='kick'&&phT<12?2 : phase==='celebrate'?3 : Math.floor(t/7)%2;
    drawPlayer(Math.floor(p1.x),Math.floor(p1.y), C.s1a,C.s1b, false, f1, phase==='celebrate');

    // P2 (blue)
    const f2= phase==='celebrate'?3 : Math.floor(t/7)%2;
    drawPlayer(Math.floor(p2.x),Math.floor(p2.y), C.s2a,C.s2b, true, f2, phase==='celebrate');

    t++;
    requestAnimationFrame(draw);
  }

  draw();
})();

// Chord shapes (fret positions for each string, -1 = muted, 0 = open)
const CHORDS = {
   'C': {
      frets: [-1, 1, 0, 2, 3, -1],
      fingers: [null, 1, null, 2, 3, null]
   },
   'G': {
      frets: [3, 0, 0, 0, 2, 3],
      fingers: [2, null, null, null, 1, 3]
   },
   'Am': {
      frets: [0, 1, 2, 2, 0, -1],
      fingers: [null, 1, 2, 3, null, null]
   },
   'F': {
      frets: [1, 1, 2, 3, 3, 1],
      fingers: [1, 1, 2, 3, 4, 1]
   },
   'D': {
      frets: [2, 3, 2, 0, -1, -1],
      fingers: [1, 3, 2, null, null, null]
   },
   'Em': {
      frets: [0, 0, 0, 2, 2, 0],
      fingers: [null, null, null, 1, 2, null]
   }
};

// Songs data - [string, fret, duration in ms]
const SONGS = {
   greensleeves: {
      name: 'Greensleeves',
      tempo: 400,
      notes: [
         [2, 0, 1],
         [1, 1, 1],
         [0, 3, 2],
         [0, 5, 1],
         [0, 3, 1],
         [0, 1, 2],
         [1, 0, 1],
         [2, 0, 1],
         [1, 1, 2],
         [2, 0, 1],
         [1, 1, 1],
         [0, 0, 2],
         [0, 0, 1],
         [1, 0, 1],
         [0, 1, 2],
         [0, 3, 1],
         [0, 5, 1],
         [0, 3, 2],
         [0, 1, 1],
         [1, 0, 1],
         [2, 0, 2],
         [1, 1, 1],
         [2, 0, 1],
         [1, 1, 2]
      ]
   },
   houseoftherisingsun: {
      name: 'House of the Rising Sun',
      tempo: 350,
      notes: [
         [4, 0, 1],
         [3, 2, 1],
         [2, 2, 1],
         [1, 1, 1],
         [2, 2, 1],
         [3, 2, 1],
         [4, 2, 1],
         [3, 2, 1],
         [2, 0, 1],
         [1, 1, 1],
         [2, 0, 1],
         [3, 2, 1],
         [4, 0, 1],
         [3, 2, 1],
         [2, 1, 1],
         [1, 0, 1],
         [2, 1, 1],
         [3, 2, 1],
         [4, 2, 1],
         [3, 2, 1],
         [2, 2, 1],
         [1, 1, 1],
         [2, 2, 1],
         [3, 2, 1]
      ]
   },
   amazinggrace: {
      name: 'Amazing Grace',
      tempo: 500,
      notes: [
         [3, 0, 1],
         [2, 0, 2],
         [1, 1, 1],
         [2, 0, 1],
         [1, 1, 2],
         [1, 0, 1],
         [2, 0, 3],
         [3, 2, 1],
         [3, 0, 2],
         [2, 0, 1],
         [1, 1, 1],
         [2, 0, 1],
         [1, 1, 2],
         [0, 0, 1],
         [0, 3, 3],
         [0, 3, 1],
         [0, 0, 2],
         [1, 1, 1],
         [2, 0, 1],
         [1, 1, 2],
         [1, 0, 1],
         [2, 0, 3]
      ]
   }
};

let soundEnabled = true;
let isPlaying = false;
let currentSong = 'greensleeves';
let songTimeout = null;
let noteIndex = 0;

// Single shared AudioContext
let audioCtx = null;
let compressor = null;

function getAudioContext() {
   if (!audioCtx) {
      audioCtx = new(window.AudioContext || window.webkitAudioContext)();
      // Add compressor to prevent clipping and reduce pops
      compressor = audioCtx.createDynamicsCompressor();
      compressor.threshold.value = -24;
      compressor.knee.value = 30;
      compressor.ratio.value = 12;
      compressor.attack.value = 0.003;
      compressor.release.value = 0.25;
      compressor.connect(audioCtx.destination);
   }
   // Resume if suspended (browsers require user interaction)
   if (audioCtx.state === 'suspended') {
      audioCtx.resume();
   }
   return audioCtx;
}

// Initialize fretboard
function initFretboard() {
   const fretboard = document.getElementById('fretboard');
   const grid = document.createElement('div');
   grid.style.display = 'contents';

   for (let string = 0; string < STRINGS; string++) {
      for (let fret = 0; fret < FRETS; fret++) {
         const fretEl = document.createElement('div');
         fretEl.className = 'fret';
         fretEl.dataset.string = string;
         fretEl.dataset.fret = fret;

         // Add fret markers
         if (string === 2 && [2, 4, 6].includes(fret)) {
            const marker = document.createElement('div');
            marker.className = 'fret-marker';
            fretEl.appendChild(marker);
         }

         // Add note marker
         const noteMarker = document.createElement('div');
         noteMarker.className = 'note-marker';
         noteMarker.textContent = getNoteAtPosition(string, fret);
         fretEl.appendChild(noteMarker);

         fretEl.addEventListener('click', () => playNote(string, fret));
         fretboard.appendChild(fretEl);
      }
   }
}

function getNoteAtPosition(string, fret) {
   const baseNote = STRING_NOTES[string];
   const baseNoteIndex = NOTE_NAMES.indexOf(baseNote.slice(0, -1).replace('b', '#'));
   const noteIndex = (baseNoteIndex + fret + 1) % 12;
   return NOTE_NAMES[noteIndex];
}

function getFrequency(string, fret) {
   const baseFreqs = [329.63, 246.94, 196.00, 146.83, 110.00, 82.41];
   return baseFreqs[string] * Math.pow(2, fret / 12);
}

function playNote(string, fret, showMarker = true) {
   // Visual feedback first
   if (showMarker) {
      const fretEl = document.querySelector(`[data-string="${string}"][data-fret="${fret}"]`);
      if (fretEl) {
         const marker = fretEl.querySelector('.note-marker');
         marker.classList.add('show', 'playing');
         setTimeout(() => marker.classList.remove('playing'), 300);
      }
   }

   if (!soundEnabled) return;

   try {
      const ctx = getAudioContext();
      const freq = getFrequency(string, fret);

      // Create oscillators for guitar-like tone
      const osc1 = ctx.createOscillator();
      const osc2 = ctx.createOscillator();
      const gainNode = ctx.createGain();
      const filter = ctx.createBiquadFilter();

      osc1.type = 'triangle';
      osc2.type = 'sine';
      osc1.frequency.value = freq;
      osc2.frequency.value = freq * 2;

      filter.type = 'lowpass';
      filter.frequency.value = 1800;
      filter.Q.value = 0.7;

      osc1.connect(filter);
      osc2.connect(filter);
      filter.connect(gainNode);
      // Route through compressor to prevent clipping
      gainNode.connect(compressor);

      // Smoother guitar-like envelope with softer attack
      const now = ctx.currentTime;
      gainNode.gain.setValueAtTime(0.001, now);
      // Soft attack to prevent click
      gainNode.gain.exponentialRampToValueAtTime(0.15, now + 0.015);
      // Quick decay to sustain
      gainNode.gain.exponentialRampToValueAtTime(0.08, now + 0.1);
      // Gradual release
      gainNode.gain.exponentialRampToValueAtTime(0.001, now + 1.2);

      osc1.start(now);
      osc2.start(now);
      osc1.stop(now + 1.2);
      osc2.stop(now + 1.2);
   } catch (e) {
      console.log('Audio error:', e);
   }
}

function showChord(chordName) {
   // Clear previous
   clearNotes();

   // Highlight active button
   document.querySelector(`[data-chord="${chordName}"]`).classList.add('active');

   const chord = CHORDS[chordName];
   const notesToPlay = [];

   chord.frets.forEach((fret, string) => {
      if (fret >= 0) {
         const actualFret = fret === 0 ? 0 : fret - 1;
         const fretEl = document.querySelector(`[data-string="${string}"][data-fret="${actualFret}"]`);
         if (fretEl) {
            const marker = fretEl.querySelector('.note-marker');
            marker.classList.add('show');
            notesToPlay.push({
               string,
               fret: actualFret
            });
         }
      }
   });

   // Play chord with strum effect
   if (soundEnabled) {
      notesToPlay.reverse().forEach((note, i) => {
         setTimeout(() => playNote(note.string, note.fret, false), i * 40);
      });
   }
}

function clearNotes() {
   document.querySelectorAll('.note-marker').forEach(m => m.classList.remove('show'));
   document.querySelectorAll('.chord-btn').forEach(b => b.classList.remove('active'));
}

// Song player functions
function playSong() {
   if (isPlaying) {
      stopSong();
      return;
   }

   // Initialize audio context on user interaction
   getAudioContext();

   isPlaying = true;
   noteIndex = 0;
   document.getElementById('playBtn').textContent = '■';
   document.getElementById('playBtn').classList.add('playing');
   playNextNote();
}

function stopSong() {
   isPlaying = false;
   if (songTimeout) {
      clearTimeout(songTimeout);
      songTimeout = null;
   }
   noteIndex = 0;
   document.getElementById('playBtn').textContent = '▶';
   document.getElementById('playBtn').classList.remove('playing');
   document.getElementById('progressBar').style.width = '0%';
   clearNotes();
}

function playNextNote() {
   if (!isPlaying) return;

   const song = SONGS[currentSong];
   if (noteIndex >= song.notes.length) {
      stopSong();
      return;
   }

   const [string, fret, duration] = song.notes[noteIndex];

   // Clear previous and play current
   clearNotes();
   const fretEl = document.querySelector(`[data-string="${string}"][data-fret="${fret}"]`);
   if (fretEl) {
      const marker = fretEl.querySelector('.note-marker');
      marker.classList.add('show', 'playing');
      playNote(string, fret, false);
   }

   // Update progress
   const progress = ((noteIndex + 1) / song.notes.length) * 100;
   document.getElementById('progressBar').style.width = progress + '%';

   noteIndex++;
   songTimeout = setTimeout(playNextNote, song.tempo * duration);
}

function changeSong(songKey) {
   stopSong();
   currentSong = songKey;
   document.getElementById('songTitle').textContent = SONGS[songKey].name;
}

// Event listeners
document.querySelectorAll('.chord-btn').forEach(btn => {
   btn.addEventListener('click', () => {
      stopSong();
      showChord(btn.dataset.chord);
   });
});

document.getElementById('soundToggle').addEventListener('click', function () {
   soundEnabled = !soundEnabled;
   this.classList.toggle('active', soundEnabled);
   // Initialize audio context when enabling sound
   if (soundEnabled) {
      getAudioContext();
   }
});

document.getElementById('clearBtn').addEventListener('click', () => {
   stopSong();
   clearNotes();
});

document.getElementById('playBtn').addEventListener('click', playSong);

document.getElementById('songSelect').addEventListener('change', function () {
   changeSong(this.value);
});

// Initialize
initFretboard();

// Pre-initialize audio context on first user interaction
document.addEventListener('click', function initAudio() {
   getAudioContext();
   document.removeEventListener('click', initAudio);
}, {
   once: true
});

// Mobile menu toggle
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const navLinks = document.querySelector('.nav-links');
const navCta = document.querySelector('.nav-cta');

mobileMenuBtn.addEventListener('click', () => {
   const isOpen = navLinks.classList.toggle('active');
   navCta.classList.toggle('active', isOpen);
   mobileMenuBtn.textContent = isOpen ? '✕' : '☰';

   // Dynamically position CTA below nav-links
   if (isOpen) {
      setTimeout(() => {
         const navLinksHeight = navLinks.offsetHeight;
         navCta.style.top = `calc(100% + ${navLinksHeight}px)`;
      }, 10);
   }
});

// Close mobile menu when clicking a link
navLinks.querySelectorAll('a').forEach(link => {
   link.addEventListener('click', () => {
      navLinks.classList.remove('active');
      navCta.classList.remove('active');
      mobileMenuBtn.textContent = '☰';
   });
});

// Pricing toggle
const PRICING = {
   monthly: {
      price: 30,
      period: '/mo',
      billed: '',
      savings: ''
   },
   quarterly: {
      price: 25.50,
      period: '/mo',
      billed: 'Billed $76.50 every 3 months',
      savings: 'Save 15%'
   },
   yearly: {
      price: 21,
      period: '/mo',
      billed: 'Billed $252 per year',
      savings: 'Save 30%'
   }
};

function updatePricing(billing) {
   const plan = PRICING[billing];

   // Update active button
   document.querySelectorAll('.billing-option').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.billing === billing);
   });

   // Update price display
   const priceEl = document.getElementById('proPrice');
   const billedEl = document.getElementById('proBilled');
   const savingsEl = document.getElementById('proSavings');

   // Animate price change
   priceEl.style.opacity = '0';
   priceEl.style.transform = 'translateY(-10px)';

   setTimeout(() => {
      priceEl.textContent = plan.price % 1 === 0 ? plan.price : plan.price.toFixed(2);
      billedEl.textContent = plan.billed;
      savingsEl.textContent = plan.savings;

      priceEl.style.opacity = '1';
      priceEl.style.transform = 'translateY(0)';
   }, 150);
}

document.getElementById('proPrice').style.transition = 'all 0.15s ease';