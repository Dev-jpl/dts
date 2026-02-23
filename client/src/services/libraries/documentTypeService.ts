export async function fetchDocumentTypes() {
    const response = await fetch("/data/lib_doc_type.json");
    const data = await response.json();

    return data.map((item: any) => ({
        item_id: item.type_id || item.type_id,
        item_title: `${item.type}-(${item.code})`,
        return_value: {
            id: item.type_id,
            code: item.code,
            type: item.type,
        }
    }));
}