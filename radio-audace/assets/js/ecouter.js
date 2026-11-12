/**
 * Thème Radio Audace — page « Écouter » (/ecouter) : le poste de radio.
 *
 * Le lecteur lui-même (lecture, états, antenne, statistiques) est celui de theme.js.
 * Ce script n'ajoute que ce qui est propre au poste :
 *  1. les textes sous le poste et sous le bouton, selon l'état du lecteur ;
 *  2. le bouton d'accord, un jeu facultatif : l'aiguille suit le doigt, la radio baisse et
 *     grésille hors de la station, puis l'aiguille revient seule quand on lâche ;
 *  3. la copie du lien.
 *
 * Sur iPhone, une page ne peut pas baisser le son (audio.volume est ignoré) : l'aiguille
 * bouge alors sans aucun effet sonore.
 */
(function () {
  'use strict';

  var poste = document.querySelector('.pv-poste');
  if (!poste) return;

  var cfg = window.RadioAudace || {};
  var racine = document.documentElement;
  var $ = function (s) { return document.querySelector(s); };
  var son = function () { return cfg.audio ? cfg.audio() : null; }; // L'élément audio de theme.js, ou null.
  var etat = function () { return racine.getAttribute('data-direct') || 'arret'; };

  /* ------------------------------------------------------------ Textes selon l'état */

  var OU = {
    arret: 'Appuyez sur le bouton pour écouter',
    charge: 'Le poste chauffe, connexion…',
    lecture: 'Vous écoutez le direct',
    reprise: 'En pause, appuyez pour reprendre',
    erreur: 'Direct indisponible, réessayez'
  };
  var TOUCHE = { arret: 'Écouter', charge: 'Connexion', lecture: 'Pause', reprise: 'Reprendre', erreur: 'Réessayer' };
  var ou = $('[data-ou]'), touche = $('[data-touche]');

  function suivre() {
    var e = etat();
    if (ou) ou.textContent = OU[e] || '';
    if (touche) touche.textContent = TOUCHE[e] || '';
    sonne();
    if (e !== 'lecture' && souffle) souffle.gain.value = 0;
  }

  /* ------------------------------------------------------------ Accord */

  var STATION = parseFloat(poste.getAttribute('data-station')) || 106.8, BAS = 102, HAUT = 108;
  var echelle = $('.pv-echelle'), bouton = $('[data-accord]');
  var frequence = STATION, ecart = 0, ctx = null, souffle = null, sommeil = 0, prec = null;

  var essai = document.createElement('audio');
  var VOLUME_REGLABLE = false;
  try { essai.volume = 0.5; VOLUME_REGLABLE = essai.volume === 0.5; } catch (e) { /* volume non réglable */ }

  function sonne() {
    var a = son();
    if (a && VOLUME_REGLABLE) a.volume = 1 - 0.85 * ecart;
  }

  // Le générateur de bruit est créé à l'appui sur le bouton d'écoute (un vrai geste), réveillé quand on saisit
  // l'accord, rendormi quand on le lâche : il ne tourne pas pour ceux qui n'y touchent pas.
  function preparerSouffle() {
    if (!VOLUME_REGLABLE || ctx) return;
    var AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return;
    try {
      ctx = new AC();
      var tampon = ctx.createBuffer(1, ctx.sampleRate * 2, ctx.sampleRate), d = tampon.getChannelData(0);
      for (var n = 0; n < d.length; n++) d[n] = Math.random() * 2 - 1;
      var source = ctx.createBufferSource();
      source.buffer = tampon; source.loop = true;
      souffle = ctx.createGain(); souffle.gain.value = 0;
      source.connect(souffle); souffle.connect(ctx.destination);
      source.start();
      ctx.suspend();
    } catch (e) { ctx = null; souffle = null; /* le jeu reste muet, l'écoute n'est pas touchée */ }
  }

  function accorder(f) {
    frequence = Math.min(HAUT, Math.max(BAS, f));
    ecart = Math.min(1, Math.abs(frequence - STATION) / 0.5);
    if (echelle) echelle.style.setProperty('--f', frequence);
    bouton.style.setProperty('--angle', ((frequence - STATION) * 50) + 'deg');
    if (souffle) souffle.gain.setTargetAtTime(etat() === 'lecture' ? ecart * 0.06 : 0, ctx.currentTime, 0.05);
    sonne();
  }

  function angle(ev) {
    var r = bouton.getBoundingClientRect();
    return Math.atan2(ev.clientY - r.top - r.height / 2, ev.clientX - r.left - r.width / 2) * 180 / Math.PI;
  }

  function lacher() {
    if (prec === null) return;
    prec = null;
    bouton.classList.remove('tient'); poste.classList.remove('accorde');
    accorder(STATION);
    if (ctx) sommeil = setTimeout(function () { if (ctx) ctx.suspend(); }, 400);
  }

  if (bouton && 'PointerEvent' in window) {
    bouton.addEventListener('pointerdown', function (ev) {
      try { bouton.setPointerCapture(ev.pointerId); } catch (e) { /* sans gravité */ }
      prec = angle(ev);
      bouton.classList.add('tient'); poste.classList.add('accorde');
      clearTimeout(sommeil);
      if (ctx) ctx.resume();
      ev.preventDefault();
    });
    bouton.addEventListener('pointermove', function (ev) {
      if (prec === null) return;
      var a = angle(ev), d = a - prec;
      if (d > 180) d -= 360;
      if (d < -180) d += 360;
      prec = a;
      accorder(frequence + d / 50);
    });
    bouton.addEventListener('pointerup', lacher);
    bouton.addEventListener('pointercancel', lacher);
  }

  var marche = $('.pv-marche');
  if (marche) marche.addEventListener('click', preparerSouffle);

  /* ------------------------------------------------------------ Copie du lien */

  var copie = $('[data-ecoute-copie]');
  if (copie) {
    copie.addEventListener('click', function () {
      var texte = copie.querySelector('span'), repos = texte ? texte.textContent : '';
      var fini = function (ok) {
        if (!texte) return;
        texte.textContent = ok ? 'Lien copié' : 'Copie impossible';
        setTimeout(function () { texte.textContent = repos; }, 2000);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(copie.getAttribute('data-ecoute-copie')).then(function () { fini(true); }, function () { fini(false); });
      } else fini(false);
    });
  }

  /* ------------------------------------------------------------ Démarrage */

  if ('MutationObserver' in window) new MutationObserver(suivre).observe(racine, { attributes: true, attributeFilter: ['data-direct'] });
  suivre();
})();
