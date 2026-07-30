import { ref } from "vue";
import { useAuth } from "./useAuth";

const orderList = ref([]);
const products = ref([]);
let ordersInitialized = false;
let productsInitialized = false;

function initOrders(initialOrders) {
    if (ordersInitialized) return;
    orderList.value = (initialOrders || []).map((o) => ({
        id: o.id,
        order_no: o.order_no,
        customer_id: o.customer_id,
        customer: o.customer || null,
        order_date: o.order_date,
        items: (o.order_items || o.orderItems || []).map((oi) => ({
            product_id: oi.product_id,
            name: oi.product ? oi.product.name : "",
            qty: oi.qty,
            unit: oi.unit,
        })),
        catatan: o.catatan,
        created_at: o.created_at,
    }));
    ordersInitialized = true;
}

function initProducts(initialProducts) {
    if (productsInitialized) return;
    products.value = initialProducts.map((p) => ({
        id: p.id,
        name: p.name,
        unit: p.unit,
    }));
    productsInitialized = true;
}

async function submitOrder(customerId, customerName, cartItems, note) {
    const { csrfToken } = useAuth();

    const res = await fetch("/api/order", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken.value,
        },
        body: JSON.stringify({
            customer_id: customerId,
            items: cartItems.map((p) => ({
                product_id: p.product_id,
                qty: p.qty,
            })),
            catatan: note,
        }),
    });
    const data = await res.json();

    if (data.success) {
        orderList.value.unshift({
            id: data.order.id,
            order_no: data.order.order_no,
            customer_id: customerId,
            customer: customerName ? { name: customerName } : null,
            order_date:
                data.order.order_date || new Date().toISOString().slice(0, 10),
            items: cartItems.map((p) => ({
                product_id: p.product_id,
                name: p.name,
                qty: p.qty,
                unit: p.unit,
            })),
            catatan: note,
            created_at: data.order.created_at || new Date().toISOString(),
        });
    }

    return data;
}

export function useOrders() {
    return { orderList, products, initOrders, initProducts, submitOrder };
}
