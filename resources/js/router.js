import { createRouter, createWebHashHistory } from "vue-router";
import HomeScreen from "./Pages/HomeScreen.vue";
import CustomersScreen from "./Pages/CustomersScreen.vue";
import CustomerDetailScreen from "./Pages/CustomerDetailScreen.vue";
import NewCustomerScreen from "./Pages/NewCustomerScreen.vue";
import OrderScreen from "./Pages/OrderScreen.vue";
import OrderFormScreen from "./Pages/OrderFormScreen.vue";
import SuccessStampScreen from "./Pages/SuccessStampScreen.vue";
import HistoryScreen from "./Pages/HistoryScreen.vue";
import ProfileScreen from "./Pages/ProfileScreen.vue";
import CheckinLocationScreen from "./Pages/CheckinLocationScreen.vue";
import CheckinResultScreen from "./Pages/CheckinResultScreen.vue";

const routes = [
    { path: "/", redirect: "/home" },
    { path: "/home", name: "home", component: HomeScreen },
    { path: "/customers", name: "customers", component: CustomersScreen },
    {
        path: "/customer-detail/:id?",
        name: "customerDetail",
        component: CustomerDetailScreen,
    },
    {
        path: "/checkin/:id/1",
        name: "checkinLocation",
        component: CheckinLocationScreen,
    },
    {
        path: "/checkin/:id/2",
        name: "checkinResult",
        component: CheckinResultScreen,
    },
    {
        path: "/new-customer",
        name: "newCustomer",
        component: NewCustomerScreen,
    },
    { path: "/order", name: "order", component: OrderScreen },
    { path: "/order-form", name: "orderForm", component: OrderFormScreen },
    {
        path: "/success-stamp",
        name: "successStamp",
        component: SuccessStampScreen,
    },
    { path: "/history", name: "history", component: HistoryScreen },
    { path: "/profile", name: "profile", component: ProfileScreen },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

export default router;
