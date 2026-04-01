document.addEventListener('DOMContentLoaded', function () {

    const results = document.getElementById('acf-results');
    if (!results) return;

    const atts        = results.dataset.atts ? JSON.parse(results.dataset.atts) : {};
    const currentLang = results.dataset.lang || '';

    // ─── TOPBAR DROPDOWN ──────────────────────────────────────
    document.querySelectorAll('.acf-filter--topbar .acf-filter-group__legend').forEach(legend => {
        legend.addEventListener('click', function (e) {
            e.stopPropagation();
            const group = this.closest('.acf-filter-group');
            document.querySelectorAll('.acf-filter--topbar .acf-filter-group').forEach(g => {
                if (g !== group) g.classList.remove('is-open');
            });
            group.classList.toggle('is-open');
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.acf-filter--topbar .acf-filter-group')) {
            document.querySelectorAll('.acf-filter--topbar .acf-filter-group')
                .forEach(g => g.classList.remove('is-open'));
        }
    });

    document.querySelectorAll('.acf-filter--topbar .acf-filter-group__options').forEach(panel => {
        panel.addEventListener('click', e => e.stopPropagation());
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
    applyBtn?.addEventListener('click', () => { closeOffcanvas(); update(1); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOffcanvas(); });

    // ─── FILTER COUNT ─────────────────────────────────────────
    function updateFilterCount() {
        const form = getActiveForm();
        if (!form) return;
        const countEl = document.querySelector('.acf-filter-count');
        if (!countEl) return;
        const checked  = form.querySelectorAll('input:checked').length;
        const total    = checked;
        countEl.textContent = total;
        countEl.classList.toggle('has-filters', total > 0);
    }

    // ─── AKTIV FORM ───────────────────────────────────────────
    function getActiveForm() {
        const forms = document.querySelectorAll('[data-filter-form]');
        for (const f of forms) {
            if (f.offsetParent !== null) return f;
        }
        return forms[0] || null;
    }

    // ─── AJAX ─────────────────────────────────────────────────
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
            bindPagination();
        })
        .catch(err => {
            console.error('Filter feil:', err);
            results.classList.remove('is-loading');
        });
    }

    // ─── PAGINERING ───────────────────────────────────────────
    function bindPagination() {
        results.querySelectorAll('.acf-pagination .page-numbers[data-page]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (!isNaN(page)) {
                    update(page);
                    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    // Lytt på alle filter-forms
    document.querySelectorAll('[data-filter-form]').forEach(f => {
        f.addEventListener('change', () => {
            updateFilterCount();
            if (!f.closest('.acf-filter--offcanvas')) {
                update(1); // Alltid tilbake til side 1 ved ny filtrering
            }
        });

        f.addEventListener('reset', () => {
            setTimeout(() => { updateFilterCount(); update(1); }, 10);
        });
    });

    // Bind paginering på initial load (ikke-AJAX)
    bindPagination();
    updateFilterCount();
});