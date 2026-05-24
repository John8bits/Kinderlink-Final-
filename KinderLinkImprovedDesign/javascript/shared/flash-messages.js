(function () {
    const messages = window.KinderLinkFlashMessages || [];
    if (!Array.isArray(messages) || typeof Swal === 'undefined') return;
    messages.forEach((message) => Swal.fire(message));
})();
