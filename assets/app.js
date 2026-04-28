// Archivo: assets/app.js
// Controla interacciones del frontend: tema, modales, validaciones y vistas previas.
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;

    const clearForms = document.querySelectorAll('form.clear-on-reload');
    clearForms.forEach(form => form.reset());

    const themeToggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('agendaTheme') || 'light';

    // Aplica el tema visual y guarda preferencia local.
    const applyTheme = theme => {
        body.classList.toggle('dark', theme === 'dark');
        if (themeToggle) {
            themeToggle.textContent = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
        }
        localStorage.setItem('agendaTheme', theme);
    };

    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const nextTheme = body.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(nextTheme);
        });
    }

    // Activa el comportamiento "ver/ocultar contraseña" en formularios.
    const passwordToggles = document.querySelectorAll('[data-toggle-password]');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('change', () => {
            const selectorList = (toggle.dataset.togglePassword || '')
                .split(',')
                .map(s => s.trim())
                .filter(Boolean);
            selectorList.forEach(selector => {
                const input = document.querySelector(selector);
                if (!input) return;
                input.type = toggle.checked ? 'text' : 'password';
            });
        });
    });

    const message = body.dataset.alertMessage || '';
    const type = body.dataset.alertType || 'success';

    if (message) {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Listo' : 'Error',
            text: message,
            confirmButtonColor: '#d33',
            background: body.classList.contains('dark') ? '#121827' : '#ffffff',
            color: body.classList.contains('dark') ? '#f5f5f7' : '#111111'
        });
    }

    const searchInput = document.getElementById('searchContacts');
    const contactList = document.getElementById('contactList');

    if (searchInput && contactList) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            const cards = contactList.querySelectorAll('.contact-card');
            cards.forEach(card => {
                const name = card.dataset.name || '';
                const phone = card.dataset.phone || '';
                const email = card.dataset.email || '';
                const visible = name.includes(query) || phone.includes(query) || email.includes(query);
                card.style.display = visible ? 'grid' : 'none';
            });
        });
    }

    const menuButtons = document.querySelectorAll('.btn-menu');
    menuButtons.forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const dropdown = btn.nextElementSibling;
            document.querySelectorAll('.dropdown-menu.show').forEach(d => {
                if (d !== dropdown) d.classList.remove('show');
            });
            dropdown.classList.toggle('show');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown-menu.show').forEach(d => d.classList.remove('show'));
    });

    const closeModal = document.getElementById('closeModal');
    const editModal = document.getElementById('editModal');
    const openAddContact = document.getElementById('openAddContact');
    const addModal = document.getElementById('addModal');
    const closeAddModal = document.getElementById('closeAddModal');

    const syncAddButtonVisibility = () => {
        if (!openAddContact || !editModal) return;
        openAddContact.classList.toggle('is-hidden', editModal.classList.contains('show'));
    };

    const closeEditModal = () => {
        if (!editModal) return;
        editModal.classList.remove('show');
        syncAddButtonVisibility();
    };

    if (closeModal && editModal) {
        closeModal.addEventListener('click', closeEditModal);
        editModal.addEventListener('click', e => {
            if (e.target === editModal) closeEditModal();
        });
    }

    if (openAddContact && addModal && closeAddModal) {
        openAddContact.addEventListener('click', () => addModal.classList.add('show'));
        closeAddModal.addEventListener('click', () => addModal.classList.remove('show'));
        addModal.addEventListener('click', e => {
            if (e.target === addModal) addModal.classList.remove('show');
        });
    }

    const profilePhotoInput = document.getElementById('profilePhotoInput');
    const profilePhotoTrigger = document.getElementById('profilePhotoTrigger');
    if (profilePhotoInput && profilePhotoTrigger) {
        profilePhotoTrigger.addEventListener('click', () => profilePhotoInput.click());
        profilePhotoInput.addEventListener('change', () => {
            if (!profilePhotoInput.files || profilePhotoInput.files.length === 0) return;
            const form = profilePhotoInput.closest('form');
            if (form) form.submit();
        });
    }

    // Restringe los campos telefónicos a solo dígitos.
    const sanitizePhoneInput = input => {
        if (!input) return;
        input.addEventListener('input', function () {
            const onlyDigits = this.value.replace(/\D/g, '').slice(0, 15);
            if (onlyDigits !== this.value) {
                this.value = onlyDigits;
            }
        });
    };

    const addTelefono = document.getElementById('addTelefono');
    const editTelefono = document.getElementById('editTelefono');
    const addCodigoPostal = document.getElementById('addCodigoPostal');
    const editCodigoPostal = document.getElementById('editCodigoPostal');
    const addParentesco = document.getElementById('addParentesco');
    const addParentescoOtroWrap = document.getElementById('addParentescoOtroWrap');
    const addParentescoOtro = document.getElementById('addParentescoOtro');
    const editParentesco = document.getElementById('editParentesco');
    const editParentescoOtroWrap = document.getElementById('editParentescoOtroWrap');
    const editParentescoOtro = document.getElementById('editParentescoOtro');
    const baseParentescos = ['Padre', 'Madre', 'Hijo', 'Hija', 'Hermano', 'Hermana', 'Pareja', 'Amigo', 'Trabajo', 'Otro'];

    sanitizePhoneInput(addTelefono);
    sanitizePhoneInput(editTelefono);

    // Ajusta reglas de longitud del teléfono según el prefijo seleccionado.
    const applyPhoneRuleByPrefix = (select, input) => {
        if (!select || !input) return;
        const selected = select.options[select.selectedIndex];
        if (!selected) return;
        const min = parseInt(selected.dataset.min || '6', 10);
        const max = parseInt(selected.dataset.max || '15', 10);
        const validMin = Number.isFinite(min) ? min : 6;
        const validMax = Number.isFinite(max) ? max : 15;
        const patternMax = Math.min(validMax, 15);

        input.maxLength = patternMax;
        input.pattern = validMin === validMax ? `[0-9]{${validMin}}` : `[0-9]{${validMin},${patternMax}}`;
        input.placeholder = validMin === validMax
            ? `Número sin prefijo (${validMin} dígitos)`
            : `Número sin prefijo (${validMin}-${patternMax} dígitos)`;
        input.value = input.value.replace(/\D/g, '').slice(0, patternMax);
    };

    if (addCodigoPostal && addTelefono) {
        addCodigoPostal.addEventListener('change', () => applyPhoneRuleByPrefix(addCodigoPostal, addTelefono));
        applyPhoneRuleByPrefix(addCodigoPostal, addTelefono);
    }

    if (editCodigoPostal && editTelefono) {
        editCodigoPostal.addEventListener('change', () => applyPhoneRuleByPrefix(editCodigoPostal, editTelefono));
        applyPhoneRuleByPrefix(editCodigoPostal, editTelefono);
    }

    // Muestra/oculta campo de texto cuando se selecciona parentesco "Otro".
    const applyParentescoRule = (select, otherWrap, otherInput) => {
        if (!select || !otherWrap || !otherInput) return;
        const isOther = select.value === 'Otro';
        otherWrap.classList.toggle('is-hidden', !isOther);
        otherInput.required = isOther;
        if (!isOther) {
            otherInput.value = '';
        }
    };

    if (addParentesco && addParentescoOtroWrap && addParentescoOtro) {
        addParentesco.addEventListener('change', () => applyParentescoRule(addParentesco, addParentescoOtroWrap, addParentescoOtro));
        applyParentescoRule(addParentesco, addParentescoOtroWrap, addParentescoOtro);
    }

    if (editParentesco && editParentescoOtroWrap && editParentescoOtro) {
        editParentesco.addEventListener('change', () => applyParentescoRule(editParentesco, editParentescoOtroWrap, editParentescoOtro));
        applyParentescoRule(editParentesco, editParentescoOtroWrap, editParentescoOtro);
    }

    const ensureDialOption = (select, value) => {
        if (!select || !value) return;
        const hasOption = Array.from(select.options).some(opt => opt.value === value);
        if (!hasOption) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = 'Prefijo +' + value;
            select.appendChild(option);
        }
    };

    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const card = btn.closest('.contact-card');
            const menuBtn = card.querySelector('.btn-menu');

            const id = menuBtn.dataset.id;
            const nombre = menuBtn.dataset.nombre;
            const telefono = menuBtn.dataset.telefono;
            const codigoPostal = menuBtn.dataset.codigopostal || '';
            const email = menuBtn.dataset.email || '';
            const parentesco = menuBtn.dataset.parentesco || '';

            const editId = document.getElementById('editId');
            const editNombre = document.getElementById('editNombre');
            const editTelefono = document.getElementById('editTelefono');
            const editEmail = document.getElementById('editEmail');
            const editCpSelect = document.getElementById('editCodigoPostal');
            const editParentescoSelect = document.getElementById('editParentesco');
            const editParentescoOtroInput = document.getElementById('editParentescoOtro');
            const editParentescoOtroContainer = document.getElementById('editParentescoOtroWrap');

            if (editId) editId.value = id;
            if (editNombre) editNombre.value = nombre;
            if (editTelefono) editTelefono.value = telefono;
            if (editEmail) editEmail.value = email;

            ensureDialOption(editCpSelect, codigoPostal);
            if (editCpSelect) editCpSelect.value = codigoPostal;

            if (editParentescoSelect && editParentescoOtroInput && editParentescoOtroContainer) {
                if (parentesco && !baseParentescos.includes(parentesco)) {
                    editParentescoSelect.value = 'Otro';
                    editParentescoOtroInput.value = parentesco;
                } else {
                    editParentescoSelect.value = parentesco;
                    if (parentesco !== 'Otro') {
                        editParentescoOtroInput.value = '';
                    }
                }
                applyParentescoRule(editParentescoSelect, editParentescoOtroContainer, editParentescoOtroInput);
            }

            if (editCodigoPostal && editTelefono) {
                applyPhoneRuleByPrefix(editCodigoPostal, editTelefono);
            }

            if (addModal) {
                addModal.classList.remove('show');
            }
            if (editModal) editModal.classList.add('show');
            syncAddButtonVisibility();
            document.querySelectorAll('.dropdown-menu.show').forEach(d => d.classList.remove('show'));
        });
    });

    const imageViewer = document.getElementById('imageViewer');
    const imageViewerContent = document.getElementById('imageViewerContent');
    const imageViewerClose = document.getElementById('imageViewerClose');

    // Abre el visor flotante de imágenes.
    const openImageViewer = (src, altText) => {
        if (!imageViewer || !imageViewerContent) return;
        imageViewerContent.src = src;
        imageViewerContent.alt = altText || 'Vista previa';
        imageViewer.classList.add('show');
        imageViewer.setAttribute('aria-hidden', 'false');
    };

    // Cierra el visor flotante de imágenes.
    const closeImageViewer = () => {
        if (!imageViewer || !imageViewerContent) return;
        imageViewer.classList.remove('show');
        imageViewer.setAttribute('aria-hidden', 'true');
        imageViewerContent.src = '';
    };

    const imageTriggers = document.querySelectorAll('.preview-image-trigger');
    imageTriggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            const src = trigger.dataset.imageSrc || '';
            const altText = trigger.dataset.imageAlt || 'Vista previa';
            if (!src) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin foto',
                    text: 'Aún no hay una imagen para mostrar.'
                });
                return;
            }
            openImageViewer(src, altText);
        });
    });

    if (imageViewerClose) {
        imageViewerClose.addEventListener('click', closeImageViewer);
    }

    if (imageViewer) {
        imageViewer.addEventListener('click', e => {
            if (e.target === imageViewer) {
                closeImageViewer();
            }
        });
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeImageViewer();
            closeEditModal();
        }
    });

    syncAddButtonVisibility();
});
