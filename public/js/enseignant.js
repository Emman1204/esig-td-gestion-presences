document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#tableSeancesProf tbody");
    const toggleButton = document.getElementById("toggleFilter");
    const zonePresences = document.getElementById("listePresences");

    let showOnlyPresent = true; // Par défaut : on affiche ceux qui ont pointé

    // -------------------------------
    // Fonction pour charger les séances depuis le serveur
    // -------------------------------
    function loadSeances() {
        fetch("/enseignant/getSeances", { method: "GET", credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                if (data.status !== "success") {
                    console.error("Erreur récupération séances");
                    return;
                }

                tableBody.innerHTML = "";

                data.seances.forEach(seance => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${seance.SPP_SEAN_DATE}</td>
                        <td>${seance.SPP_SEAN_HEURE_DEB ?? ""}</td>
                        <td>${seance.SPP_SEAN_HEURE_FIN ?? ""}</td>
                        <td>${seance.SPP_UTIL_NOM} ${seance.SPP_UTIL_PRENOM}</td>
                        <td>
                            <select class="status-select" data-seance-id="${seance.SPP_SEAN_ID}" data-eleve-id="${seance.eleve_id}">
                                <option value="">En attente</option>
                                <option value="PRESENT">Présent</option>
                                <option value="ABSENT">Absent</option>
                                <option value="EXCUSE">Excusé</option>
                                <option value="RETARD">Retard</option>
                            </select>
                        </td>
                    `;
                    tableBody.appendChild(tr);

                    // Initialiser le select avec le status actuel
                    const select = tr.querySelector(".status-select");
                    if (seance.SPP_ENS_SEAN_STATUS) {
                        select.value = seance.SPP_ENS_SEAN_STATUS;
                    }

                    // Listener pour mise à jour du status
                    select.addEventListener("change", function () {
                        fetch("/enseignant/validerPresence", {
                            method: "POST",
                            credentials: "same-origin",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                seanceId: this.dataset.seanceId,
                                eleveId: this.dataset.eleveId,
                                status: this.value
                            })
                        })
                        .then(res => res.json())
                        .then(resData => {
                            if (resData.status !== "success") alert("Erreur lors de la validation");
                        })
                        .catch(err => console.error(err));
                    });
                });
            })
            .catch(err => console.error("Erreur fetch séances:", err));
    }

    // Charger les séances au chargement
    loadSeances();
    window.reloadSeancesProf = loadSeances; // pour pouvoir relancer si besoin

    // -------------------------------
    // Toggle : voir seulement ceux qui ont pointé / tous les élèves
    // -------------------------------
    toggleButton.addEventListener("click", () => {
        showOnlyPresent = !showOnlyPresent;

        if (showOnlyPresent) {
            // Affiche seulement ceux qui ont pointé
            zonePresences.innerHTML = `
                <h3>Élèves ayant pointé</h3>
                <p>(La liste réelle des élèves ayant pointé sera affichée ici)</p>
            `;
            toggleButton.textContent = "Voir tous les élèves";
        } else {
            // Affiche tous les élèves (test pour le moment)
            zonePresences.innerHTML = `
                <h3>Afficher la classe</h3>
                <p>(La liste complète des élèves sera affichée ici)</p>
            `;
            toggleButton.textContent = "Voir seulement ceux qui ont pointé";
        }
    });
});
