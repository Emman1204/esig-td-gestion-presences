document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#tableSeances tbody");

    if (!tableBody) return;


    /**
 * Calculer la durée entre deux heures au format HH:MM:SS
 * @param {string} debut - heure de début (HH:MM:SS)
 * @param {string} fin - heure de fin (HH:MM:SS)
 * @returns {string} durée formatée HH:MM:SS
 */
    function calculerTempsPresence(debut, fin) {
        if (!debut || !fin) return "00:00:00";

        const [hDeb, mDeb, sDeb] = debut.split(":").map(Number);
        const [hFin, mFin, sFin] = fin.split(":").map(Number);

        const dateDeb = new Date(0, 0, 0, hDeb, mDeb, sDeb);
        const dateFin = new Date(0, 0, 0, hFin, mFin, sFin);

        let diffMs = dateFin - dateDeb;
        if (diffMs < 0) diffMs = 0; // sécurité si heures manquantes ou fin < début

        const heures = String(Math.floor(diffMs / 1000 / 3600)).padStart(2, "0");
        const minutes = String(Math.floor((diffMs / 1000 % 3600) / 60)).padStart(2, "0");
        const secondes = String(Math.floor(diffMs / 1000 % 60)).padStart(2, "0");

        return `${heures}:${minutes}:${secondes}`;
    }
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
                    <td>${calculerTempsPresence(seance.SPP_SEAN_HEURE_DEB, seance.SPP_SEAN_HEURE_FIN)}</td>
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
