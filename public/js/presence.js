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

    console.log("🟢 CLICK détecté");
    console.log("Etat actuel :", etat);
    console.log("SeanceId actuel :", currentSeanceId);

    const action = etat === "depart" ? "depart" : "fin";
    const heure = new Date().toLocaleTimeString("fr-FR", { hour12: false });

    console.log("📤 Données envoyées :", {
        seanceId: currentSeanceId,
        action: action,
        heure: heure
    });

    fetch("/public/eleve/marquerPresence", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ 
            seanceId: currentSeanceId, 
            action, 
            heure 
        })
    })
    .then(async res => {
        console.log("📡 Réponse HTTP status :", res.status);

        const text = await res.text();
        console.log("📥 Réponse brute serveur :", text);

        try {
            const data = JSON.parse(text);
            console.log("📦 JSON parsé :", data);

            if (data.status === "success") {
                console.log("✅ Succès côté serveur");

                if (data.seanceId) {
                    currentSeanceId = data.seanceId;
                    btn.dataset.seanceId = currentSeanceId;
                    console.log("🆔 Nouveau seanceId :", currentSeanceId);

                    if (action === "depart") {
                        btn.dataset.heureDeb = heure;
                    }
                }

                if (action === "depart") {
                    etat = "fin";
                    btn.textContent = "Fin";
                    btn.classList.add("fin");
                    startTimerLive(heure);
                } else {
                    etat = "depart";
                    btn.textContent = "Départ";
                    stopTimer();
                    btn.classList.remove("fin");
                    btn.dataset.heureDeb = "";
                }

                if (window.reloadSeances) {
                    console.log("🔄 Reload séances");
                    window.reloadSeances();
                }

            } else {
                console.log("❌ Erreur logique :", data.message);
                alert(data.message || "Erreur lors du pointage");
            }

        } catch (e) {
            console.error("❌ JSON invalide :", text);
        }
    })
    .catch(err => {
        console.error("❌ Erreur AJAX :", err);
    });
});
});
