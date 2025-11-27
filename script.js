// LOGIN

const btns = document.querySelectorAll('.button_login');
const connectBtn = document.querySelector('.connect');
const inscBtn = document.querySelector('.insc');
const containerConnect = document.querySelector('.container_connect');
const containerInsc = document.querySelector('.container_insc');
const closeIcons = document.querySelectorAll('.container_connect img, .container_insc img');

function showBtns() {
    btns.forEach(btn => btn.style.display = 'flex');
}

function hideBtns() {
    btns.forEach(btn => btn.style.display = 'none');
}

closeIcons.forEach(icon => {
    icon.addEventListener('click', () => {
        showBtns();
        containerConnect.style.display = 'none';
        containerInsc.style.display = 'none';
    });
});

connectBtn.addEventListener('click', () => {
    hideBtns();
    containerConnect.style.display = 'block';
    containerInsc.style.display = 'none';
});

inscBtn.addEventListener('click', () => {
    hideBtns();
    containerConnect.style.display = 'none';
    containerInsc.style.display = 'block';
});
