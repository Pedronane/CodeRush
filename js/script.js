/* ── INIT ──────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initFormValidation();
    initTabKey();
    initPageTransition();
    initCodeParticles();
    initPasswordStrength();
});

/* ── PAGE TRANSITION ───────────────────────────────────────── */
function initPageTransition() {
    var overlay = document.getElementById('page-transition');
    if (!overlay) return;
    document.body.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') || a.target === '_blank') return;
        if (a.href.startsWith(location.origin) || a.href.startsWith('/')) {
            e.preventDefault();
            overlay.classList.add('show');
            setTimeout(function () {
                window.location.href = a.href;
            }, 320);
        }
    });
}

/* ── CODE PARTICLES ────────────────────────────────────────── */
var SYMBOLS = ['</>', '{ }', '//', '()', '=>', '[]', '&&', '||', 'fn', '0x'];

function spawnParticles(container, density) {
    density = density || 18;
    container.innerHTML = '';
    container.classList.add('code-particles');
    for (var i = 0; i < density; i++) {
        var sym = SYMBOLS[i % SYMBOLS.length];
        var el = document.createElement('span');
        el.className = 'cp-sym';
        el.textContent = sym;
        var left     = Math.random() * 100;
        var top      = Math.random() * 100;
        var delay    = (Math.random() * 6).toFixed(2);
        var duration = (6 + Math.random() * 8).toFixed(2);
        var size     = Math.round(14 + Math.random() * 32);
        el.style.cssText = 'left:' + left + '%;top:' + top + '%;font-size:' + size + 'px;' +
            '--dur:' + duration + 's;--delay:' + delay + 's;' +
            'animation:float ' + duration + 's ease-in-out ' + delay + 's infinite;';
        container.appendChild(el);
    }
}

function initCodeParticles() {
    document.querySelectorAll('[data-particles]').forEach(function (el) {
        var density = parseInt(el.dataset.particles, 10) || 18;
        spawnParticles(el, density);
    });
}

/* ── PASSWORD STRENGTH ─────────────────────────────────────── */
function initPasswordStrength() {
    var newPwd = document.getElementById('new_password');
    var segs   = document.querySelectorAll('.pwd-seg');
    var label  = document.querySelector('.pwd-label');
    if (!newPwd || !segs.length) return;
    var COLORS = ['var(--brand-danger)', 'var(--brand-orange)', 'var(--brand-orange)', 'var(--brand-lime)', 'var(--brand-green)'];
    var LABELS = ['Debole', 'Debole', 'Media', 'Buona', 'Forte'];
    newPwd.addEventListener('input', function () {
        var p = this.value;
        var s = 0;
        if (p.length >= 8) s++;
        if (/[A-Z]/.test(p)) s++;
        if (/[0-9]/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        segs.forEach(function (seg, i) {
            seg.style.background = i < s ? COLORS[s] : 'rgba(255,255,255,.12)';
        });
        if (label) {
            label.textContent = p ? LABELS[s] : '';
            label.style.color = COLORS[s];
        }
    });
}

/* ── TABS ──────────────────────────────────────────────────── */
function initTabs() {
    var tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = this.dataset.tab;
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.tab-pane').forEach(function (p) { p.classList.remove('active'); });
            var pane = document.getElementById('tab-' + target);
            if (pane) pane.classList.add('active');
        });
    });
}

/* ── ROLE TOGGLE (login) ───────────────────────────────────── */
function initRoleToggle() {
    var btns  = document.querySelectorAll('.role-toggle-btn');
    var input = document.getElementById('role-input');
    var labelEl = document.getElementById('login-id-label');
    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            btns.forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            var role = this.dataset.role;
            if (input) input.value = role;
            if (labelEl) labelEl.textContent = role === 'host' ? 'Username' : 'Matricola';
        });
    });
}

/* ── FORM VALIDATION ───────────────────────────────────────── */
function initFormValidation() {
    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;
            this.querySelectorAll('[required]').forEach(function (field) {
                clearError(field);
                if (!field.value.trim()) {
                    showError(field, 'Campo obbligatorio.'); valid = false;
                } else if (field.minLength > 0 && field.value.trim().length < field.minLength) {
                    showError(field, 'Minimo ' + field.minLength + ' caratteri.'); valid = false;
                } else if (field.type === 'number') {
                    var val = parseFloat(field.value);
                    if (field.min && val < parseFloat(field.min)) { showError(field, 'Minimo: ' + field.min); valid = false; }
                    else if (field.max && val > parseFloat(field.max)) { showError(field, 'Massimo: ' + field.max); valid = false; }
                } else if (field.pattern) {
                    if (!new RegExp('^' + field.pattern + '$').test(field.value.trim())) {
                        showError(field, 'Formato non valido.'); valid = false;
                    }
                }
            });
            var pw = this.querySelector('[name="new_password"]');
            var cf = this.querySelector('[name="confirm_password"]');
            if (pw && cf && pw.value && cf.value && pw.value !== cf.value) {
                showError(cf, 'Le password non coincidono.'); valid = false;
            }
            if (!valid) {
                e.preventDefault();
                this.style.animation = 'shake .4s ease-in-out';
                var _this = this;
                setTimeout(function () { _this.style.animation = ''; }, 400);
            }
        });
    });
}

function showError(field, msg) {
    field.classList.add('is-invalid');
    var errEl = document.getElementById('err-' + field.name) || field.nextElementSibling;
    if (errEl && errEl.classList.contains('error-text')) errEl.textContent = msg;
}
function clearError(field) {
    field.classList.remove('is-invalid');
    var errEl = document.getElementById('err-' + field.name) || field.nextElementSibling;
    if (errEl && errEl.classList.contains('error-text')) errEl.textContent = '';
}

/* ── TAB KEY in code editor ────────────────────────────────── */
function initTabKey() {
    document.querySelectorAll('.code-editor').forEach(function (editor) {
        editor.addEventListener('keydown', function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                var s = this.selectionStart, end = this.selectionEnd;
                this.value = this.value.substring(0, s) + '    ' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = s + 4;
            }
        });
    });
}

/* ── CONSEGNA PREVIEW ──────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('selectDomanda');
    var preview = document.getElementById('previewDomanda');
    if (!sel || !preview || typeof domande === 'undefined') return;
    sel.addEventListener('change', function () {
        var d = domande.find(function (x) { return x.id == sel.value; });
        if (d) { preview.textContent = d.testo; preview.style.display = 'block'; }
        else   { preview.style.display = 'none'; }
    });
});
