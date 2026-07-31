// ============================================================
//  assets/js/main.js — Global JS
//  Inventaris Lab
// ============================================================

'use strict';

// ── Navigasi lab card ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-href]').forEach(el => {
    el.addEventListener('click', () => {
      window.location.href = el.dataset.href;
    });
  });
});