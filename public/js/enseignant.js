document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#tableSeancesProf tbody");

    function loadSeances() {
        fetch("/public/enseignant/getSeances", { method: "GET", credentials: "same-origin" })
        .then(res => res.json())
        .then(data => {
            if (data.status !== "success") return;
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

                // Set current status
                const select = tr.querySelector(".status-select");
                if (seance.SPP_ENS_SEAN_STATUS) {
                    select.value = seance.SPP_ENS_SEAN_STATUS;
                }

                select.addEventListener("change", function() {
                    fetch("/public/enseignant/validerPresence", {
                        method: "POST",
                        credentials: "same-origin",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            seanceId: this.dataset.seanceId,
                            eleveId: this.dataset.eleveId,
                            status: this.value
                        })
                    }).then(res => res.json()).then(resData => {
                        if (resData.status !== "success") alert("Erreur lors de la validation");
                    });
                });
            });
        });
    }

    loadSeances();
    window.reloadSeancesProf = loadSeances;
});
