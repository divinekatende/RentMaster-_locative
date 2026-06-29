// theme.js - À inclure sur toutes les pages
document.addEventListener("DOMContentLoaded", () => {
    // 1. Vérifier si un thème est déjà enregistré dans le localStorage
    const savedTheme = localStorage.getItem("rentmaster-theme");
    
    // 2. Si le thème enregistré est 'dark', on l'applique immédiatement
    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        
        // Ajustement spécifique pour la page paramètres si les éléments existent
        const themeToggle = document.getElementById('themeToggle');
        const darkModeCheckbox = document.getElementById('darkModeSetting');
        
        if (themeToggle) themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        if (darkModeCheckbox) darkModeCheckbox.checked = true;
    }
});