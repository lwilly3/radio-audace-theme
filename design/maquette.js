// Maquette Radio Audace : en-tête, pied et lecteur communs à toutes les pages,
// plus les comportements que le thème reprendra (direct, tiroir, typographie…).
document.documentElement.classList.add('js');
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* ---------- Données simulées (dans le thème : grille fournie par audace-analytics) ---------- */
const FLUX = 'https://stream.radio.audace.ovh/stream.mp3'; // flux du plugin Radio Audace Player
// Ce qui passe RÉELLEMENT à l'antenne n'est pas toujours ce que prévoit la grille. Quatre cas, à essayer avec ?antenne=… :
//   emission    — un conducteur est en ligne dans RadioManager (current_show)
//   musique     — pas d'émission, RadioDJ diffuse un titre (current_track). Exemple réel relevé le 2026-11-11.
//   rediffusion — une émission enregistrée repasse (information que RadioManager ne fournit pas encore)
//   inconnu     — aucune information (API injoignable) : on n'affirme rien
const CAS = {
  emission:    { label: 'En direct · 106.8 FM',    titre: 'Audace Info Midi Magazine', detail: "Jusqu'à 13 h 30", desc: 'Politique, économie, culture, santé et sports, avec la parole aux experts.' },
  musique:     { label: 'En ce moment · 106.8 FM', titre: 'DESILLUSION', detail: 'NGUEA LA ROUTE · Musique', desc: 'Prochaine émission : Le Club Audace, à 15 h.' },
  rediffusion: { label: 'Rediffusion · 106.8 FM',  titre: 'Parlons Droit !', detail: 'Émission du jeudi 5 novembre', desc: 'Prochaine émission en direct : Le Club Audace, à 15 h.' },
  inconnu:     { label: 'En direct · 106.8 FM',    titre: 'Radio Audace', detail: "Bien plus que l'info", desc: '' },
};
const casChoisi = new URLSearchParams(location.search).get('antenne');
try { if (casChoisi in CAS) sessionStorage.setItem('antenne', casChoisi); } catch (e) { /* aperçu local */ }
let casMemo = null; try { casMemo = sessionStorage.getItem('antenne'); } catch (e) { /* aperçu local */ }
const ANTENNE = CAS[casChoisi] || CAS[casMemo] || CAS.emission;

const I = {
  jouer: '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2.5.8v12.4L13 7z"/></svg>',
  pause: '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 1h3.6v12H2zM8.4 1H12v12H8.4z"/></svg>',
  charge: '<svg class="tourne" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 1a6 6 0 1 0 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  loupe: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.8-3.8"/></svg>',
  menu: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
  croix: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>',
  whatsapp: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 1.8a8.2 8.2 0 1 1-4.2 15.3l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 0 1 12 3.800zm-3.200 4c-.2 0-.5 0-.7.300-.3.3-1 1-1 2.300s1 2.700 1.100 2.900c.2.2 2 3.100 4.900 4.300 2.400 1 2.900.8 3.400.7.500 0 1.700-.7 1.900-1.300.2-.700.2-1.200.2-1.300-.100-.2-.3-.2-.6-.4l-1.900-.9c-.3-.100-.5-.2-.7.100l-.9 1.100c-.2.2-.3.2-.6.100-.3-.2-1.200-.5-2.300-1.400-.8-.8-1.400-1.700-1.600-2-.2-.3 0-.5.100-.6l.5-.5.3-.5c.100-.2 0-.4 0-.5l-.9-2c-.2-.5-.4-.4-.600-.4z"/></svg>',
  facebook: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.500 21v-7.500H16l.4-3h-2.900V8.600c0-.9.3-1.500 1.500-1.500h1.500V4.400c-.3 0-1.200-.100-2.200-.100-2.200 0-3.700 1.300-3.700 3.800v2.400H8v3h2.600V21z"/></svg>',
  x: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.200 4h2.800l-6.100 7 7.100 9h-5.600l-4.400-5.700L6 20H3.200l6.500-7.500L2.900 4h5.700l4 5.200zm-1 14.300h1.500L7.800 5.600H6.200z"/></svg>',
  lien: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.600 13.400a1 1 0 0 1 0-1.400l3-3a1 1 0 1 1 1.400 1.400l-3 3a1 1 0 0 1-1.400 0zM7 17a4 4 0 0 1 0-5.700l2-2 1.400 1.400-2 2A2 2 0 0 0 11.300 15.600l2-2 1.400 1.400-2 2A4 4 0 0 1 7 17zm6.600-6.600-1.400-1.400 2-2A4 4 0 0 1 19.900 12.700l-2 2-1.400-1.400 2-2A2 2 0 0 0 15.600 8.400z"/></svg>',
  chevG: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3 5 8l5 5"/></svg>',
  chevD: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg>',
};

/* ---------- En-tête ---------- */
const entete = $('.entete');
if (entete) {
  const courant = entete.dataset.courant || '';
  const l = (href, cle, txt, cls = '') => `<a href="${href}"${cls ? ` class="${cls}"` : ''}${cle === courant ? ' aria-current="page"' : ''}>${txt}</a>`;
  entete.innerHTML = `
  <div class="cadre">
    <button class="icone-btn burger" type="button" aria-expanded="false" aria-controls="menu" aria-label="Menu">${I.menu}</button>
    <a class="marque" href="accueil.html" aria-label="Radio Audace 106.8 FM, accueil"><img src="assets/logo-radio-audace-simplifie.svg" alt="" width="138" height="34"></a>
    <nav class="menu" id="menu" aria-label="Navigation principale">
      <form class="champ-recherche" role="search" action="#"><input type="search" name="s" placeholder="Rechercher…" aria-label="Rechercher"><button type="submit">OK</button></form>
      ${l('emission.html', 'emissions', 'Émissions', 'radio')}
      ${l('grille.html', 'grille', 'Grille', 'radio')}
      <span class="sep" aria-hidden="true"></span>
      ${l('#', 'infos', 'Infos')}${l('#', 'politique', 'Politique')}${l('#', 'economie', 'Économie')}${l('#', 'sport', 'Sport')}
      ${l('rubrique.html', 'societe', 'Société')}${l('#', 'culture', 'Culture')}${l('#', 'education', 'Éducation')}
    </nav>
    <div class="outils">
      <button class="icone-btn chercher" type="button" aria-expanded="false" aria-controls="recherche" aria-label="Rechercher">${I.loupe}</button>
      <button class="direct-btn" type="button" data-direct-btn><span class="ico"></span><span class="libelle long"></span><span class="libelle court"></span></button>
    </div>
  </div>
  <div class="barre-recherche" id="recherche" hidden><div class="cadre"><form class="champ-recherche" role="search" action="#"><input type="search" name="s" placeholder="Rechercher un sujet, une émission…" aria-label="Rechercher"><button type="submit">Rechercher</button></form></div></div>`;

  const burger = $('.burger', entete);
  burger.addEventListener('click', () => {
    const ouvert = entete.classList.toggle('ouvert');
    document.documentElement.classList.toggle('tiroir-ouvert', ouvert); // la page ne défile pas derrière le tiroir
    burger.setAttribute('aria-expanded', ouvert);
    burger.innerHTML = ouvert ? I.croix : I.menu;
  });
  const chercher = $('.chercher', entete), barre = $('#recherche');
  chercher.addEventListener('click', () => {
    barre.hidden = !barre.hidden;
    chercher.setAttribute('aria-expanded', !barre.hidden);
    if (!barre.hidden) $('input', barre).focus();
  });
  addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    barre.hidden = true; entete.classList.remove('ouvert'); document.documentElement.classList.remove('tiroir-ouvert'); burger.innerHTML = I.menu; burger.setAttribute('aria-expanded', false);
  });
}

/* ---------- Pied ---------- */
const pied = $('.pied');
if (pied) {
  pied.innerHTML = `
  <div class="cadre">
    <div class="colonnes">
      <div>
        <a class="marque" href="accueil.html" aria-label="Radio Audace 106.8 FM, accueil"><img src="assets/logo-radio-audace-simplifie.svg" alt="" width="138" height="34"></a>
        <address>Rue Tokoto, Bonapriso<br><span class="l2">BP 1797 Douala, Cameroun</span>
          <span class="coord"><a href="tel:+237223420949">(+237) 223 42 09 49</a><a href="tel:+237658311455">(+237) 6 58 31 14 55</a><a href="mailto:contact@audacemediagroup.com">contact@audacemediagroup.com</a></span>
        </address>
        <div class="reseaux" aria-label="Réseaux sociaux"><a href="#">WhatsApp</a><a href="#">Facebook</a><a href="https://www.youtube.com/@radioaudace">YouTube</a></div>
      </div>
      <nav aria-labelledby="p-emi"><h2 id="p-emi">Émissions</h2><ul><li><a href="emission.html">Le Train d'Audace</a></li><li><a href="emission.html">Audace Info Midi Magazine</a></li><li><a href="emission.html">Le Club Audace</a></li><li><a href="emission.html">Parlons Droit !</a></li><li><a href="emission.html">Toutes les émissions</a></li><li><a href="grille.html">Grille des programmes</a></li></ul></nav>
      <nav aria-labelledby="p-rub"><h2 id="p-rub">Rubriques</h2><ul><li><a href="#">Infos</a></li><li><a href="#">Politique</a></li><li><a href="#">Économie</a></li><li><a href="#">Sport</a></li><li><a href="rubrique.html">Société</a></li><li><a href="#">Culture</a></li><li><a href="#">Éducation</a></li></ul></nav>
      <nav aria-labelledby="p-ra"><h2 id="p-ra">Radio Audace</h2><ul><li><a href="#">À propos</a></li><li><a href="#">Contacts</a></li><li><a href="#">Invités</a></li><li><a href="#">Newsletter</a></li></ul></nav>
    </div>
    <div class="bas"><span>© 2026 Audace Media Group · Douala</span><nav aria-label="Informations légales"><a href="#">Mentions légales</a><a href="#">Confidentialité</a></nav></div>
  </div>`;
}

/* ---------- Blocs « À l'antenne » : remplis selon ce qui passe réellement ---------- */
$$('.antenne, .bandeau-direct').forEach((b) => {
  $('.etiquette', b).innerHTML = `<span class="pastille"></span>${ANTENNE.label}`;
  $('h2, b', b).textContent = ANTENNE.titre;
  $('.quoi, .h', b).textContent = b.matches('.bandeau-direct') ? `${ANTENNE.detail} · le son continue pendant votre lecture` : ANTENNE.detail;
  const d = $('.desc', b); if (d) { d.textContent = ANTENNE.desc; d.hidden = !ANTENNE.desc; }
});
// Grille : le créneau prévu n'est marqué « En ce moment » que si l'émission est réellement en ligne.
if (ANTENNE !== CAS.emission) $$('.creneau.en-cours .etiquette').forEach((e) => { e.textContent = 'Prévu à cette heure'; });

/* ---------- Lecteur du direct : un seul lecteur, quatre états, persistant en bas d'écran ---------- */
document.body.insertAdjacentHTML('beforeend', `
<aside class="lecteur" aria-label="Lecteur du direct">
  <div class="cadre">
    <button class="jouer" type="button" data-direct-btn><span class="ico"></span><span class="sr libelle"></span></button>
    <div class="txt"><div class="etat"><span class="pastille"></span><span data-etat></span></div><div class="quoi">${ANTENNE.titre} <span>· ${ANTENNE.detail}</span></div></div>
    <div class="onde" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
    <button class="fermer" type="button" aria-label="Fermer le lecteur" data-direct-fermer>${I.croix}</button>
  </div>
</aside>`);

const ETATS = {
  arret:   { ico: I.jouer,  long: 'Écouter le direct', court: 'Direct', etat: '' },
  charge:  { ico: I.charge, long: 'Connexion…',        court: 'Direct', etat: 'Connexion au direct…' },
  lecture: { ico: I.pause,  long: 'En écoute',         court: 'Pause',  etat: ANTENNE.label },
  reprise: { ico: I.jouer,  long: 'Reprendre le direct', court: 'Direct', etat: 'En pause' },
  erreur:  { ico: I.jouer,  long: 'Réessayer',         court: 'Réessayer', etat: 'Direct indisponible — réessayer' },
};
// Dans le thème, ces boutons piloteront le lecteur du plugin Radio Audace Player (un seul lecteur sur le site).
const DELAI = 12000; // sans son au bout de 12 s, on le dit au lieu de rester sur « Connexion… »
let audio = null, minuteur = 0;
const memo = (v) => { try { v === undefined ? 0 : sessionStorage.setItem('direct', v); return sessionStorage.getItem('direct'); } catch (e) { return null; } };
function etat(e) {
  document.documentElement.dataset.direct = e;
  const d = ETATS[e];
  $$('[data-direct-btn]').forEach((b) => {
    $('.ico', b).innerHTML = d.ico;
    $$('.libelle', b).forEach((s) => { s.textContent = s.classList.contains('court') ? d.court : d.long; });
    b.setAttribute('aria-pressed', e === 'lecture');
  });
  $$('[data-etat]').forEach((s) => { s.textContent = d.etat; });
}
function couper(e) { clearTimeout(minuteur); minuteur = 0; if (audio) { audio.pause(); audio.removeAttribute('src'); audio.load(); audio = null; } etat(e); }
function lancer(auto) {
  audio = new Audio(FLUX);
  etat('charge'); memo('1');
  // Le délai court dès qu'on attend du son ; seul le son qui joue l'arrête (un événement réseau ne le relance pas).
  const garde = () => { if (!minuteur) minuteur = setTimeout(() => { minuteur = 0; if (audio) couper('erreur'); }, DELAI); };
  garde();
  audio.addEventListener('playing', () => { clearTimeout(minuteur); minuteur = 0; etat('lecture'); });
  audio.addEventListener('waiting', () => { etat('charge'); garde(); });
  audio.addEventListener('error', () => { if (audio) couper('erreur'); });
  // Navigateur qui refuse la reprise automatique après un changement de page : on propose de reprendre.
  audio.play().catch((err) => { if (audio) couper(auto && err.name === 'NotAllowedError' ? 'reprise' : 'erreur'); });
}
addEventListener('click', (ev) => {
  if (ev.target.closest('[data-direct-fermer]')) { memo('0'); couper('arret'); return; }
  if (!ev.target.closest('[data-direct-btn]')) return;
  const e = document.documentElement.dataset.direct;
  if (e === 'lecture' || e === 'charge') { memo('0'); couper('reprise'); } else lancer(false);
});
etat('arret');
if (memo() === '1') lancer(true); // le direct était lancé sur la page précédente

/* ---------- Typographie française : espaces insécables (dans le thème : filtre PHP) ---------- */
(function insecables() {
  const zone = $('main'); if (!zone) return;
  const marche = document.createTreeWalker(zone, NodeFilter.SHOW_TEXT, { acceptNode: (n) => (n.parentElement.closest('script,style,code,pre,input') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT) });
  const noeuds = []; while (marche.nextNode()) noeuds.push(marche.currentNode);
  for (const n of noeuds) {
    const t = n.nodeValue
      .replace(/ ([!?;])/g, ' $1').replace(/ ([:»›·–—%])/g, ' $1').replace(/« /g, '« ')
      .replace(/(\d) (h|min|FCFA|milliards|millions|ans|morts|jours)\b/g, '$1 $2').replace(/(\bh) (\d)/g, '$1 $2');
    if (t !== n.nodeValue) n.nodeValue = t;
  }
})();

/* ---------- Corps d'article : ce que le thème fera côté serveur ---------- */
$$('.corps blockquote').forEach((q) => { if (q.children.length <= 1 && q.textContent.trim().length <= 150) q.classList.add('exergue'); });
$$('.corps table').forEach((t) => { const d = document.createElement('div'); d.className = 'tableau'; t.replaceWith(d); d.append(t); });

/* ---------- Partage : WhatsApp d'abord ---------- */
$$('[data-partage]').forEach((z) => {
  const u = encodeURIComponent(location.href), t = encodeURIComponent(document.title);
  z.insertAdjacentHTML('beforeend', `
    <a href="https://wa.me/?text=${t}%20${u}" aria-label="Partager sur WhatsApp">${I.whatsapp}</a>
    <a href="https://www.facebook.com/sharer/sharer.php?u=${u}" aria-label="Partager sur Facebook">${I.facebook}</a>
    <a href="https://x.com/intent/post?url=${u}&text=${t}" aria-label="Partager sur X">${I.x}</a>
    <span class="copie" role="status"></span><button type="button" aria-label="Copier le lien" data-copier>${I.lien}</button>`);
});
addEventListener('click', async (ev) => {
  const b = ev.target.closest('[data-copier]'); if (!b) return;
  try { await navigator.clipboard.writeText(location.href); } catch (e) { /* aperçu local */ }
  const s = b.previousElementSibling; s.textContent = 'Lien copié';
  setTimeout(() => { s.textContent = ''; }, 2000);
});

/* ---------- Carrousel des émissions : flèches à la souris ---------- */
$$('.defile').forEach((d) => {
  const piste = $('.emissions', d), [g, dr] = $$('.fleches button', d.closest('section'));
  if (!g) return;
  g.innerHTML = I.chevG; dr.innerHTML = I.chevD;
  const pas = () => piste.clientWidth * 0.9;
  g.addEventListener('click', () => piste.scrollBy({ left: -pas(), behavior: 'smooth' }));
  dr.addEventListener('click', () => piste.scrollBy({ left: pas(), behavior: 'smooth' }));
  const maj = () => { g.disabled = piste.scrollLeft < 4; dr.disabled = piste.scrollLeft + piste.clientWidth > piste.scrollWidth - 4; };
  piste.addEventListener('scroll', maj, { passive: true }); addEventListener('resize', maj); maj();
});

/* ---------- Vidéo : rien n'est chargé de YouTube avant le clic ---------- */
$$('.video[data-youtube]').forEach((v) => v.addEventListener('click', () => {
  if (audio) { memo('0'); couper('reprise'); } // la vidéo coupe le direct
  v.outerHTML = `<div class="video"><iframe src="https://www.youtube-nocookie.com/embed/${v.dataset.youtube}?autoplay=1" title="${v.getAttribute('aria-label') || 'Vidéo'}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>`;
}));

/* ---------- Apparitions et barre de progression ---------- */
const observateur = new IntersectionObserver((entrees) => {
  for (const e of entrees) if (e.isIntersecting) { e.target.classList.add('vu'); observateur.unobserve(e.target); }
}, { rootMargin: '0px 0px -6% 0px' });
$$('.apparait').forEach((el) => observateur.observe(el));

const barreP = $('.progression'), corps = $('.corps');
if (barreP && corps) {
  const maj = () => {
    const r = corps.getBoundingClientRect();
    barreP.style.transform = `scaleX(${Math.min(1, Math.max(0, (innerHeight * 0.5 - r.top) / r.height))})`;
  };
  addEventListener('scroll', maj, { passive: true }); maj();
}
