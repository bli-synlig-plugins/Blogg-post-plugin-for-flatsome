jQuery(document).ready(function ($) {

    const container   = $('#acf-builder');
    const textarea    = $('#acf-json');
    const taxonomies  = acfFilterAdmin.taxonomies || [];
    const hasPolylang = acfFilterAdmin.hasPolylang || false;
    const languages   = acfFilterAdmin.languages || [];

    let data = [];

    try {
        data = JSON.parse(textarea.val() || '[]');
    } catch (e) {
        data = [];
    }

    function save() {
        textarea.val(JSON.stringify(data, null, 2));
    }

    function taxOptions(selected) {
        return taxonomies.map(t =>
            `<option value="${t.value}" ${selected === t.value ? 'selected' : ''}>${t.label}</option>`
        ).join('');
    }

    function render() {
        container.empty();

        data.forEach((filter, index) => {

            const typeOptions = `<option value="taxonomy" ${filter.type === 'taxonomy' ? 'selected' : ''}>Taksonomi</option>`;

            const taxSelector = filter.type === 'taxonomy'
                ? `<label>Taksonomi
                       <select class="filter-taxonomy">${taxOptions(filter.taxonomy)}</select>
                   </label>`
                : '';

            // Bygg visningsnavn-felt per språk
            const labels = filter.labels || {};
            const labelFields = languages.map(lang => `
                <label>
                    <input type="text"
                        class="filter-lang-label"
                        data-lang="${lang.slug}"
                        value="${$('<div>').text(labels[lang.slug] || '').html()}">

                    Visningsnavn (${lang.name})
                </label>
            `).join('');

            const el = $(`
                <div class="acf-filter-item" data-index="${index}">
                    <div class="acf-filter-header">
                        Filter ${index + 1}
                        <button type="button" class="acf-filter-button remove-filter"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2-icon lucide-trash-2"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
                    </div>

                    <div class="acf-filter-label-fields">
                        <legend><strong>Visningsnavn</strong></legend>
                        <div class="acf-filter-label-fields__fields">${labelFields}</div>
                    </div>

                    <div class="acf-filter-settings">
                        <label class="acf-filter-label">Type
                            <select class="filter-type">${typeOptions}</select>
                        </label>

                        <div class="acf-filter-label tax-selector-wrap">${taxSelector}</div>


                        <label class="acf-filter-label">Valg-type
                            <select class="filter-input-type">
                                <option value="checkbox" ${(filter.input_type || 'checkbox') === 'checkbox' ? 'selected' : ''}>Flervalg (checkbox)</option>
                                <option value="radio"    ${filter.input_type === 'radio'    ? 'selected' : ''}>Enkeltvalg (radio)</option>
                            </select>
                        </label>

                        <label class="acf-filter-label">Logikk
                            <select class="filter-logic">
                                <option value="OR"  ${filter.logic === 'OR'  ? 'selected' : ''}>OR</option>
                                <option value="AND" ${filter.logic === 'AND' ? 'selected' : ''}>AND</option>
                            </select>
                        </label>
                    </div>
                </div>
            `);

            container.append(el);
        });

        save();
    }

    // Legg til nytt filter
    $(document).on('click', '#add-filter', function () {
        data.push({
            labels:   {},
            type:     hasPolylang ? 'language' : 'taxonomy',
            taxonomy: taxonomies[0]?.value || 'category',
            logic:    'OR',
        });
        render();
    });

    // Slett filter
    $(document).on('click', '.remove-filter', function () {
        const index = $(this).closest('.acf-filter-item').data('index');
        data.splice(index, 1);
        render();
    });

    // Bytt type
    $(document).on('change', '.filter-type', function () {
        const index = $(this).closest('.acf-filter-item').data('index');
        data[index].type = $(this).val();
        render();
    });

    $(document).on('change', '.filter-taxonomy', function () {
        const index = $(this).closest('.acf-filter-item').data('index');
        data[index].taxonomy = $(this).val();
        save();
    });

    $(document).on('change', '.filter-logic', function () {
        const index = $(this).closest('.acf-filter-item').data('index');
        data[index].logic = $(this).val();
        save();
    });

    // Oppdater visningsnavn per språk
    $(document).on('input', '.filter-lang-label', function () {
        const index = $(this).closest('.acf-filter-item').data('index');
        const lang  = $(this).data('lang');

        if (!data[index].labels) data[index].labels = {};
        data[index].labels[lang] = $(this).val();
        save();
    });

    $(document).on('change', '.filter-input-type', function () {
        const index = $(this).closest('.acf-filter-item').data('index');
        data[index].input_type = $(this).val();
        save();
    });

    render();
});