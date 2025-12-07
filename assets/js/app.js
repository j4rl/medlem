// Main application JavaScript

// Theme management
function setTheme(theme) {
    if (!theme) return;
    document.documentElement.setAttribute('data-theme', theme);
}

function getTheme() {
    return document.documentElement.getAttribute('data-theme') || 'light';
}

// Load theme on page load
document.addEventListener('DOMContentLoaded', function() {
    const theme = getTheme();
    setTheme(theme);
});

// Primary color management
function setPrimaryColor(color) {
    document.documentElement.style.setProperty('--primary', color);
    
    // Calculate hover color (darker)
    const hoverColor = adjustColor(color, -20);
    document.documentElement.style.setProperty('--primary-hover', hoverColor);
}

function getPrimaryColor() {
    const current = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
    return current || '#2563eb';
}

// Adjust color brightness
function adjustColor(color, amount) {
    const num = parseInt(color.replace('#', ''), 16);
    const r = Math.max(0, Math.min(255, (num >> 16) + amount));
    const g = Math.max(0, Math.min(255, ((num >> 8) & 0x00FF) + amount));
    const b = Math.max(0, Math.min(255, (num & 0x0000FF) + amount));
    return '#' + (0x1000000 + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

// Dropdown menu toggle
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.user-menu') && !event.target.closest('.nav-dropdown')) {
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.main-content .container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'just nu';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minut${minutes > 1 ? 'er' : ''} sedan`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} timm${hours > 1 ? 'ar' : 'e'} sedan`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} dag${days > 1 ? 'ar' : ''} sedan`;
    } else {
        return date.toLocaleDateString('sv-SE');
    }
}

// Apply relative dates
document.addEventListener('DOMContentLoaded', function() {
    const dateElements = document.querySelectorAll('[data-date]');
    dateElements.forEach(element => {
        const date = element.getAttribute('data-date');
        element.textContent = formatDate(date);
    });
});

// File upload preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Är du säker på att du vill ta bort detta?');
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.form-textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});

// Search/filter functionality
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Member picker popover (search + copy to clipboard)
(function() {
    if (window.initMemberPicker) return;

    function copyToClipboard(text, onDone) {
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(onDone).catch(() => {
                fallbackCopy(text);
                onDone && onDone();
            });
        } else {
            fallbackCopy(text);
            onDone && onDone();
        }
    }

    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(textarea);
    }

    function buildMemberCard(member, onCopy) {
        const div = document.createElement('div');
        div.className = 'member-hit';

        const meta = document.createElement('div');
        meta.className = 'member-hit__meta';
        meta.innerHTML = `
            <strong>${member.namn || ''}</strong><br>
            <span class="muted">${member.medlnr || ''}</span>
            ${member.forening ? `<div class="muted">${member.forening}</div>` : ''}
            ${member.befattning ? `<div class="muted">${member.befattning}</div>` : ''}
        `;

        const pickableFields = [
            { key: 'namn', label: 'Namn', value: member.namn || '' },
            { key: 'medlnr', label: 'Nr', value: member.medlnr || '' },
            { key: 'forening', label: 'Förening', value: member.forening || '' },
            { key: 'befattning', label: 'Befattning', value: member.befattning || '' },
            { key: 'medlemsform', label: 'Medlemsform', value: member.medlemsform || '' },
            { key: 'verksamhetsform', label: 'Verksamhetsform', value: member.verksamhetsform || '' },
            { key: 'arbetsplats', label: 'Arbetsplats', value: member.arbetsplats || '' },
        ].filter(f => f.value && f.value !== '');

        const checks = document.createElement('div');
        checks.className = 'member-hit__checks';

        pickableFields.forEach(f => {
            const id = `chk-${f.key}-${Math.random().toString(36).slice(2)}`;
            const label = document.createElement('label');
            label.className = 'member-hit__check';
            label.innerHTML = `<input type="checkbox" data-value="${f.value}" id="${id}"> ${f.label}`;
            checks.appendChild(label);
        });

        const actions = document.createElement('div');
        actions.className = 'member-hit__actions';

        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = 'btn btn-primary btn-sm';
        copyBtn.textContent = 'Copy';
        copyBtn.addEventListener('click', () => {
            const checked = Array.from(checks.querySelectorAll('input[type="checkbox"]:checked')).map(c => c.dataset.value || '').filter(Boolean);
            let text = '';

            if (checked.length > 0) {
                text = checked.join('\n');
            } else {
                const parts = [];
                if (member.namn) parts.push(member.namn);
                if (member.medlnr) parts.push(`(${member.medlnr})`);
                if (member.forening) parts.push(`- ${member.forening}`);
                text = parts.join(' ').trim() || JSON.stringify(member, null, 2);
            }

            copyToClipboard(text, () => {
                copyBtn.disabled = true;
                const original = copyBtn.textContent;
                copyBtn.textContent = 'Copied';
                setTimeout(() => {
                    copyBtn.disabled = false;
                    copyBtn.textContent = original;
                }, 900);
                onCopy && onCopy();
            });
        });

        actions.appendChild(copyBtn);

        div.appendChild(meta);
        if (pickableFields.length > 0) {
            div.appendChild(checks);
        }
        div.appendChild(actions);
        return div;
    }

    function renderResults(container, results, onCopy) {
        container.innerHTML = '';
        if (!results || results.length === 0) {
            container.innerHTML = '<p class="muted" style="margin: 0;">Inga träffar.</p>';
            return;
        }
        results.forEach(member => {
            container.appendChild(buildMemberCard(member, onCopy));
        });
    }

    function createPopover() {
        const overlay = document.createElement('div');
        overlay.className = 'member-popover-backdrop';
        overlay.innerHTML = `
            <div class="member-popover" role="dialog" aria-modal="true">
                <div class="member-popover__header">
                    <div>
                        <p class="eyebrow">Medlem</p>
                        <h3 style="margin: 0;">Sök och kopiera</h3>
                        <p class="muted" style="margin: 0;">Sök efter medlem, kopiera valda fält till urklipp.</p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm member-popover__close" aria-label="Close">&times;</button>
                </div>
                <div class="form-group" style="margin-bottom: 0.75rem;">
                    <input type="text" class="form-input" id="memberPopoverSearch" placeholder="Skriv namn eller medlemsnr..." autocomplete="off">
                </div>
                <div class="member-popover__results" id="memberPopoverResults">
                    <p class="muted" style="margin: 0;">Börja skriva för att söka.</p>
                </div>
            </div>
        `;

        const popover = overlay.querySelector('.member-popover');
        const searchInput = overlay.querySelector('#memberPopoverSearch');
        const closeBtn = overlay.querySelector('.member-popover__close');
        const resultsBox = overlay.querySelector('#memberPopoverResults');
        let debounceTimer = null;

        function close() {
            overlay.remove();
        }

        function handleSearch(value) {
            const q = value.trim();
            if (q.length < 2) {
                resultsBox.innerHTML = '<p class="muted" style="margin: 0;">Minst 2 tecken för att söka.</p>';
                return;
            }

            resultsBox.innerHTML = '<p class="muted" style="margin: 0;">Söker...</p>';

            fetch(`member-search.php?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        resultsBox.innerHTML = `<p class="muted" style="margin: 0;">${data.error || 'Kunde inte hämta medlemmar.'}</p>`;
                        return;
                    }
                    renderResults(resultsBox, data.results, () => searchInput.focus());
                })
                .catch(() => {
                    resultsBox.innerHTML = '<p class="muted" style="margin: 0;">Något gick fel vid sökningen.</p>';
                });
        }

        closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) close();
        });

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => handleSearch(e.target.value), 250);
        });

        setTimeout(() => searchInput.focus(), 50);

        return {
            open: () => document.body.appendChild(overlay),
            close
        };
    }

    window.initMemberPicker = function(selector) {
        const triggers = typeof selector === 'string'
            ? document.querySelectorAll(selector)
            : (selector instanceof Element ? [selector] : Array.from(selector || []));

        triggers.forEach(trigger => {
            if (!trigger || trigger.dataset.memberPickerBound === '1') return;
            trigger.dataset.memberPickerBound = '1';

            trigger.addEventListener('click', () => {
                const popover = createPopover();
                popover.open();
            });
        });
    };
})();
