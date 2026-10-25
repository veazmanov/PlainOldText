// ./html/js/cite.js
// The dagger after an article's title opens and closes the citation beneath it, without jumping to #cite;
// "Summary" in the dateline does the same for the summary.
document.querySelectorAll('.cite-mark, .summary-toggle').forEach(function (mark) {
    var panel = document.getElementById(mark.getAttribute('aria-controls'));
    mark.addEventListener('click', function (event) {
        event.preventDefault();
        var open = !panel.classList.contains('open');
        panel.classList.toggle('open', open);
        mark.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});

// "Copy" under the citation: puts it on the clipboard, with italics where the paste target supports them, or selects
// it for copying by hand.
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
