document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("btnPresence");
    const timerEl = document.getElementById("timer");

    if (!btn) return;

    let timerLive = null; // 🔹 Timer live global
    let etat = "depart";
    let currentSeanceId = parseInt(btn.dataset.seanceId) || 0;
    const heureDebut = btn.dataset.heureDeb || null;

    // Déterminer l'état initial
    if (currentSeanceId > 0) {
        etat = "fin";
        btn.textContent = "Fin";
        btn.classList.add("fin"); // 🔴
        if (heureDebut) startTimerLive(heureDebut);
    }


    // Formater le temps HH:MM:SS
    function formatTime(sec) {
        const h = String(Math.floor(sec / 3600)).padStart(2, "0");
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, "0");
        const s = String(sec % 60).padStart(2, "0");
        return `${h}:${m}:${s}`;
    }

    // 🔹 Démarrer le timer live à partir de l'heure de départ
    function startTimerLive(heureDeb) {
        if (!heureDeb) return;

        clearInterval(timerLive);

        function updateTimer() {
            const [h, m, s] = heureDeb.split(":").map(Number);
            const now = new Date();
            const debut = new Date(now.getFullYear(), now.getMonth(), now.getDate(), h, m, s);
            let diff = Math.floor((now - debut) / 1000);
            if (diff < 0) diff = 0;
            timerEl.textContent = formatTime(diff);
            timerEl.classList.remove("hidden");
        }

        updateTimer();
        timerLive = setInterval(updateTimer, 1000);
    }

    // 🔹 Arrêter complètement le timer
    function stopTimer() {
        clearInterval(timerLive); // Arrêt réel
        timerLive = null;         // Reset variable
        timerEl.textContent = "00:00:00";
        timerEl.classList.add("hidden");
    }

    btn.addEventListener("click", () => {
        const action = etat === "depart" ? "depart" : "fin";
        const heure = new Date().toLocaleTimeString("fr-FR", { hour12: false });

        fetch("/public/eleve/marquerPresence", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ seanceId: currentSeanceId, action, heure })
        })
            .then(async res => {
                const text = await res.text();
                try {
                    const data = JSON.parse(text);

                    if (data.status === "success") {
                        if (data.seanceId) {
                            currentSeanceId = data.seanceId;
                            btn.dataset.seanceId = currentSeanceId;
                            // 🔹 Stocker l'heure de départ uniquement si action depart
                            if (action === "depart") {
                                btn.dataset.heureDeb = heure;
                            }
                        }

                        if (action === "depart") {
                            etat = "fin";
                            btn.textContent = "Fin";
                            btn.classList.add("fin"); // 🔴 bouton rouge
                            startTimerLive(heure);
                        }
                        else {
                            etat = "depart";
                            btn.textContent = "Départ";
                            stopTimer(); // 🔹 Arrêt réel du timer
                            btn.classList.remove("fin"); // 🟢 retour au vert
                            btn.dataset.heureDeb = ""; // 🔹 Réinitialiser pour que seance.js ne redémarre pas le timer
                        }

                        if (window.reloadSeances) window.reloadSeances();
                    } else {
                        alert(data.message || "Erreur lors du pointage");
                    }
                } catch (e) {
                    console.error("Réponse non JSON :", text);
                }
            })
            .catch(err => console.error("Erreur AJAX :", err));
    });
});
