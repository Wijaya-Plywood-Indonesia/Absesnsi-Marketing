<template>
    <div class="bottomnav">
        <button
            class="navbtn"
            :class="{ active: currentTab === 'home' }"
            @click="$emit('nav', 'home')"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <path d="M3 11l9-8 9 8" />
                <path d="M5 10v10h14V10" /></svg
            >Beranda
        </button>
        <button
            class="navbtn"
            :class="{ active: currentTab === 'customers' }"
            @click="$emit('nav', 'customers')"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <circle cx="12" cy="8" r="3.2" />
                <path d="M4 20c1.6-4 4.6-6 8-6s6.4 2 8 6" /></svg
            >Customer
        </button>
        <button
            class="navbtn"
            :class="{ active: currentTab === 'order' }"
            @click="$emit('nav', 'order')"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                <line x1="3" y1="6" x2="21" y2="6" />
                <path d="M16 10a4 4 0 0 1-8 0" /></svg
            >Order
        </button>
        <button
            class="navbtn"
            :class="{ active: currentTab === 'history' }"
            @click="$emit('nav', 'history')"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <circle cx="12" cy="12" r="8.5" />
                <path d="M12 7v5l3.5 2" /></svg
            >Riwayat
        </button>
        <button
            class="navbtn"
            :class="{ active: currentTab === 'profile' }"
            @click="$emit('nav', 'profile')"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <circle cx="12" cy="8" r="3.5" />
                <path d="M5 20c1.5-4 4-6 7-6s5.5 2 7 6" /></svg
            >Profil
        </button>
    </div>
</template>

<script setup>
defineProps({
    currentTab: { type: String, required: true },
});
defineEmits(["nav"]);
</script>
