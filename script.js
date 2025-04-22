// Fonction pour basculer la visibilité du mot de passe
function togglePasswordVisibility(inputId, toggleButton) {
    const passwordInput = document.getElementById(inputId);

    // Basculer entre 'password' et 'text'
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.classList.remove('fa-eye'); // Retirer l'icône "œil ouvert"
        toggleButton.classList.add('fa-eye-slash'); // Ajouter l'icône "œil barré"
    } else {
        passwordInput.type = 'password';
        toggleButton.classList.remove('fa-eye-slash'); // Retirer l'icône "œil barré"
        toggleButton.classList.add('fa-eye'); // Ajouter l'icône "œil ouvert"
    }
}

// Fonction pour basculer entre les formulaires de connexion et d'inscription
function toggleForm() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    // Basculer l'affichage des formulaires
    if (loginForm.style.display === 'none') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    }
}

// Ajouter des écouteurs d'événements après le chargement de la page
document.addEventListener('DOMContentLoaded', function () {
    // Ajouter un écouteur d'événement pour le bouton de bascule du mot de passe dans le formulaire de connexion
    const loginToggleButton = document.querySelector('#login-form .toggle-password i');
    if (loginToggleButton) {
        loginToggleButton.addEventListener('click', function () {
            togglePasswordVisibility('login-password', this);
        });
    }

    // Ajouter un écouteur d'événement pour le bouton de bascule du mot de passe dans le formulaire d'inscription
    const registerToggleButton = document.querySelector('#register-form .toggle-password i');
    if (registerToggleButton) {
        registerToggleButton.addEventListener('click', function () {
            togglePasswordVisibility('register-password', this);
        });
    }

    // Ajouter un écouteur d'événement pour le lien de bascule entre les formulaires
    const toggleLinks = document.querySelectorAll('.toggle-form');
    toggleLinks.forEach(link => {
        link.addEventListener('click', toggleForm);
    });
});