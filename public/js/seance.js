document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#tableSeances tbody");
    if (!tableBody) return;

    let timerInterval = null; // 🔹 Timer live global pour la séance en cours

    // Calculer la durée entre deux heures HH:MM:SS
    function calculerTempsPresence(debut, fin) {
        if (!debut || !fin) return "00:00:00";

        const [hDeb, mDeb, sDeb] = debut.split(":").map(Number);
        const [hFin, mFin, sFin] = fin.split(":").map(Number);

        const dateDeb = new Date(0, 0, 0, hDeb, mDeb, sDeb);
        const dateFin = new Date(0, 0, 0, hFin, mFin, sFin);

        let diffMs = dateFin - dateDeb;
        if (diffMs < 0) diffMs = 0;

        const heures = String(Math.floor(diffMs / 1000 / 3600)).padStart(2, "0");
        const minutes = String(Math.floor((diffMs / 1000 % 3600) / 60)).padStart(2, "0");
        const secondes = String(Math.floor(diffMs / 1000 % 60)).padStart(2, "0");

        return `${heures}:${minutes}:${secondes}`;
    }

    // Formater la date en français (NomJour) dd.mm.yyyy
    function formatDateFr(dateStr) {
        if (!dateStr) return "";
        const jours = ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"];
        const dateObj = new Date(dateStr);
        const nomJour = jours[dateObj.getDay()];
        const jour = String(dateObj.getDate()).padStart(2, "0");
        const mois = String(dateObj.getMonth() + 1).padStart(2, "0");
        const annee = dateObj.getFullYear();
        return `(${nomJour}) ${jour}.${mois}.${annee}`;
    }

    // Créer la cellule commentaire avec le crayon ou texte existant
    function creerCommentCell(seance) {
        if (seance.SPP_SEAN_COMM && seance.SPP_SEAN_COMM.trim() !== "") {
            return `
                <div class="comment-display">
                    ${seance.SPP_SEAN_COMM}
                    <button type="button" class="edit-comment-btn" title="Modifier">✏️</button>
                </div>
            `;
        } else {
            return `
                <div class="comment-display">
                    <button type="button" class="edit-comment-btn" title="Ajouter un commentaire">✏️</button>
                </div>
            `;
        }
    }

    // Démarrer le timer live pour la séance en cours
    function startTimer(timerDiv, heureDebut) {
        if (!heureDebut) return;

        clearInterval(timerInterval);

        function updateTimer() {
            const [h, m, s] = heureDebut.split(":").map(Number);
            const now = new Date();
            const debut = new Date(now.getFullYear(), now.getMonth(), now.getDate(), h, m, s);

            let diffMs = now - debut;
            if (diffMs < 0) diffMs = 0;

            const heures = String(Math.floor(diffMs / 1000 / 3600)).padStart(2, "0");
            const minutes = String(Math.floor((diffMs / 1000 % 3600) / 60)).padStart(2, "0");
            const secondes = String(Math.floor(diffMs / 1000 % 60)).padStart(2, "0");
            timerDiv.textContent = `${heures}:${minutes}:${secondes}`;
        }

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
    }

    // Charger les séances de l'élève
    function loadSeances() {
        fetch("/public/eleve/getSeances", { method: "GET", credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                if (data.status !== "success") return;

                tableBody.innerHTML = "";

                data.seances.forEach(seance => {
                    const tr = document.createElement("tr");

                    tr.innerHTML = `
                        <td>${formatDateFr(seance.SPP_SEAN_DATE)}</td>
                        <td>${seance.SPP_SEAN_HEURE_DEB ?? ""}</td>
                        <td>${seance.SPP_SEAN_HEURE_FIN ?? ""}</td>
                        <td>${calculerTempsPresence(seance.SPP_SEAN_HEURE_DEB, seance.SPP_SEAN_HEURE_FIN)}</td>
                        <td>${creerCommentCell(seance)}</td>
                    `;

                    tableBody.appendChild(tr);

                    // 🔹 Gestion commentaire
                    const td = tr.querySelector("td:nth-child(5)");
                    const editBtn = td.querySelector(".edit-comment-btn");
                    if (editBtn) {
                        editBtn.addEventListener("click", () => {
                            let currentComment = "";
                            const commentDiv = td.querySelector(".comment-display");
                            if (commentDiv) {
                                commentDiv.childNodes.forEach(node => {
                                    if (node.nodeType === Node.TEXT_NODE) {
                                        currentComment += node.textContent.trim();
                                    }
                                });
                            }

                            td.innerHTML = `
                                <form class="comment-form" data-seance-id="${seance.SPP_SEAN_ID}">
                                    <textarea name="commentaire" rows="2" style="width:100%;">${currentComment}</textarea>
                                    <button type="submit">💾</button>
                                </form>
                            `;

                            const form = td.querySelector(".comment-form");
                            form.addEventListener("submit", function (e) {
                                e.preventDefault();
                                const commentaire = this.querySelector("textarea").value.trim();
                                if (!commentaire) return alert("Veuillez saisir un commentaire.");

                                fetch("/public/eleve/commentaire", {
                                    method: "POST",
                                    credentials: "same-origin",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({
                                        seanceId: this.dataset.seanceId,
                                        commentaire
                                    })
                                })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.status === "success") {
                                            td.innerHTML = `
                                            <div class="comment-display">
                                                ${commentaire}
                                                <button type="button" class="edit-comment-btn" title="Modifier">✏️</button>
                                            </div>
                                        `;
                                            const newEditBtn = td.querySelector(".edit-comment-btn");
                                            newEditBtn.addEventListener("click", () => editBtn.click());
                                        } else {
                                            alert("Erreur lors de l'enregistrement du commentaire.");
                                        }
                                    })
                                    .catch(err => console.error("Erreur AJAX commentaire :", err));
                            });
                        });
                    }
                });

                // 🔹 Gestion du timer live synchronisé avec presence.js
                const btnPresence = document.getElementById("btnPresence");
                const timerDiv = document.getElementById("timer");

                if (btnPresence && timerDiv) {
                    // Si heureDeb est vide ou séance terminée, arrêter le timer
                    if (!btnPresence.dataset.heureDeb || btnPresence.dataset.heureDeb.trim() === "") {
                        clearInterval(timerInterval);
                        timerDiv.textContent = "00:00:00";
                        timerDiv.classList.add("hidden");
                    } else {
                        timerDiv.classList.remove("hidden");
                        startTimer(timerDiv, btnPresence.dataset.heureDeb);
                    }
                }
            })
            .catch(err => console.error("Erreur chargement séances :", err));
    }

    // 🔹 Chargement initial
    loadSeances();

    // 🔹 Rendre accessible depuis d'autres scripts
    window.reloadSeances = loadSeances;
});
