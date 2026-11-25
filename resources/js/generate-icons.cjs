const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

function createPng(width, height, drawFn) {
    const buffer = Buffer.alloc(width * height * 4);
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const idx = (y * width + x) * 4;
            const [r, g, b, a] = drawFn(x, y, width, height);
            buffer[idx] = r;
            buffer[idx + 1] = g;
            buffer[idx + 2] = b;
            buffer[idx + 3] = a;
        }
    }

    const rawData = Buffer.alloc(height * (width * 4 + 1));
    for (let y = 0; y < height; y++) {
        rawData[y * (width * 4 + 1)] = 0; // Filter type: None
        buffer.copy(rawData, y * (width * 4 + 1) + 1, y * width * 4, (y + 1) * width * 4);
    }

    const compressed = zlib.deflateSync(rawData);

    const table = new Uint32Array(256);
    for (let i = 0; i < 256; i++) {
        let c = i;
        for (let k = 0; k < 8; k++) {
            c = ((c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1));
        }
        table[i] = c;
    }

    function crc32(buf) {
        let crc = 0 ^ (-1);
        for (let i = 0; i < buf.length; i++) {
            crc = (crc >>> 8) ^ table[(crc ^ buf[i]) & 0xff];
        }
        return (crc ^ (-1)) >>> 0;
    }

    function makeChunk(type, data) {
        const len = Buffer.alloc(4);
        len.writeUInt32BE(data.length, 0);
        const typeAndData = Buffer.concat([Buffer.from(type), data]);
        const crc = Buffer.alloc(4);
        crc.writeUInt32BE(crc32(typeAndData), 0);
        return Buffer.concat([len, typeAndData, crc]);
    }

    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(width, 0);
    ihdr.writeUInt32BE(height, 4);
    ihdr[8] = 8;
    ihdr[9] = 6;
    ihdr[10] = 0;
    ihdr[11] = 0;
    ihdr[12] = 0;

    const signature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
    return Buffer.concat([
        signature,
        makeChunk('IHDR', ihdr),
        makeChunk('IDAT', compressed),
        makeChunk('IEND', Buffer.alloc(0))
    ]);
}

const iconsDir = path.join(__dirname, '..', '..', 'public', 'icons');
if (!fs.existsSync(iconsDir)) {
    fs.mkdirSync(iconsDir, { recursive: true });
}

function renderAlertoIcon(x, y, w, h, isMaskable = false) {
    const cx = w / 2;
    const cy = h / 2;
    const dx = x - cx;
    const dy = y - cy;
    const dist = Math.sqrt(dx * dx + dy * dy);
    const maxR = w / 2;

    const cornerR = w * 0.22;
    const inBoxX = Math.abs(dx) <= (cx - cornerR);
    const inBoxY = Math.abs(dy) <= (cy - cornerR);
    const inCorner = Math.sqrt(Math.max(0, Math.abs(dx) - (cx - cornerR)) ** 2 + Math.max(0, Math.abs(dy) - (cy - cornerR)) ** 2) <= cornerR;

    if (!isMaskable && !(inBoxX || inBoxY || inCorner)) {
        return [0, 0, 0, 0];
    }

    let r = 11, g = 23, b = 38, a = 255; // #0B1726

    // Outer ring #3187D8
    if (dist <= maxR * 0.88 && dist >= maxR * 0.82) {
        return [49, 135, 216, 255];
    }

    // Shield area
    const normX = dx / (w * 0.35);
    const normY = (dy + h * 0.05) / (h * 0.38);

    const inShieldUpper = (normY >= -0.8 && normY <= 0.1 && Math.abs(normX) <= 0.85);
    const inShieldLower = (normY > 0.1 && normY <= 0.95 && Math.abs(normX) <= (0.85 * (1 - (normY - 0.1) / 0.85)));

    if (inShieldUpper || inShieldLower) {
        // Shield background #173957
        r = 23; g = 57; b = 87;
        
        // Inner emergency cross / beacon
        if (Math.abs(dx) <= w * 0.07 && dy >= -h * 0.22 && dy <= h * 0.22) {
            r = 52; g = 185; b = 212; // #34B9D4
        }
        if (Math.abs(dy) <= h * 0.07 && Math.abs(dx) <= w * 0.22) {
            r = 52; g = 185; b = 212;
        }
        // Center alert pulse #DB4545
        const centerDist = Math.sqrt(dx * dx + dy * dy);
        if (centerDist <= w * 0.09) {
            r = 219; g = 69; b = 69;
        }
    }

    return [r, g, b, 255];
}

fs.writeFileSync(path.join(iconsDir, 'icon-192.png'), createPng(192, 192, (x, y, w, h) => renderAlertoIcon(x, y, w, h, false)));
fs.writeFileSync(path.join(iconsDir, 'icon-512.png'), createPng(512, 512, (x, y, w, h) => renderAlertoIcon(x, y, w, h, false)));
fs.writeFileSync(path.join(iconsDir, 'icon-maskable.png'), createPng(512, 512, (x, y, w, h) => renderAlertoIcon(x, y, w, h, true)));

// Also create vector SVG icon
const svgContent = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0B1726" />
      <stop offset="100%" stop-color="#173957" />
    </linearGradient>
    <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#3187D8" />
      <stop offset="100%" stop-color="#173957" />
    </linearGradient>
    <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
      <feGaussianBlur stdDeviation="8" result="blur" />
      <feComposite in="SourceGraphic" in2="blur" operator="over" />
    </filter>
  </defs>
  <rect width="512" height="512" rx="112" fill="url(#bgGrad)" />
  <circle cx="256" cy="256" r="215" fill="none" stroke="#3187D8" stroke-width="4" stroke-opacity="0.4" stroke-dasharray="12 8" />
  
  <!-- Shield Outline -->
  <path d="M256 90 L390 140 C390 270 320 375 256 422 C192 375 122 270 122 140 Z" 
        fill="url(#shieldGrad)" stroke="#34B9D4" stroke-width="6" />
  
  <!-- Inner Cross / Emergency Emblem -->
  <path d="M228 170 H284 V330 H228 Z" fill="#FFFFFF" opacity="0.95" />
  <path d="M176 222 H336 V278 H176 Z" fill="#FFFFFF" opacity="0.95" />
  
  <!-- Red Emergency Alert Beacon Center -->
  <circle cx="256" cy="250" r="38" fill="#DB4545" filter="url(#glow)" />
  <circle cx="256" cy="250" r="18" fill="#FFFFFF" />
  
  <!-- Hinoba-an LDRRMO Star/Accent -->
  <circle cx="256" cy="120" r="8" fill="#F0A82E" />
</svg>`;

fs.writeFileSync(path.join(iconsDir, 'icon.svg'), svgContent);
console.log('All icons generated successfully!');

