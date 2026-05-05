document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initFormValidation();
    initTabKey();
});

function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            const pane = document.getElementById('tab-' + target);
            if (pane) pane.classList.add('active');
        });
    });
}

function initFormValidation() {
    document.querySelectorAll('form[novalidate]').forEach(form => {
        form.addEventListener('submit', function (e) {
            let valid = true;
            this.querySelectorAll('[required]').forEach(field => {
                clearError(field);
                if (!field.value.trim()) {
                    showError(field, 'Campo obbligatorio.');
                    valid = false;
                } else if (field.minLength > 0 && field.value.trim().length < field.minLength) {
                    showError(field, 'Minimo ' + field.minLength + ' caratteri.');
                    valid = false;
                } else if (field.type === 'number') {
                    const val = parseFloat(field.value);
                    if (field.min && val < parseFloat(field.min)) {
                        showError(field, 'Valore minimo: ' + field.min + '.');
                        valid = false;
                    } else if (field.max && val > parseFloat(field.max)) {
                        showError(field, 'Valore massimo: ' + field.max + '.');
                        valid = false;
                    }
                } else if (field.pattern) {
                    const re = new RegExp('^' + field.pattern + '$');
                    if (!re.test(field.value.trim())) {
                        showError(field, 'Formato non valido.');
                        valid = false;
                    }
                }
            });

            const pw = this.querySelector('[name="new_password"]');
            const cf = this.querySelector('[name="confirm_password"]');
            if (pw && cf && pw.value && cf.value && pw.value !== cf.value) {
                showError(cf, 'Le password non coincidono.');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    });
}

function showError(field, msg) {
    field.classList.add('is-invalid');
    const errId = 'err-' + field.name;
    const errEl = document.getElementById(errId) || field.nextElementSibling;
    if (errEl && errEl.classList.contains('error-text')) {
        errEl.textContent = msg;
    }
}

function clearError(field) {
    field.classList.remove('is-invalid');
    const errId = 'err-' + field.name;
    const errEl = document.getElementById(errId) || field.nextElementSibling;
    if (errEl && errEl.classList.contains('error-text')) {
        errEl.textContent = '';
    }
}

function initTabKey() {
    document.querySelectorAll('.code-editor').forEach(editor => {
        editor.addEventListener('keydown', function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
    });
}
