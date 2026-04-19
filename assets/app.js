document.addEventListener('DOMContentLoaded', function() {
    // Limpiar formularios cuando se recarga la página.
    const clearForms = document.querySelectorAll('form.clear-on-reload');
    clearForms.forEach(form => form.reset());

    // Cargar tema guardado en localStorage o usar tema claro por defecto.
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('agendaTheme') || 'light';

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

    // Mostrar alertas SweetAlert2 si algún mensaje fue enviado desde el servidor.
    const message = body.dataset.alertMessage || '';
    const type = body.dataset.alertType || 'success';

    if (message) {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? '¡Genial!' : 'Ups...',
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
});
