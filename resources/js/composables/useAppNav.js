import { useRouter } from "vue-router";
import { useCustomers } from "./useCustomers";

export function useAppNav() {
    const router = useRouter();
    const { currentCustomerId } = useCustomers();

    function nav(screen, id = null) {
        const targetId = id ?? currentCustomerId.value;

        if (screen === "checkin") {
            router.push({ name: "checkinLocation", params: { id: targetId } });
            return;
        }
        if (screen === "customerDetail" && targetId) {
            router.push({ name: screen, params: { id: targetId } });
            return;
        }
        router.push({ name: screen });
    }

    function openDetail(id) {
        router.push({ name: "customerDetail", params: { id } });
    }

    return { nav, openDetail };
}
