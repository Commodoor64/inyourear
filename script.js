let message = document.querySelector('textarea[name="message"]');
let form = document.querySelector('form');


// async function refresh() {
//     const r = await fetch('api/postcards.php');
//     const html = await r.text();
//     document.querySelector('.postcards').innerHTML = html;
// }

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        const res = await fetch('api/upload.php', {
            method: 'POST',
            body: new FormData(form),
        });
       
    } catch (err) {
       
    }
    form.reset();
});
 
// refresh();