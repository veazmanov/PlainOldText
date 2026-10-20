// ./html/js/page-tools.js
// The cog menu on article pages is a <details>, so it works without JavaScript; this closes it on
// Escape, on a click outside it, and after choosing a link inside it.
(function () {
    var tools = document.getElementById('page-tools');
    if (!tools) return;

    document.addEventListener('click', function (e) {
        if (tools.open && !tools.contains(e.target)) tools.open = false;
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && tools.open) {
            tools.open = false;
            tools.querySelector('summary').focus();
        }
    });
    tools.querySelectorAll('.page-tools-menu a').forEach(function (a) {
        a.addEventListener('click', function () { tools.open = false; });
    });
})();
