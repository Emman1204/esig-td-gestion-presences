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
    // 🔹 Fonction pour formater la date
    // ===============================
    function formatDate(dateString) {
        const jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        const date = new Date(dateString);
        const jourNom = jours[date.getDay()];
        const jour = String(date.getDate()).padStart(2, '0');
        const mois = String(date.getMonth() + 1).padStart(2, '0');
        const annee = date.getFullYear();
        return `${jourNom} ${jour}.${mois}.${annee}`;
    }

    // ===============================
    // 🔹 CLICK SUR UN ÉLÈVE
    // ===============================
    document.querySelectorAll(".eleves-table tbody tr").forEach(tr => {
        tr.addEventListener("click", function () {

            const eleveId = this.dataset.eleveId;
            if (!eleveId) return console.error("ID élève introuvable");

            fetch(`public/enseignant/eleve/${eleveId}`)
                .then(response => {
                    if (!response.ok) throw new Error("Erreur HTTP " + response.status);
                    return response.json();
                })
                .then(data => {

                    // ===============================
                    // INFOS ÉLÈVE
                    // ===============================
                    modalPrenom.textContent = data.eleve.SPP_UTIL_PRENOM;
                    modalNom.textContent = data.eleve.SPP_UTIL_NOM;

                    modalPointage.innerHTML = "";
                    modalHistorique.innerHTML = "";

                    // ===============================
                    // 🔹 POINTAGE DU JOUR
                    // ===============================
                    if (data.pointageJour) {
                        const p = data.pointageJour;
                        modalPointage.innerHTML = `
                            <h4>Pointage du jour</h4>
                            <p><strong>Heure début :</strong> ${p.SPP_SEAN_HEURE_DEB ?? '-'}</p>
                            <p><strong>Heure fin :</strong> ${p.SPP_SEAN_HEURE_FIN ?? '-'}</p>
                            <p><strong>Commentaire :</strong> ${p.SPP_SEAN_COMM ?? '-'}</p>
                            <label>Statut :</label>
                            <select id="statutSelect">
                                <option value="EN ATTENTE" ${p.SPP_ENS_SEAN_STATUS === 'EN ATTENTE' ? 'selected' : ''}>EN ATTENTE</option>
                                <option value="PRÉSENT" ${p.SPP_ENS_SEAN_STATUS === 'PRÉSENT' ? 'selected' : ''}>PRÉSENT</option>
                                <option value="ABSENT" ${p.SPP_ENS_SEAN_STATUS === 'ABSENT' ? 'selected' : ''}>ABSENT</option>
                                <option value="RETARD" ${p.SPP_ENS_SEAN_STATUS === 'RETARD' ? 'selected' : ''}>RETARD</option>
                            </select>
                        `;
                    } else {
                        modalPointage.innerHTML = `<p>Aucun pointage aujourd’hui.</p>`;
                    }

                    // ===============================
                    // 🔽 HISTORIQUE DES POINTAGES
                    // ===============================
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
                                </tr>
                            `;
                        });

                        table += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        modalHistorique.innerHTML = table;
                    }

                    openModal();
                })
                .catch(error => console.error("Erreur lors du chargement des infos de l'élève :", error));
        });
    });

});