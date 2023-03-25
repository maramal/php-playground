function changeDirection(button) {
    const row = document.querySelector('.row');

    if (button.id === 'view-h') {
        row.style.flexDirection = 'row';
        row.classList.add('horizontal-view');
        row.classList.remove('vertical-view');

        document.querySelector('#view-v').classList.remove('selected');

        setCookie('wp-phpp-view', 'horizontal', 30);

    } else {
        row.style.flexDirection = 'column';
        row.classList.add('vertical-view');
        row.classList.remove('horizontal-view');

        document.querySelector('#view-h').classList.remove('selected');

        setCookie('wp-phpp-view', 'vertical', 30);
    }

    button.classList.add('selected');
}

function setInitialView() {
    const savedView = getCookie('wp-phpp-view');
    if (savedView) {
        const id = savedView === 'horizontal' ? 'view-h' : 'view-v';
        const button = document.getElementById(id);
        changeDirection(button);
    }
}