document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("eleveModal");
    const closeModal = document.getElementById("closeModal");
    const modalPrenom = document.getElementById("modalPrenom");
    const modalNom = document.getElementById("modalNom");
    const modalPointage = document.getElementById("modalPointage");
    const modalHistorique = document.getElementById("modalHistorique");

    // ===============================
    // 🔹 OUVERTURE / FERMETURE MODAL
    // ===============================
    function openModal() { modal.style.display = "flex"; }
    function closeModalFunc() { modal.style.display = "none"; }

    closeModal.addEventListener("click", closeModalFunc);
    window.addEventListener("click", (event) => {
        if (event.target === modal) closeModalFunc();
    });

    // ===============================
    // 🔹 FORMAT DATE
    // ===============================
    function formatDate(dateString) {
        const jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        const date = new Date(dateString);
        const jourNom = jours[date.getDay()];
        const jour = String(date.getDate()).padStart(2, '0');
        const mois = String(date.getMonth() + 1).padStart(2, '0');
        const annee = date.getFullYear();
        return `${jourNom} ${jour}.${mois}.${annee}`;
    }

    // ===============================
    // 🔹 COULEUR STATUT
    // ===============================
    function applyStatusColor(select) {
        select.classList.remove("status-grey", "status-green", "status-red", "status-yellow");
        switch (select.value) {
            case "PRÉSENT": select.classList.add("status-green"); break;
            case "ABSENT": select.classList.add("status-red"); break;
            case "RETARD": select.classList.add("status-yellow"); break;
            default: select.classList.add("status-grey");
        }
    }

    // ===============================
    // 🔹 SAUVEGARDE STATUT AJAX (fonction unique)
    // ===============================
    function attachStatusListener(select) {
        applyStatusColor(select);

        select.addEventListener("change", function () {
            applyStatusColor(this);

            // 🔹 Vérification data-seance-id
            const seanceId = this.dataset.seanceId;
            if (!seanceId) {
                console.error("⚠️ SeanceId manquant pour ce select :", this);
                alert("Erreur : impossible de mettre à jour le statut, identifiant manquant.");
                return; // arrêter l'exécution
            }

            const statut = this.value;
            console.log("🔹 Changement statut", { seanceId, statut });

            fetch("/public/enseignant/updateStatut", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                credentials: "same-origin",
                body: JSON.stringify({ seanceId, statut })
            })
                .then(res => res.text())
                .then(text => {
                    console.log("📥 Réponse brute updateStatut :", text);
                    try {
                        const data = JSON.parse(text);
                        if (data.status !== "success") {
                            alert("Erreur lors de la mise à jour : " + (data.message || "inconnue"));
                        }
                    } catch (e) {
                        console.error("Erreur parse JSON updateStatut :", e, text);
                        alert("Erreur lors de la mise à jour : réponse invalide du serveur");
                    }
                })
                .catch(err => console.error("Erreur update statut :", err));
        });
    }

    // ===============================
    // 🔹 CLICK SUR UN ÉLÈVE
    // ===============================
    document.querySelectorAll(".eleves-table tbody tr").forEach(tr => {
        tr.addEventListener("click", function () {
            const eleveId = this.dataset.eleveId;
            if (!eleveId) return console.error("ID élève introuvable");

            fetch(`/public/enseignant/eleve/${eleveId}`)
                .then(response => {
                    if (!response.ok) throw new Error("Erreur HTTP " + response.status);
                    return response.json();
                })
                .then(data => {
                    modalPrenom.textContent = data.eleve.SPP_UTIL_PRENOM;
                    modalNom.textContent = data.eleve.SPP_UTIL_NOM;

                    modalPointage.innerHTML = "";
                    modalHistorique.innerHTML = "";

                    console.log("📦 Historique reçu :", data.historique);

                    // 🔹 POINTAGES DU JOUR (PLUSIEURS)
                    if (data.pointagesJour && data.pointagesJour.length > 0) {

                        let html = `<h4>Pointages du jour</h4>`;

                        data.pointagesJour.forEach(p => {
                            html += `
            <div class="pointage-item">
                <p><strong>Heure début :</strong> ${p.SPP_SEAN_HEURE_DEB ?? '-'}</p>
                <p><strong>Heure fin :</strong> ${p.SPP_SEAN_HEURE_FIN ?? '-'}</p>
                <p><strong>Commentaire :</strong> ${p.SPP_SEAN_COMM ?? '-'}</p>
                <label>Statut :</label>
                <select class="status-select" data-seance-id="${p.SPP_SEAN_ID}">
                    <option value="EN ATTENTE" ${p.SPP_ENS_SEAN_STATUS === 'EN ATTENTE' ? 'selected' : ''}>EN ATTENTE</option>
                    <option value="PRÉSENT" ${p.SPP_ENS_SEAN_STATUS === 'PRÉSENT' ? 'selected' : ''}>PRÉSENT</option>
                    <option value="ABSENT" ${p.SPP_ENS_SEAN_STATUS === 'ABSENT' ? 'selected' : ''}>ABSENT</option>
                    <option value="RETARD" ${p.SPP_ENS_SEAN_STATUS === 'RETARD' ? 'selected' : ''}>RETARD</option>
                    <option value="EXCUSE" ${p.SPP_ENS_SEAN_STATUS === 'EXCUSE' ? 'selected' : ''}>EXCUSE</option>

                </select>
                <hr>
            </div>
        `;
                        });

                        modalPointage.innerHTML = html;

                        // 🔹 Attacher le listener à TOUS les selects
                        modalPointage.querySelectorAll(".status-select")
                            .forEach(select => attachStatusListener(select));

                    } else {
                        modalPointage.innerHTML = `<p>Aucun pointage aujourd’hui.</p>`;
                    }

                    // 🔹 HISTORIQUE DES POINTAGES
                    if (data.historique?.length > 0) {
                        let table = `
        <h4>Historique des pointages</h4>
        <div class="historique-wrapper">
            <table class="historique-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
        `;

                        data.historique.forEach(h => {
                            table += `
            <tr>
                <td>${formatDate(h.SPP_SEAN_DATE)}</td>
                <td>${h.SPP_SEAN_HEURE_DEB ?? '-'}</td>
                <td>${h.SPP_SEAN_HEURE_FIN ?? '-'}</td>
                <td>${h.SPP_SEAN_COMM ?? '-'}</td>
                <td>
                    <select class="status-select" data-seance-id="${h.SPP_SEAN_ID}">
                    
                        <option value="EN ATTENTE" ${h.SPP_ENS_SEAN_STATUS === 'EN ATTENTE' ? 'selected' : ''}>EN ATTENTE</option>
                        <option value="PRÉSENT" ${h.SPP_ENS_SEAN_STATUS === 'PRÉSENT' ? 'selected' : ''}>PRÉSENT</option>
                        <option value="ABSENT" ${h.SPP_ENS_SEAN_STATUS === 'ABSENT' ? 'selected' : ''}>ABSENT</option>
                        <option value="RETARD" ${h.SPP_ENS_SEAN_STATUS === 'RETARD' ? 'selected' : ''}>RETARD</option>
                        <option value="EXCUSE" ${h.SPP_ENS_SEAN_STATUS === 'EXCUSE' ? 'selected' : ''}>EXCUSE</option>

                    </select>
                </td>

            </tr>
        `;
                        });

                        table += `
                </tbody>
            </table>
        </div>
        `;

                        modalHistorique.innerHTML = table;

                        // 🔹 ATTACHER LE LISTENER À CHAQUE SELECT DE L'HISTORIQUE
                        modalHistorique.querySelectorAll(".status-select").forEach(select => attachStatusListener(select));
                    }

                    openModal();
                })
                .catch(error => console.error("Erreur lors du chargement des infos de l'élève :", error));
        });
    });

});