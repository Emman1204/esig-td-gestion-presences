document.addEventListener("DOMContentLoaded", () => {
    // -------------------------------
    // RÉCUPÉRATION DES ÉLÉMENTS
    // -------------------------------
    const btn = document.getElementById("btnPresence");
    const timerEl = document.getElementById("timer");

    if (!btn) return; // si le bouton n'existe pas, ne rien faire

    // -------------------------------
    // VARIABLES TIMER
    // -------------------------------
    let timer = null;
    let seconds = 0;
    let etat = "depart"; // État actuel du bouton : "depart" ou "fin"
    let currentSeanceId = parseInt(btn.dataset.seanceId) || 0; // ID réel de la séance

    // -------------------------------
    // FONCTIONS TIMER
    // -------------------------------
    function formatTime(sec) {
        const h = String(Math.floor(sec / 3600)).padStart(2, '0');
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    function startTimer() {
        timerEl.classList.remove("hidden");
        timer = setInterval(() => {
            seconds++;
            timerEl.textContent = formatTime(seconds);
        }, 1000);
    }

    function stopTimer() {
        clearInterval(timer);
        seconds = 0;
        timerEl.textContent = "00:00:00";
        timerEl.classList.add("hidden");
    }

    // -------------------------------
    // ÉCOUTEUR SUR LE BOUTON
    // -------------------------------
    btn.addEventListener("click", () => {
        // -------------------------------
        // Déterminer l'action à envoyer
        // -------------------------------
        const action = etat === "depart" ? "depart" : "fin";

        // Mise à jour visuelle côté client
        if (etat === "depart") {
            btn.textContent = "Fin";
            etat = "fin";
            startTimer();
        } else {
            btn.textContent = "Départ";
            etat = "depart";
            stopTimer();
        }

        // -------------------------------
        // AJAX : envoyer la présence au serveur
        // -------------------------------
        console.log("Envoi de la présence : seanceId =", currentSeanceId, "action =", action);
        fetch("/public/eleve/marquerPresence", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({
                seanceId: currentSeanceId, // 0 si séance non créée
                action: action,
                // ⚠️ On envoie uniquement l'heure pour le champ TIME
                heure: new Date().toLocaleTimeString('fr-FR', { hour12: false })
            })

        })
            .then(async res => {
                const text = await res.text();
                try {
                    const data = JSON.parse(text);

                    // -------------------------------
                    // Si le serveur a créé une nouvelle séance, récupérer son ID
                    // -------------------------------
                    if (data.status === "success" && data.seanceId) {
                        currentSeanceId = data.seanceId;
                        btn.dataset.seanceId = currentSeanceId; // mettre à jour le bouton
                    }

                    console.log(`${action.charAt(0).toUpperCase() + action.slice(1)} enregistré :`, data);
                } catch (e) {
                    console.error("Réponse non JSON :", text);
                }
            })
            .catch(err => console.error("Erreur AJAX :", err));
    });
});
