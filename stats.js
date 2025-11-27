document.addEventListener("DOMContentLoaded", () => {
    // Roue de nav pour revenir sur home
    const wheel = document.getElementById("statsWheel");
    if (wheel) {
        wheel.addEventListener("click", () => {
            wheel.classList.add("rotating");
            setTimeout(() => {
                window.location.href = "home.php";
            }, 520);
        });
    }

    // Gestion modale avatar
    const avatarBtn = document.getElementById("openAvatarModal");
    const avatarModal = document.getElementById("avatarModal");
    const closeAvatar = document.getElementById("closeAvatarModal");

    if (avatarBtn && avatarModal && closeAvatar) {
        avatarBtn.addEventListener("click", () => {
            avatarModal.style.display = "flex";
        });

        closeAvatar.addEventListener("click", () => {
            avatarModal.style.display = "none";
        });

        // Fermer si on clique en dehors de la box
        avatarModal.addEventListener("click", (e) => {
            if (e.target === avatarModal) {
                avatarModal.style.display = "none";
            }
        });
    }
});
