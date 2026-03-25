document.addEventListener('DOMContentLoaded', function () {

    function getActiveForm() {
        const forms = document.querySelectorAll('[data-filter-form]');
        for (const f of forms) {
            // Returner synlig form
            if (f.offsetParent !== null) return f;
        }
        return forms[0] || null;
    }

    const form    = getActiveForm();
    
    const results = document.getElementById('acf-results');

    // ─── TOPBAR DROPDOWN ──────────────────────────────────────
    document.querySelectorAll('.acf-filter--topbar .acf-filter-group__legend').forEach(legend => {
        legend.addEventListener('click', function (e) {
            e.stopPropagation();
            const group = this.closest('.acf-filter-group');

            // Lukk andre grupper
            document.querySelectorAll('.acf-filter--topbar .acf-filter-group').forEach(g => {
                if (g !== group) g.classList.remove('is-open');
            });

            group.classList.toggle('is-open');
        });
    });

    // Lukk topbar dropdown ved klikk utenfor — men ikke ved klikk inne i panel
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.acf-filter--topbar .acf-filter-group')) {
            document.querySelectorAll('.acf-filter--topbar .acf-filter-group')
                .forEach(g => g.classList.remove('is-open'));
        }
    });

    // Hindre at klikk inne i dropdown lukker den
    document.querySelectorAll('.acf-filter--topbar .acf-filter-group__options').forEach(panel => {
        panel.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // ─── OFFCANVAS ────────────────────────────────────────────
    const trigger  = document.querySelector('.acf-filter-trigger');
    const panel    = document.getElementById('acf-offcanvas-panel');
    const overlay  = document.querySelector('.acf-filter-overlay');
    const closeBtn = document.querySelector('.acf-offcanvas-close');
    const applyBtn = document.querySelector('.acf-offcanvas-apply');

    function openOffcanvas() {
        panel?.classList.add('is-open');
        overlay?.classList.add('is-active');
        trigger?.setAttribute('aria-expanded', 'true');
        panel?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeOffcanvas() {
        panel?.classList.remove('is-open');
        overlay?.classList.remove('is-active');
        trigger?.setAttribute('aria-expanded', 'false');
        panel?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    trigger?.addEventListener('click', openOffcanvas);
    closeBtn?.addEventListener('click', closeOffcanvas);
    overlay?.addEventListener('click', closeOffcanvas);
    applyBtn?.addEventListener('click', () => { closeOffcanvas(); if (form) update(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOffcanvas(); });

    // ─── FILTER COUNT ─────────────────────────────────────────
    function updateFilterCount() {
        if (!form) return;
        const countEl = document.querySelector('.acf-filter-count');
        if (!countEl) return;
        const checked  = form.querySelectorAll('input:checked').length;
        const selected = form.querySelectorAll('select option:checked:not([value=""])').length;
        const total    = checked + selected;
        countEl.textContent = total;
        countEl.classList.toggle('has-filters', total > 0);
    }

    // ─── AJAX FILTER ──────────────────────────────────────────
    if (!form || !results) return;

    const atts        = results.dataset.atts ? JSON.parse(results.dataset.atts) : {};
    const currentLang = results.dataset.lang || '';

    function update(paged = 1) {

        const form = getActiveForm();
        if (!form) return;

        const data = new FormData(form);
        data.append('action', 'acf_filter');
        data.append('nonce', acfFilter.nonce);
        data.append('paged', paged);
        data.append('current_lang', currentLang);

        Object.entries(atts).forEach(([key, value]) => {
            data.append('atts[' + key + ']', value);
        });

        results.classList.add('is-loading');

        fetch(acfFilter.ajaxUrl, {
            method: 'POST',
            body: data,
        })
        .then(res => res.text())
        .then(html => {
            results.innerHTML = html;
            results.classList.remove('is-loading');
            updateFilterCount();

            results.querySelectorAll('.acf-pagination .page-numbers').forEach(link => {
                link.addEventListener('click', function (e) {
                    if (!this.href) return;
                    e.preventDefault();
                    const url     = new URL(this.href);
                    const pageNum = url.searchParams.get('paged') || 1;
                    update(pageNum);
                    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        })
        .catch(err => {
            console.error('Filter feil:', err);
            results.classList.remove('is-loading');
        });
    }

    // Lytt på alle filter-forms
    document.querySelectorAll('[data-filter-form]').forEach(f => {
        f.addEventListener('change', () => {
            updateFilterCount();
            if (!f.closest('.acf-filter--offcanvas')) {
                update();
            }
        });

        f.addEventListener('reset', () => {
            setTimeout(() => { updateFilterCount(); update(); }, 10);
        });
    });


    // form.addEventListener('change', () => {
    //     updateFilterCount();
    //     if (!form.closest('.acf-filter--offcanvas')) {
    //         update();
    //     }
    // });

    // form.addEventListener('reset', () => {
    //     setTimeout(() => { updateFilterCount(); update(); }, 10);
    // });

    updateFilterCount();
});