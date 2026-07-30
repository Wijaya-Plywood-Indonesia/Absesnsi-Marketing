import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

const app = createApp({});
app.use(router);
app.component('app-component', App);
app.mount('#app');
