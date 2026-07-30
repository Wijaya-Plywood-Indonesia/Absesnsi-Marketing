import { ref } from "vue";

const successStamp = ref({
    title: "TERCATAT",
    isOrder: false,
    details: [],
    action: () => {},
});

function setSuccessStamp(stamp) {
    successStamp.value = stamp;
}

export function useSuccessStamp() {
    return { successStamp, setSuccessStamp };
}
