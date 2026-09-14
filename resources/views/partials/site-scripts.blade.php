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
</script>
