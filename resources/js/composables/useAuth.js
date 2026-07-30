import { ref } from "vue";

const user = ref(null);
const csrfToken = ref("");
let initialized = false;

function initAuth(initialUser, token) {
    if (initialized) return;
    user.value = initialUser;
    csrfToken.value = token;
    initialized = true;
}

function getInitials(name) {
    if (!name) return "AW";
    return name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function logout() {
    document.getElementById("logout-form").submit();
}

export function useAuth() {
    return { user, csrfToken, initAuth, getInitials, logout };
}
