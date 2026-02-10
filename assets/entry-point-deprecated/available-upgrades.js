import handler from 'pages/invites/availableUpgrades';

const btn = document.querySelector('#availableUpgradesBtn');
const stars = btn.getAttribute('data-stars');

btn.addEventListener('click', (e) => {
    e.preventDefault();
    handler(stars);
});
