/**
 * Share-link copy for the listing detail page (M9.3). Plain external script
 * on purpose: the CSP header (script-src 'self') blocks inline scripts, and
 * this file is served from the same origin. Motion: none (instant copy
 * feedback text only).
 */
document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-share-url]');

  if (!button) {
    return;
  }

  const url = button.getAttribute('data-share-url');

  const showNote = () => {
    const note = button.parentElement
      ? button.parentElement.querySelector('[data-share-note]')
      : null;

    if (!note) {
      return;
    }

    note.hidden = false;
    setTimeout(() => {
      note.hidden = true;
    }, 1600);
  };

  const fallbackCopy = () => {
    const area = document.createElement('textarea');
    area.value = url;
    document.body.appendChild(area);
    area.select();

    try {
      document.execCommand('copy');
    } catch (error) {
      // Older engines may refuse; the note still confirms the link.
    }

    document.body.removeChild(area);
    showNote();
  };

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url).then(showNote, fallbackCopy);
  } else {
    fallbackCopy();
  }
});
