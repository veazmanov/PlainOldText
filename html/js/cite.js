// ./html/js/cite.js
// "Copy" under "View citation" in the cog menu: puts the citation on the clipboard, with italics where the paste target
// supports them, or selects it for copying by hand.
document.querySelectorAll('.cite-copy').forEach(function (button) {
    var citation = document.getElementById(button.getAttribute('data-target'));
    button.hidden = false;

    function done(label) {
        button.textContent = label;
        setTimeout(function () { button.textContent = 'Copy'; }, 2000);
    }

    function selectByHand() {
        var range = document.createRange();
        range.selectNodeContents(citation);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        done('Selected');
    }

    button.addEventListener('click', function () {
        var text = citation.textContent.replace(/\s+/g, ' ').trim();
        if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
            navigator.clipboard.write([new ClipboardItem({
                'text/html': new Blob([citation.innerHTML.trim()], { type: 'text/html' }),
                'text/plain': new Blob([text], { type: 'text/plain' })
            })]).then(function () { done('Copied'); }, selectByHand);
        } else if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { done('Copied'); }, selectByHand);
        } else {
            selectByHand();
        }
    });
});
