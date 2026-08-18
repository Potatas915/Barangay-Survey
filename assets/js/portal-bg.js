// ---------------------------------------------------------------------
// Interactive login background: slow-drifting geometric shapes
// (triangles, squares, hexagons, circles) in the site's teal-green
// palette, with a soft mouse-reactive pull. Only runs on pages that
// have a <canvas id="portalBgCanvas">, so it's safe to include on
// both staff/login.php and resident/login.php.
// ---------------------------------------------------------------------
(function () {
    const canvas = document.getElementById("portalBgCanvas");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    const isStaff = document.body.classList.contains("is-staff");

    const COLORS = isStaff ? [
        "rgba(15, 26, 43, 0.10)",   // deep navy
        "rgba(43, 58, 82, 0.14)",   // 700
        "rgba(52, 67, 93, 0.16)",   // 600
        "rgba(71, 89, 122, 0.15)",  // 500
        "rgba(118, 136, 166, 0.18)" // 400
    ] : [
        "rgba(15, 46, 41, 0.10)",   // deep
        "rgba(20, 122, 99, 0.14)", // 700
        "rgba(23, 143, 116, 0.16)",// 600
        "rgba(31, 174, 130, 0.15)",// 500
        "rgba(88, 201, 160, 0.18)" // 400
    ];
    const TYPES = ["triangle", "square", "hexagon", "circle"];

    let w = 0, h = 0, dpr = 1;
    let shapes = [];
    const mouse = { x: null, y: null };

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = window.innerWidth;
        h = window.innerHeight;
        canvas.style.width = w + "px";
        canvas.style.height = h + "px";
        canvas.width = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function makeShape() {
        return {
            type: TYPES[Math.floor(Math.random() * TYPES.length)],
            x: Math.random() * w,
            y: Math.random() * h,
            size: 16 + Math.random() * 44,
            rotation: Math.random() * Math.PI * 2,
            rotSpeed: (Math.random() - 0.5) * 0.005,
            vx: (Math.random() - 0.5) * 0.16,
            vy: (Math.random() - 0.5) * 0.16,
            color: COLORS[Math.floor(Math.random() * COLORS.length)]
        };
    }

    function init() {
        resize();
        const count = Math.max(12, Math.min(24, Math.round((w * h) / 52000)));
        shapes = Array.from({ length: count }, makeShape);
        if (reduceMotion) {
            drawFrame();
        } else {
            requestAnimationFrame(loop);
        }
    }

    function drawShape(s) {
        ctx.save();
        ctx.translate(s.x, s.y);
        ctx.rotate(s.rotation);
        ctx.fillStyle = s.color;
        ctx.beginPath();

        if (s.type === "circle") {
            ctx.arc(0, 0, s.size / 2, 0, Math.PI * 2);
        } else {
            const sides = s.type === "triangle" ? 3 : s.type === "square" ? 4 : 6;
            const r = s.size / 2;
            for (let i = 0; i < sides; i++) {
                const angle = (i / sides) * Math.PI * 2 - Math.PI / 2;
                const px = Math.cos(angle) * r;
                const py = Math.sin(angle) * r;
                if (i === 0) ctx.moveTo(px, py);
                else ctx.lineTo(px, py);
            }
            ctx.closePath();
        }

        ctx.fill();
        ctx.restore();
    }

    function drawFrame() {
        ctx.clearRect(0, 0, w, h);
        shapes.forEach(drawShape);
    }

    function loop() {
        ctx.clearRect(0, 0, w, h);

        shapes.forEach((s) => {
            // Gentle constant drift
            s.x += s.vx;
            s.y += s.vy;
            s.rotation += s.rotSpeed;

            // Soft, short-range pull toward the cursor — makes the
            // background feel interactive without being distracting.
            if (mouse.x !== null) {
                const dx = mouse.x - s.x;
                const dy = mouse.y - s.y;
                const dist = Math.sqrt(dx * dx + dy * dy) || 1;
                const radius = 240;
                if (dist < radius) {
                    const pull = ((radius - dist) / radius) * 0.35;
                    s.x -= (dx / dist) * pull;
                    s.y -= (dy / dist) * pull;
                }
            }

            // Wrap around the edges instead of bouncing
            const pad = s.size;
            if (s.x < -pad) s.x = w + pad;
            if (s.x > w + pad) s.x = -pad;
            if (s.y < -pad) s.y = h + pad;
            if (s.y > h + pad) s.y = -pad;

            drawShape(s);
        });

        requestAnimationFrame(loop);
    }

    let resizeTimer;
    window.addEventListener("resize", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resize, 150);
    });

    window.addEventListener("mousemove", (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
    });
    window.addEventListener("mouseleave", () => {
        mouse.x = null;
        mouse.y = null;
    });
    window.addEventListener("touchmove", (e) => {
        if (e.touches && e.touches[0]) {
            mouse.x = e.touches[0].clientX;
            mouse.y = e.touches[0].clientY;
        }
    }, { passive: true });

    init();
})();