const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
const thresholdInput = document.getElementById('threshold');

let width, height;
let particles = [];
let mouse = { x: null, y: null };
let hue = 0;
let particleConnectionDistance = 120;

const particleCount = 120;
const particleRadius = 3;
const mouseConnectionDistance = 180;
const particleSpeed = 1.5;

function cmToPixels(cm) {
    const dpi = 96;
    const inches = cm / 2.54;
    return inches * dpi;
}

function updateThreshold() {
    const cm = parseFloat(thresholdInput.value) || 1;
    particleConnectionDistance = cmToPixels(cm);
}

thresholdInput.addEventListener('input', updateThreshold);
thresholdInput.addEventListener('change', updateThreshold);

function resize() {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
}

function createParticles() {
    particles = [];
    for (let i = 0; i < particleCount; i++) {
        particles.push({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * particleSpeed * 2,
            vy: (Math.random() - 0.5) * particleSpeed * 2,
            radius: particleRadius + Math.random() * 2
        });
    }
}

function updateParticles() {
    for (const p of particles) {
        p.x += p.vx;
        p.y += p.vy;

        if (p.x < 0 || p.x > width) p.vx *= -1;
        if (p.y < 0 || p.y > height) p.vy *= -1;

        if (p.x < 0) p.x = 0;
        if (p.x > width) p.x = width;
        if (p.y < 0) p.y = 0;
        if (p.y > height) p.y = height;
    }
}

function drawParticles() {
    for (const p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
        ctx.fillStyle = `hsl(${hue}, 100%, 60%)`;
        ctx.fill();
    }
}

function drawConnections() {
    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < particleConnectionDistance) {
                const opacity = 1 - distance / particleConnectionDistance;
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = `hsla(${hue}, 100%, 60%, ${opacity})`;
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }

        if (mouse.x !== null && mouse.y !== null) {
            const dx = particles[i].x - mouse.x;
            const dy = particles[i].y - mouse.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < mouseConnectionDistance) {
                const opacity = 1 - distance / mouseConnectionDistance;
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(mouse.x, mouse.y);
                ctx.strokeStyle = `hsla(${hue + 60}, 100%, 60%, ${opacity})`;
                ctx.lineWidth = 1.5;
                ctx.stroke();
            }
        }
    }
}

function animate() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
    ctx.fillRect(0, 0, width, height);

    hue = (hue + 0.3) % 360;

    updateParticles();
    drawConnections();
    drawParticles();

    requestAnimationFrame(animate);
}

window.addEventListener('resize', () => {
    resize();
    createParticles();
});

canvas.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
});

canvas.addEventListener('mouseleave', () => {
    mouse.x = null;
    mouse.y = null;
});

resize();
createParticles();
updateThreshold();
animate();
