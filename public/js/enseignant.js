document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#tableSeancesProf tbody");
    const toggleButton = document.getElementById("toggleFilter");
    const zonePresences = document.getElementById("listePresences");

    if (!tableBody) {
        console.error("Erreur : le tableau #tableSeancesProf n'existe pas dans le DOM");
        return;
    }

    let showOnlyPresent = false; // On affiche tous les élèves par défaut

    function loadSeances() {
        fetch("enseignant/getSeances", { method: "GET", credentials: "same-origin" })
            .then(res => res.json())
            .then(data => {
                if (data.status !== "success") {
                    console.error("Erreur récupération séances");
                    return;
                }

                tableBody.innerHTML = "";

                const seancesGrouped = {};
                data.seances.forEach(seance => {
                    const seanceId = seance.SPP_SEAN_ID;
                    if (!seancesGrouped[seanceId]) {
                        seancesGrouped[seanceId] = { ...seance, eleves: [] };
                    }
                    seancesGrouped[seanceId].eleves.push({
                        id: seance.eleve_id,
                        nom: seance.eleve_nom,
                        prenom: seance.eleve_prenom,
                        status: seance.SPP_ENS_SEAN_STATUS || 'EN ATTENTE'
                    });
                });

                Object.values(seancesGrouped).forEach(seance => {
                    const tr = document.createElement("tr");
                    const date = seance.SPP_SEAN_DATE ?? "-";
                    const debut = seance.SPP_SEAN_HEURE_DEB ?? "-";
                    const fin = seance.SPP_SEAN_HEURE_FIN ?? "-";
                    const classe = seance.SPP_CLASSE_NOM ?? "-";

                    // Construire sous-table de tous les élèves (plus de filtre)
                    let elevesHTML = `<table border="0" width="100%">`;
                    seance.eleves.forEach(eleve => {
                        elevesHTML += `
                            <tr>
                                <td>${eleve.prenom} ${eleve.nom}</td>
                                <td>
                                    <select class="status-select"
                                        data-seance-id="${seance.SPP_SEAN_ID}"
                                        data-eleve-id="${eleve.id}">
                                        <option value="EN ATTENTE" ${eleve.status === "EN ATTENTE" ? "selected" : ""}>En attente</option>
                                        <option value="PRESENT" ${eleve.status === "PRESENT" ? "selected" : ""}>Présent</option>
                                        <option value="ABSENT" ${eleve.status === "ABSENT" ? "selected" : ""}>Absent</option>
                                        <option value="EXCUSE" ${eleve.status === "EXCUSE" ? "selected" : ""}>Excusé</option>
                                        <option value="RETARD" ${eleve.status === "RETARD" ? "selected" : ""}>Retard</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn-valider-status"
                                        data-eleve-id="${eleve.id}"
                                        data-seance-id="${seance.SPP_SEAN_ID}">
                                        Confirmer
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    elevesHTML += `</table>`;

                    tr.innerHTML = `
                        <td>${date}</td>
                        <td>${debut}</td>
                        <td>${fin}</td>
                        <td>${classe}</td>
                        <td>${elevesHTML}</td>
                    `;

                    tableBody.appendChild(tr);
                });

                // Listeners
                tableBody.querySelectorAll(".status-select").forEach(select => {
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

                tableBody.querySelectorAll(".btn-valider-status").forEach(btn => {
                    btn.addEventListener("click", function () {
                        const select = this.closest("tr").querySelector(".status-select");
                        select.dispatchEvent(new Event("change"));
                    });
                });
            })
            .catch(err => console.error("Erreur fetch séances:", err));
    }

    loadSeances();
    window.reloadSeancesProf = loadSeances;

    toggleButton.addEventListener("click", () => {
        showOnlyPresent = !showOnlyPresent;
        loadSeances(); // Recharger le tableau (toggle pour futur filtre)
        toggleButton.textContent = showOnlyPresent ? "Voir tous les élèves" : "Voir seulement ceux qui ont pointé";
    });
});
