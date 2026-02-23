export async function fetchActions() {
    const response = await fetch("/data/lib_actions.json");
    const data = await response.json();

    return data.map((item: any) => ({
        item_id: item.for_id || item.for_id,
        item_title: item.for,
        return_value: {
            id: item.for_id,
            action: item.for
        }
    }));
}