(function () {
    var navbar = document.querySelector('.navbar');
    if (!navbar) {
        return;
    }

    var hasHero = document.body.classList.contains('has-hero');
    var scrollThreshold = 80;

    function updateNavbarState() {
        var scrolledPastHero = window.scrollY > scrollThreshold;

        if (hasHero && !scrolledPastHero) {
            navbar.classList.remove('navbar-light');
            navbar.classList.add('navbar-dark');
            navbar.classList.remove('scrolled');
        } else {
            navbar.classList.remove('navbar-dark');
            navbar.classList.add('navbar-light');
            navbar.classList.add('scrolled');
        }
    }

    updateNavbarState();
    window.addEventListener('scroll', updateNavbarState);
})();
