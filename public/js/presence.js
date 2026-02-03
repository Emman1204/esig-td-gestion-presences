document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("presenceBtn");
    const timerEl = document.getElementById("timer");

    let timer = null;
    let seconds = 0;

    function startTimer() {
        timerEl.classList.remove("hidden");
        timer = setInterval(() => {
            seconds++;
            const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            timerEl.textContent = `${h}:${m}:${s}`;
        }, 1000);
    }

    btn.addEventListener("click", () => {
        const state = btn.dataset.state;
        const seanceId = btn.dataset.seanceId;

        fetch("/index.php?controller=eleve&action=presence", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                seanceId: seanceId,
                action: state
            })
        });

        if (state === "start") {
            btn.textContent = "Fin";
            btn.dataset.state = "end";
            btn.classList.add("fin");
            startTimer();
        } else {
            btn.textContent = "Présence terminée";
            btn.disabled = true;
            clearInterval(timer);
        }
    });
});
