(function () {
    const storageKey = 'office_theme';
    const root = document.documentElement;
    const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function storedTheme() {
        try { return localStorage.getItem(storageKey) || ''; } catch (error) { return ''; }
    }

    function apply(theme) {
        const dark = theme === 'dark' || (!theme && media && media.matches);
        root.classList.toggle('theme-dark', !!dark);
        const button = document.getElementById('theme-toggle');
        if (button) {
            button.textContent = dark ? '☀️ 浅色模式' : '🌙 深色模式';
            button.setAttribute('aria-label', dark ? '切换到浅色模式' : '切换到深色模式');
            button.title = dark ? '切换到浅色模式' : '切换到深色模式';
        }
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', dark ? '#0f172a' : '#4f46e5');
    }

    function toggle() {
        const next = root.classList.contains('theme-dark') ? 'light' : 'dark';
        try { localStorage.setItem(storageKey, next); } catch (error) {}
        apply(next);
    }

    function mount() {
        if (document.getElementById('theme-toggle')) return;
        const button = document.createElement('button');
        button.id = 'theme-toggle';
        button.className = 'theme-toggle';
        button.type = 'button';
        button.addEventListener('click', toggle);
        const footer = document.querySelector('.sidebar-footer');
        if (footer && footer.parentNode) footer.parentNode.insertBefore(button, footer);
        else document.body.appendChild(button);
        apply(storedTheme());
    }

    if (media) media.addEventListener('change', function () { if (!storedTheme()) apply(''); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
    else mount();
})();
