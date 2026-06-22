import imageCompression from 'https://esm.sh/browser-image-compression@2';
import UPNG from 'https://esm.sh/upng-js@2';

console.log('UPNG is', UPNG);
 
let COLOR_DARK = [0, 0, 0];  

const COLOR_LIGHT = [244, 244, 244];

let message = document.querySelector('textarea[name="message"]');
let form = document.querySelector('form');
let photo;
const canvas = document.getElementById('pad');
let ctx = canvas.getContext('2d');
let isDrawing = true;
let pencildown = false;
let width = 600;
let height = 400;

const BAYER4 = [
   0,  8,  2, 10,
  12,  4, 14,  6,
   3, 11,  1,  9,
  15,  7, 13,  5,
];

function hexToRgb(hex) {
    hex = hex.trim().replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    return [
        parseInt(hex.slice(0, 2), 16),
        parseInt(hex.slice(2, 4), 16),
        parseInt(hex.slice(4, 6), 16),
    ];
} 
document.getElementById('photo').addEventListener('change', async (e) => {
    isDrawing = false;
  try {
    
    const file = e.target.files[0];
    if (!file) return;
    console.log('picked', file.name, file.type, file.size);
    const small = await imageCompression(file, { maxWidthOrHeight: 600, useWebWorker: true });
    console.log('compressed', small.size);
    photo = await createImageBitmap(small);
    
    console.log('bitmap', photo.width, photo.height);
    redraw();
  } catch (err) {
    console.error('photo handler failed', err);
  }
});

function redraw() {
  ctx.clearRect(0, 0, width, height);
  if (photo) {
    const s = Math.max(width / photo.width, height / photo.height);
    const w = photo.width * s, h = photo.height * s;
    
    ctx.drawImage(photo, (width - w) / 2, (height - h) / 2, w, h);
    bitmap();
    
  
  } else {
    ctx.fillStyle = '#f4f4f4';
    ctx.fillRect(0, 0, width, height);
  }
}
redraw();

   function bitmap() {
    const img = ctx.getImageData(0, 0, width, height);
    const d = img.data;
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const i = (y * width + x) * 4;
            const lum = 0.299*d[i] + 0.587*d[i+1] + 0.114*d[i+2];
            const t = (BAYER4[(y & 3) * 4 + (x & 3)] + 0.5) * (256 / 16);
            const c = lum < t ? COLOR_DARK : COLOR_LIGHT;
            d[i] = c[0]; d[i+1] = c[1]; d[i+2] = c[2]; d[i+3] = 255;
        }
    }
    ctx.putImageData(img, 0, 0);
}




document.getElementById('send').addEventListener('click', async () => {
  const id  = ctx.getImageData(0, 0, width, height);
  const png = UPNG.encode([id.data.buffer], width, height, 0); 
  const blob = new Blob([png], { type: 'image/png' });

  const form = new FormData();
  form.append('image', blob, 'postcard.png');
  form.append('message', document.getElementById('message').value);
  form.append('closer', closerValue);
  form.append('closerColor', closerColorValue);


  const res = await fetch('api/upload.php', { method: 'POST', body: form });
  if (!res.ok) { alert('Upload failed'); return; }

  document.querySelector('.overlay').classList.add('hidden');
  console.log('overlay classes now:', document.querySelector('.overlay').className);
  // Reset the composer.
  photo = null; redraw();
  document.getElementById('message').value = '';
  document.getElementById('photo').value   = '';

  // Refresh the grid immediately instead of waiting for the 15s poll.
  htmx.trigger('#grid', 'refresh');

});
 
// refresh();
// refresh();



document.getElementById('grid').addEventListener('click', (e) => {
    if (e.target.closest('#new-card')) {
        document.querySelector('.overlay').classList.remove('hidden');
        console.log('new card clicked');
        return;                            
    }
    const card = e.target.closest('.card');
    if (!card) return;
    card.classList.toggle('flipped');
 
    const id = card.dataset.id;
    const flipped = new Set(JSON.parse(localStorage.getItem('flipped') || '[]'));
    if (card.classList.contains('flipped')) flipped.add(id);
    else flipped.delete(id);
    localStorage.setItem('flipped', JSON.stringify([...flipped]));

    
   
    
});

function applyFlippedState() {
    const flipped = new Set(JSON.parse(localStorage.getItem('flipped') || '[]'));
    document.querySelectorAll('#grid .card').forEach((card) => {
        if (flipped.has(card.dataset.id)) card.classList.add('flipped');
    });
}

document.body.addEventListener('htmx:afterSwap', (e) => {
    if (e.target.id === 'grid') applyFlippedState();
});


document.querySelector('.overlay').addEventListener('click', (e) => {
    if (e.target.classList.contains('overlay')) {
        e.currentTarget.classList.add('hidden');
    }
});

document.querySelector('.controls').addEventListener('click', (e) => {
    const btn = e.target.closest('.button');
    if (!btn || btn.id === 'photo-label' || btn.id === 'clear' || !btn.style.getPropertyValue('--c') && !getComputedStyle(btn).getPropertyValue('--c').trim()) {
        // not a color button, skip
        return;
    }
    // deselect siblings, select this one
    document.querySelectorAll('.controls .button.selected').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');


    
    if (!btn) return;
    
    const colorVar = getComputedStyle(btn).getPropertyValue('--c').trim();
    if (!colorVar) return;   // not a color button (e.g. file-picker label)
    
    document.querySelectorAll('.controls .button.selected').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    
    COLOR_DARK = hexToRgb(colorVar);
    redraw();   
  
});

let lastX = 0, lastY = 0;
 
canvas.addEventListener('mousedown', (e) => {
    // if (isDrawing == false) {
    //     isDrawing = true;
    //     redraw();
    // }
    pencildown = true;
    lastX = e.offsetX;
    lastY = e.offsetY;
});
 
canvas.addEventListener('mousemove', (e) => {
    if (!pencildown) return;
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.lineWidth = 10;
    ctx.lineCap = 'round';
    ctx.strokeStyle = `rgb(${COLOR_DARK[0]}, ${COLOR_DARK[1]}, ${COLOR_DARK[2]})`;;
    ctx.stroke();
    lastX = e.offsetX;
    lastY = e.offsetY;
});
canvas.addEventListener('mouseup', () => {
    console.log('canvas mouseup');
    pencildown = false;
});

document.getElementById('clear').addEventListener('click', () => {
    redraw();
});


const dd     = document.getElementById('closers');
const toggle = dd.querySelector('.dd-toggle');
const menu   = dd.querySelector('.dd-menu');

let closerValue = 'Love';   // current selection, read this when sending
let closerColorValue = 'blue';   // current selection, read this when sending

toggle.addEventListener('click', () => { menu.hidden = !menu.hidden; });

menu.addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (!li) return;
    closerValue = li.dataset.value;
    closerColorValue = li.classList[0];   // e.g. "red", "blue", "green"
    toggle.textContent = li.textContent + ' ▾';
    toggle.style.backgroundColor = getComputedStyle(li).getPropertyValue('--c').trim();  
    if (getComputedStyle(li).getPropertyValue('--c').trim() === '#FFFF32') {
        toggle.style.color = '#000';
      
    }
    
    menu.hidden = true;
});



document.addEventListener('click', (e) => {
    if (!dd.contains(e.target)) menu.hidden = true;
});