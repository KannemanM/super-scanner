document.addEventListener("DOMContentLoaded", function () {

    if (typeof SuperScannerData === "undefined") return;

    var API_BASE = SuperScannerData.apiBase;
    var STORES   = SuperScannerData.stores;

    var scanner    = null;
    var isScanning = false;

    var container = document.getElementById("ss-container");
    if (!container) return;

    var input   = document.getElementById("ss-ean-input");
    var btnCam  = document.getElementById("ss-btn-camera");
    var btnCons = document.getElementById("ss-btn-consultar");
    var reader  = document.getElementById("ss-reader");
    var results = document.getElementById("ss-resultados");

    function isValidEAN(val) {
        return /^\d{8,13}$/.test(val.trim());
    }

    input.addEventListener("input", function () {
        btnCons.disabled = !isValidEAN(this.value);
    });

    btnCam.addEventListener("click", function () {
        if (isScanning) {
            stopScanner();
        } else {
            startScanner();
        }
    });

    function startScanner() {
        if (scanner) scanner.stop();
        reader.style.display = "block";
        btnCam.classList.add("scanning");
        isScanning = true;

        scanner = new Html5Qrcode("ss-reader");
        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 150 } },
            function (decodedText) {
                input.value = decodedText;
                btnCons.disabled = false;
                if (navigator.vibrate) navigator.vibrate(100);
                stopScanner();
            },
            function () {}
        ).catch(function (err) {
            console.error("Error al iniciar cámara:", err);
            stopScanner();
        });
    }

    function stopScanner() {
        if (scanner) {
            try { scanner.stop(); } catch (e) {}
        }
        scanner = null;
        reader.style.display = "none";
        btnCam.classList.remove("scanning");
        isScanning = false;
    }

    btnCons.addEventListener("click", async function () {
        var ean = input.value.trim();
        if (!isValidEAN(ean)) return;

        btnCons.disabled = true;
        btnCons.textContent = "Consultando…";
        results.innerHTML = "<div class='ss-loading'><i class='fas fa-spinner fa-spin'></i> Consultando supermercados…</div>";

        var promises = STORES.map(function (s) {
            return fetch(API_BASE + "/" + s.slug + "/" + ean)
                .then(function (r) { return r.json(); });
        });

        var responses = await Promise.allSettled(promises);

        btnCons.disabled = false;
        btnCons.textContent = "Comparar precios";
        renderResults(responses);
    });

    function renderResults(responses) {
        var html = "";

        responses.forEach(function (res, i) {
            var store = STORES[i];
            var data  = res.status === "fulfilled" ? res.value : null;

            html += '<div class="ss-store-card">';
            html += '<div class="ss-store-header" style="background:' + store.color + '">';
            html += '<i class="fas fa-store"></i> ' + store.label;
            html += '</div>';

            if (data && data.success) {
                var img = data.imagen || "";
                html += '<div class="ss-store-body">';
                if (img) {
                    html += '<div class="ss-store-imagen">';
                    html += '<img src="' + img + '" alt="' + escapeHtml(data.nombre) + '" loading="lazy">';
                    html += '</div>';
                }
                html += '<div class="ss-store-info">';
                html += '<div class="ss-store-nombre">' + escapeHtml(data.nombre) + '</div>';
                html += '<div class="ss-store-marca">' + escapeHtml(data.marca) + '</div>';
                html += '<div class="ss-store-precios">';
                html += '<span class="ss-store-precio-web">$ ' + formatPrice(data.precio_web) + '</span>';
                if (data.tiene_desc) {
                    html += '<span class="ss-store-precio-lista">$ ' + formatPrice(data.precio_lista) + '</span>';
                }
                html += '</div>';
                html += '</div>';
                html += '</div>';
            } else {
                html += '<div class="ss-store-no-disponible">';
                html += '<i class="fas fa-times-circle"></i> No disponible en ' + store.label;
                html += '</div>';
            }

            html += '</div>';
        });

        results.innerHTML = html;
    }

    function formatPrice(n) {
        return Number(n).toLocaleString("es-AR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str || ""));
        return div.innerHTML;
    }

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !btnCons.disabled) {
            btnCons.click();
        }
    });
});
