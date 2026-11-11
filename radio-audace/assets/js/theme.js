/**
 * Thème Radio Audace — comportements.
 *
 * 1. Lecteur du direct : un seul lecteur, des états explicites, un délai d'attente.
 * 2. Antenne : ce qui passe réellement (émission en ligne, musique, ou rien de connu), rafraîchi chaque minute.
 * 3. Navigation sans rechargement : une couche ajoutée, active seulement pendant l'écoute,
 *    pour que le son ne s'interrompe pas. Au moindre doute, on revient à une navigation normale.
 * 4. Tiroir du menu, recherche, carrousel, vidéo, partage, apparitions.
 *
 * Le site fonctionne sans ce script (liens, recherche, lecture des articles).
 */
(function () {
  'use strict';

  var cfg = window.RadioAudace || {};
  var racine = document.documentElement;
  racine.classList.add('js');

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  var I = {
    jouer: '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2.5.8v12.4L13 7z"/></svg>',
    pause: '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 1h3.6v12H2zM8.4 1H12v12H8.4z"/></svg>',
    charge: '<svg class="tourne" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 1a6 6 0 1 0 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    menu: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
    croix: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>'
  };

  function memo(cle, valeur) {
    try {
      if (valeur !== undefined) sessionStorage.setItem(cle, valeur);
      return sessionStorage.getItem(cle);
    } catch (e) { return null; }
  }

  /* ------------------------------------------------------------ Antenne */

  var antenne = cfg.antenne || { type: 'inconnu', label: 'En direct', titre: 'Radio Audace', detail: '', desc: '' };

  function afficherAntenne(zone) {
    $$('[data-antenne]', zone).forEach(function (bloc) {
      $$('[data-a]', bloc).forEach(function (el) {
        var v = antenne[el.getAttribute('data-a')] || '';
        el.textContent = v;
        if (el.getAttribute('data-a') === 'desc') el.hidden = !v;
      });
    });
    majEtat();
  }

  function rafraichirAntenne() {
    if (!cfg.antenneUrl || document.hidden) return;
    fetch(cfg.antenneUrl, { credentials: 'omit' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (a) {
        if (!a || !a.titre) return;
        antenne = a;
        afficherAntenne(document);
        majSessionMedia();
      })
      .catch(function () { /* on garde le dernier état connu */ });
  }
  setInterval(rafraichirAntenne, 60000);

  /* ------------------------------------------------------------ Lecteur */

  var DELAI = 12000; // Sans son au bout de 12 s, on le dit au lieu de rester sur « Connexion… ».
  var audio = null, minuteur = 0, etatCourant = 'arret';

  function libelles(e) {
    return {
      arret:   { ico: I.jouer,  long: 'Écouter le direct',   court: 'Direct',    etat: '' },
      charge:  { ico: I.charge, long: 'Connexion…',          court: 'Direct',    etat: 'Connexion au direct…' },
      lecture: { ico: I.pause,  long: 'En écoute',           court: 'Pause',     etat: antenne.label || 'En direct' },
      reprise: { ico: I.jouer,  long: 'Reprendre le direct', court: 'Direct',    etat: 'En pause' },
      erreur:  { ico: I.jouer,  long: 'Réessayer',           court: 'Réessayer', etat: 'Direct indisponible — réessayer' }
    }[e];
  }

  function majEtat() {
    var d = libelles(etatCourant);
    racine.setAttribute('data-direct', etatCourant);
    $$('[data-direct-btn]').forEach(function (b) {
      var ico = $('.ico', b);
      if (ico) ico.innerHTML = d.ico;
      $$('.libelle', b).forEach(function (s) { s.textContent = s.classList.contains('court') ? d.court : d.long; });
      b.setAttribute('aria-pressed', etatCourant === 'lecture' ? 'true' : 'false');
    });
    $$('[data-etat]').forEach(function (s) { s.textContent = d.etat; });
  }

  function etat(e) { etatCourant = e; majEtat(); }

  function couper(e) {
    clearTimeout(minuteur); minuteur = 0;
    if (audio) {
      stats('pause');
      audio.pause(); audio.removeAttribute('src'); audio.load(); audio = null;
    }
    etat(e);
  }

  function lancer(auto) {
    if (!cfg.flux) { etat('erreur'); return; }
    audio = new Audio(cfg.flux);
    etat('charge'); memo('ra-direct', '1');
    // Le délai court dès qu'on attend du son ; seul le son qui joue l'arrête.
    var garde = function () {
      if (!minuteur) minuteur = setTimeout(function () { minuteur = 0; if (audio) couper('erreur'); }, DELAI);
    };
    garde();
    audio.addEventListener('playing', function () {
      clearTimeout(minuteur); minuteur = 0;
      if (etatCourant !== 'lecture') stats('play');
      etat('lecture'); majSessionMedia();
    });
    audio.addEventListener('waiting', function () { etat('charge'); garde(); });
    audio.addEventListener('error', function () { if (audio) couper('erreur'); });
    var p = audio.play();
    if (p && p.catch) {
      // Navigateur qui refuse la reprise automatique après un changement de page : on propose de reprendre.
      p.catch(function (err) { if (audio) couper(auto && err && err.name === 'NotAllowedError' ? 'reprise' : 'erreur'); });
    }
  }

  function majSessionMedia() {
    if (!('mediaSession' in navigator) || !window.MediaMetadata) return;
    navigator.mediaSession.metadata = new MediaMetadata({ title: antenne.titre || 'Radio Audace', artist: antenne.detail || 'Radio Audace 106.8 FM', album: 'Radio Audace 106.8 FM' });
    navigator.mediaSession.setActionHandler('pause', function () { memo('ra-direct', '0'); couper('reprise'); });
    navigator.mediaSession.setActionHandler('play', function () { if (!audio) lancer(false); });
  }

  /* Statistiques d'écoute : mêmes événements que le plugin Radio Audace Player (tableau de bord RadioManager). */
  var debutEcoute = 0, battement = 0;
  function stats(type) {
    if (!cfg.stats) return;
    if (type === 'play') {
      debutEcoute = Date.now();
      clearInterval(battement);
      battement = setInterval(function () { stats('heartbeat'); }, 60000);
    }
    var duree = debutEcoute ? Math.round((Date.now() - debutEcoute) / 1000) : 0;
    if (type === 'pause') { clearInterval(battement); battement = 0; if (!debutEcoute) return; }
    var session = memo('ra-session') || memo('ra-session', 's' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8));
    var corps = new URLSearchParams({ action: 'rap_analytics', session_id: session || '', event_type: type, duration: String(duree), page_url: location.href });
    try {
      if (navigator.sendBeacon) navigator.sendBeacon(cfg.stats, corps);
      else fetch(cfg.stats, { method: 'POST', body: corps, keepalive: true }).catch(function () {});
    } catch (e) { /* les statistiques ne doivent jamais gêner l'écoute */ }
    if (type === 'pause') debutEcoute = 0;
  }
  addEventListener('pagehide', function () { if (audio && etatCourant === 'lecture') stats('pause'); });

  /* ------------------------------------------------------------ Ce qui doit être relancé après un changement de page */

  var observateur = 'IntersectionObserver' in window ? new IntersectionObserver(function (entrees) {
    entrees.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add('vu'); observateur.unobserve(e.target); }
    });
  }, { rootMargin: '0px 0px -6% 0px' }) : null;

  var majProgression = null;

  function initialiser(zone) {
    // Apparitions au défilement.
    $$('.apparait', zone).forEach(function (el) { if (observateur) observateur.observe(el); else el.classList.add('vu'); });

    // Carrousel des émissions : flèches à la souris.
    $$('.defile', zone).forEach(function (d) {
      var piste = $('.emissions', d), boutons = $$('.fleches button', d.closest('section') || zone);
      if (!piste || boutons.length < 2) return;
      var pas = function () { return piste.clientWidth * 0.9; };
      boutons[0].addEventListener('click', function () { piste.scrollBy({ left: -pas(), behavior: 'smooth' }); });
      boutons[1].addEventListener('click', function () { piste.scrollBy({ left: pas(), behavior: 'smooth' }); });
      var maj = function () {
        boutons[0].disabled = piste.scrollLeft < 4;
        boutons[1].disabled = piste.scrollLeft + piste.clientWidth > piste.scrollWidth - 4;
      };
      piste.addEventListener('scroll', maj, { passive: true });
      maj();
    });

    // Vidéo : la grande vignette si elle existe, et rien de YouTube avant le clic.
    $$('.video[data-youtube]', zone).forEach(function (v) {
      var img = $('img', v), grande = img && img.getAttribute('data-grande');
      if (grande) {
        var essai = new Image();
        essai.onload = function () { if (essai.naturalWidth > 320) img.src = grande; }; // YouTube renvoie une image grise de 120 px quand elle n'existe pas.
        essai.src = grande;
      }
      v.addEventListener('click', function () {
        if (audio) { memo('ra-direct', '0'); couper('reprise'); } // La vidéo coupe le direct.
        var cadre = document.createElement('div');
        cadre.className = 'video';
        var f = document.createElement('iframe');
        f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(v.getAttribute('data-youtube')) + '?autoplay=1';
        f.title = v.getAttribute('aria-label') || 'Vidéo';
        f.allow = 'autoplay; encrypted-media; picture-in-picture';
        f.allowFullscreen = true;
        cadre.appendChild(f);
        v.replaceWith(cadre);
      });
    });

    // Barre de progression de lecture.
    var barre = $('.progression', zone), corps = $('.corps', zone);
    majProgression = barre && corps ? function () {
      var r = corps.getBoundingClientRect();
      barre.style.transform = 'scaleX(' + Math.min(1, Math.max(0, (innerHeight * 0.5 - r.top) / r.height)) + ')';
    } : null;
    if (majProgression) majProgression();

    afficherAntenne(zone);
  }
  addEventListener('scroll', function () { if (majProgression) majProgression(); }, { passive: true });

  /* ------------------------------------------------------------ En-tête : tiroir et recherche */

  var entete = $('.entete');
  function fermerTiroir() {
    if (!entete) return;
    entete.classList.remove('ouvert');
    racine.classList.remove('tiroir-ouvert');
    var b = $('.burger', entete);
    if (b) { b.innerHTML = I.menu; b.setAttribute('aria-expanded', 'false'); }
  }

  document.addEventListener('click', function (ev) {
    var cible = ev.target instanceof Element ? ev.target : null;
    if (!cible) return;

    if (cible.closest('[data-direct-fermer]')) { memo('ra-direct', '0'); couper('arret'); return; }
    if (cible.closest('[data-direct-btn]')) {
      if (etatCourant === 'lecture' || etatCourant === 'charge') { memo('ra-direct', '0'); couper('reprise'); }
      else lancer(false);
      return;
    }

    var burger = cible.closest('.burger');
    if (burger && entete) {
      var ouvert = entete.classList.toggle('ouvert');
      racine.classList.toggle('tiroir-ouvert', ouvert); // La page ne défile pas derrière le tiroir.
      burger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
      burger.innerHTML = ouvert ? I.croix : I.menu;
      return;
    }

    var loupe = cible.closest('.chercher');
    if (loupe) {
      var barre = $('#recherche');
      barre.hidden = !barre.hidden;
      loupe.setAttribute('aria-expanded', barre.hidden ? 'false' : 'true');
      if (!barre.hidden) $('input', barre).focus();
      return;
    }

    var copier = cible.closest('[data-copier]');
    if (copier) {
      var note = copier.previousElementSibling;
      var fini = function () { if (note) { note.textContent = 'Lien copié'; setTimeout(function () { note.textContent = ''; }, 2000); } };
      if (navigator.clipboard) navigator.clipboard.writeText(location.href).then(fini, fini); else fini();
      return;
    }

    naviguerSiPossible(ev, cible);
  });

  addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    fermerTiroir();
    var barre = $('#recherche');
    if (barre) barre.hidden = true;
  });

  /* ------------------------------------------------------------ Navigation sans rechargement */

  // Active seulement pendant l'écoute, pour un visiteur non connecté. Sinon : navigation normale.
  function douce() {
    return !!audio && !document.getElementById('wpadminbar') && 'DOMParser' in window && !!history.pushState;
  }

  function naviguerSiPossible(ev, cible) {
    var a = cible.closest('a[href]');
    if (!a || !douce()) return;
    if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    var url;
    try { url = new URL(a.href, location.href); } catch (e) { return; }
    if (url.origin !== location.origin) return;
    if (/\/wp-(admin|login|json)|\/feed\/?$|\.(pdf|jpe?g|png|webp|gif|zip|mp3|mp4|docx?|xlsx?)$/i.test(url.pathname)) return;
    if (url.pathname === location.pathname && url.search === location.search) return; // Simple ancre.
    ev.preventDefault();
    charger(url.href, true);
  }

  var chargementEnCours = 0;
  function charger(href, pousser) {
    var jeton = ++chargementEnCours;
    racine.classList.add('charge');
    fermerTiroir();
    fetch(href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'RadioAudace' } })
      .then(function (r) {
        if (!r.ok || !/text\/html/.test(r.headers.get('content-type') || '')) throw new Error('réponse inattendue');
        return r.text().then(function (html) { return { html: html, url: r.url }; });
      })
      .then(function (rep) {
        if (jeton !== chargementEnCours) return;
        var doc = new DOMParser().parseFromString(rep.html, 'text/html');
        var nouveau = doc.querySelector('main#contenu'), ancien = $('main#contenu');
        // Page qui a besoin de ses propres scripts (formulaire, module d'un plugin) : navigation normale.
        if (!nouveau || !ancien || nouveau.querySelector('script, form:not([role="search"]), iframe')) throw new Error('page à charger normalement');
        ancien.innerHTML = nouveau.innerHTML;
        document.title = doc.title;
        document.body.className = doc.body.className;
        var menuN = doc.querySelector('.entete .menu'), menu = $('.entete .menu');
        if (menuN && menu) menu.innerHTML = menuN.innerHTML;
        if (pousser) history.pushState({ ra: 1 }, '', rep.url);
        var ancre = rep.url.split('#')[1], cibleAncre = ancre ? document.getElementById(ancre) : null;
        if (cibleAncre) cibleAncre.scrollIntoView(); else scrollTo(0, 0);
        initialiser(ancien);
        pageVue(rep.html);
        racine.classList.remove('charge');
        var h1 = $('h1', ancien);
        if (h1) { h1.setAttribute('tabindex', '-1'); h1.focus({ preventScroll: true }); } // Lecteurs d'écran : on annonce la nouvelle page.
      })
      .catch(function () {
        if (jeton !== chargementEnCours) return;
        location.href = href; // Au moindre doute, on charge la page normalement (le son sera proposé à la reprise).
      });
  }

  addEventListener('popstate', function () {
    if (douce()) charger(location.href, false); else location.reload();
  });

  // Page vue : on rejoue le signal du plugin de statistiques (audace-analytics) avec la configuration de la nouvelle page.
  function pageVue(html) {
    var m = /window\.AA_CFG=(\{.*?\});/.exec(html);
    if (!m) return;
    try {
      var c = JSON.parse(m[1]);
      if (!c.endpoint) return;
      var charge = JSON.stringify({ type: c.type || 'pageview', post_id: c.postId || 0, path: location.pathname.slice(0, 190), referer: location.hostname, terms: c.terms || '', results: c.results || 0 });
      if (navigator.sendBeacon) navigator.sendBeacon(c.endpoint, new Blob([charge], { type: 'application/json' }));
    } catch (e) { /* sans gravité */ }
  }

  /* ------------------------------------------------------------ Démarrage */

  etat('arret');
  initialiser(document);
  rafraichirAntenne();
  if (memo('ra-direct') === '1') lancer(true); // Le direct était lancé sur la page précédente.
})();
