document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#tableSeances tbody");

    if (!tableBody) return;

    /**
     * Charger les séances de l'élève
     */
    function loadSeances() {
        fetch("/public/eleve/getSeances", {
            method: "GET",
            credentials: "same-origin"
        })
            .then(res => res.json())
            .then(data => {
                if (data.status !== "success") return;

                tableBody.innerHTML = "";

                data.seances.forEach(seance => {
                    const tr = document.createElement("tr");

                    tr.innerHTML = `
                    <td>${seance.SPP_SEAN_DATE ?? ""}</td>
                    <td>${seance.SPP_SEAN_HEURE_DEB ?? ""}</td>
                    <td>${seance.SPP_SEAN_HEURE_FIN ?? ""}</td>
                    <td>${seance.SPP_SEAN_COMM ?? ""}</td>
                    <td>
                        <button type="button" disabled>Modifier</button>
                    </td>
                `;

                    tableBody.appendChild(tr);
                });
            })
            .catch(err => console.error("Erreur chargement séances :", err));
    }

    // Chargement initial
    loadSeances();

    // 🔁 rendre accessible à presence.js plus tard
    window.reloadSeances = loadSeances;
});
window.reloadSeances = function () {
    fetch("/eleve/getSeances")
        .then(res => {
            if (!res.ok) {
                throw new Error("HTTP " + res.status);
            }
            return res.text(); // 👈 HTML
        })
        .then(html => {
            document.getElementById("seances-container").innerHTML = html;
        })
        .catch(err => console.error("Erreur chargement séances :", err));
};
