document.addEventListener("DOMContentLoaded", () => {

    // --- ton code existant ici ---
    // ...

    // 🔹 Étape modal : sélection des éléments du modal
    const modal = document.getElementById("eleveModal");
    const closeModal = document.getElementById("closeModal");
    const modalPrenom = document.getElementById("modalPrenom");
    const modalNom = document.getElementById("modalNom");
    const modalStatus = document.getElementById("modalStatus");

    function openModal(eleve) {
        modalPrenom.textContent = eleve.prenom;
        modalNom.textContent = eleve.nom;
        modalStatus.textContent = eleve.status || "Non pointé";
        modal.style.display = "block";
    }

    function closeModalFunc() {
        modal.style.display = "none";
    }

    closeModal.addEventListener("click", closeModalFunc);

    // fermer si clic en dehors du contenu
    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModalFunc();
        }
    });

    // 🔹 Ajouter l'événement sur chaque ligne d'élève
    function attachModalListeners() {
        document.querySelectorAll(".eleves-table tbody tr").forEach(tr => {
            tr.addEventListener("click", () => {
                const prenom = tr.cells[0].textContent.trim();
                const nom = tr.cells[1].textContent.trim();
                const status = tr.classList.contains("eleve-pointé") ? "EN ATTENTE" : "Non pointé";

                openModal({ prenom, nom, status });
            });
        });
    }

    attachModalListeners();

    // 🔹 Si tu recharges le tableau via AJAX, rappeler attachModalListeners()
    // window.reloadSeancesProf = () => { ... attachModalListeners(); ... }
});
