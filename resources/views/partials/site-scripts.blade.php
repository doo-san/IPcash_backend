<script>
    (function () {
        var targets = document.querySelectorAll('.reveal, .reveal-scale');
        if (!('IntersectionObserver' in window) || targets.length === 0) {
            targets.forEach(function (el) { el.classList.add('in'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        targets.forEach(function (el) { observer.observe(el); });
    })();

    (function () {
        var toggle = document.getElementById('downloadToggle');
        var menu = document.getElementById('downloadMenu');
        if (!toggle || !menu) return;

        function close() {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = !menu.hidden;
            if (isOpen) { close(); return; }
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        });

        document.addEventListener('click', function (event) {
            if (!menu.hidden && !menu.contains(event.target) && event.target !== toggle) close();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });
    })();
</script>
